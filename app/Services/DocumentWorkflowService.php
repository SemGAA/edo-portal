<?php

namespace App\Services;

use App\Models\Document;
use App\Models\DocumentActionLog;
use App\Models\DocumentWorkflowStep;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class DocumentWorkflowService
{
    public function log(
        Document $document,
        ?User $actor,
        string $action,
        string $title,
        ?string $description = null,
        ?DocumentWorkflowStep $step = null,
        array $meta = []
    ): DocumentActionLog {
        return $document->actionLogs()->create([
            'user_id' => $actor?->id,
            'workflow_step_id' => $step?->id,
            'action' => $action,
            'title' => $title,
            'description' => $description,
            'meta' => $meta === [] ? null : $meta,
        ]);
    }

    public function submit(Document $document, User $actor): void
    {
        DB::transaction(function () use ($document, $actor) {
            $document->loadMissing(['category', 'approver']);

            $nextRound = (int) $document->workflow_round + 1;
            $this->skipOpenSteps($document, 'Маршрут обновлен новой отправкой документа.');

            $steps = $this->buildSteps($document);

            $document->update([
                'workflow_round' => $nextRound,
                'submitted_at' => now(),
                'approved_at' => null,
                'archived_at' => null,
                'rejection_reason' => null,
            ]);

            if ($steps === []) {
                $document->update([
                    'status' => 'approved',
                    'approved_at' => now(),
                ]);

                $this->log(
                    $document,
                    $actor,
                    'submitted',
                    'Документ согласован автоматически',
                    'Для документа не найдено обязательных этапов маршрута.',
                    null,
                    ['round' => $nextRound]
                );

                return;
            }

            foreach ($steps as $index => $stepData) {
                $document->workflowSteps()->create([
                    ...$stepData,
                    'round' => $nextRound,
                    'sequence' => $index + 1,
                    'status' => $index === 0
                        ? DocumentWorkflowStep::STATUS_IN_PROGRESS
                        : DocumentWorkflowStep::STATUS_PENDING,
                    'started_at' => $index === 0 ? now() : null,
                    'acted_at' => null,
                ]);
            }

            $document->update([
                'status' => 'in_review',
            ]);

            $this->log(
                $document,
                $actor,
                'submitted',
                'Документ отправлен на согласование',
                'Маршрут сформирован и передан на первый этап.',
                null,
                [
                    'round' => $nextRound,
                    'steps' => array_map(fn (array $step) => $step['title'], $steps),
                ]
            );
        });
    }

    public function approve(Document $document, User $actor, ?string $comment = null): void
    {
        DB::transaction(function () use ($document, $actor, $comment) {
            $currentStep = $document->current_step;

            if (!$currentStep) {
                return;
            }

            $currentStep->update([
                'status' => DocumentWorkflowStep::STATUS_APPROVED,
                'comment' => $comment,
                'acted_at' => now(),
            ]);

            $nextStep = $document->workflowSteps()
                ->where('round', $document->workflow_round)
                ->where('status', DocumentWorkflowStep::STATUS_PENDING)
                ->orderBy('sequence')
                ->first();

            if ($nextStep) {
                $nextStep->update([
                    'status' => DocumentWorkflowStep::STATUS_IN_PROGRESS,
                    'started_at' => now(),
                ]);

                $document->update([
                    'status' => 'in_review',
                    'rejection_reason' => null,
                ]);

                $this->log(
                    $document,
                    $actor,
                    'step_approved',
                    'Этап согласования завершен',
                    $comment ?: sprintf('Этап "%s" завершен без замечаний.', $currentStep->title),
                    $currentStep,
                    ['next_step' => $nextStep->title]
                );

                return;
            }

            $document->update([
                'status' => 'approved',
                'approved_at' => now(),
                'archived_at' => null,
                'rejection_reason' => null,
            ]);

            $this->log(
                $document,
                $actor,
                'approved',
                'Документ согласован по всем этапам',
                $comment ?: 'Маршрут завершен успешно.',
                $currentStep
            );
        });
    }

    public function reject(Document $document, User $actor, ?string $comment = null): void
    {
        DB::transaction(function () use ($document, $actor, $comment) {
            $currentStep = $document->current_step;

            if (!$currentStep) {
                return;
            }

            $reason = $comment ?: 'Документ возвращен на доработку без дополнительного комментария.';

            $currentStep->update([
                'status' => DocumentWorkflowStep::STATUS_REJECTED,
                'comment' => $reason,
                'acted_at' => now(),
            ]);

            $document->workflowSteps()
                ->where('round', $document->workflow_round)
                ->where('status', DocumentWorkflowStep::STATUS_PENDING)
                ->update([
                    'status' => DocumentWorkflowStep::STATUS_SKIPPED,
                    'comment' => 'Этап отменен после отклонения документа.',
                    'acted_at' => now(),
                ]);

            $document->update([
                'status' => 'rejected',
                'approved_at' => null,
                'archived_at' => null,
                'rejection_reason' => $reason,
            ]);

            $this->log(
                $document,
                $actor,
                'rejected',
                'Документ возвращен на доработку',
                $reason,
                $currentStep
            );
        });
    }

    public function archive(Document $document, User $actor, ?string $comment = null): void
    {
        DB::transaction(function () use ($document, $actor, $comment) {
            $document->update([
                'status' => 'archived',
                'archived_at' => now(),
            ]);

            $this->log(
                $document,
                $actor,
                'archived',
                'Документ переведен в архив',
                $comment ?: 'Карточка закрыта и помещена в электронный архив.'
            );
        });
    }

    /**
     * @return array<int, array<string, int|string|null>>
     */
    protected function buildSteps(Document $document): array
    {
        $steps = [];
        $usedUsers = [];

        $departmentHead = User::query()
            ->where('role', User::ROLE_DEPARTMENT_HEAD)
            ->whereHas('categories', fn ($query) => $query->whereKey($document->category_id))
            ->orderBy('name')
            ->first();

        if ($departmentHead && $departmentHead->id !== $document->author_id) {
            $steps[] = [
                'title' => 'Согласование руководителем отдела',
                'role_code' => DocumentWorkflowStep::ROLE_DEPARTMENT_HEAD,
                'user_id' => $departmentHead->id,
                'department_id' => $document->category_id,
            ];
            $usedUsers[] = $departmentHead->id;
        }

        if ($document->approver_id && !in_array($document->approver_id, $usedUsers, true)) {
            $steps[] = [
                'title' => 'Функциональное согласование',
                'role_code' => DocumentWorkflowStep::ROLE_APPROVER,
                'user_id' => $document->approver_id,
                'department_id' => $document->category_id,
            ];
            $usedUsers[] = (int) $document->approver_id;
        }

        $officeUser = User::query()
            ->where('role', User::ROLE_OFFICE)
            ->orderBy('name')
            ->first();

        if ($officeUser && !in_array($officeUser->id, $usedUsers, true)) {
            $steps[] = [
                'title' => 'Регистрация и завершение в канцелярии',
                'role_code' => DocumentWorkflowStep::ROLE_OFFICE,
                'user_id' => $officeUser->id,
                'department_id' => $document->category_id,
            ];
        }

        return $steps;
    }

    protected function skipOpenSteps(Document $document, string $comment): void
    {
        $document->workflowSteps()
            ->whereIn('status', [
                DocumentWorkflowStep::STATUS_PENDING,
                DocumentWorkflowStep::STATUS_IN_PROGRESS,
            ])
            ->update([
                'status' => DocumentWorkflowStep::STATUS_SKIPPED,
                'comment' => $comment,
                'acted_at' => now(),
            ]);
    }
}

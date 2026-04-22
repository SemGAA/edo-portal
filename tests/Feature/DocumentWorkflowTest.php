<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Document;
use App\Models\DocumentWorkflowStep;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DocumentWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_submission_builds_multi_step_route(): void
    {
        [$department, $author, $head, $approver, $office] = $this->makeWorkflowActors();

        $document = $this->makeDocument($department, $author, $approver);

        $this->actingAs($author)
            ->post(route('documents.submit', $document))
            ->assertRedirect(route('documents.show', $document));

        $document->refresh();
        $steps = $document->workflowSteps()->orderBy('sequence')->get();

        $this->assertSame('in_review', $document->status);
        $this->assertSame(1, $document->workflow_round);
        $this->assertCount(3, $steps);
        $this->assertSame($head->id, $steps[0]->user_id);
        $this->assertSame(DocumentWorkflowStep::STATUS_IN_PROGRESS, $steps[0]->status);
        $this->assertSame($approver->id, $steps[1]->user_id);
        $this->assertSame(DocumentWorkflowStep::STATUS_PENDING, $steps[1]->status);
        $this->assertSame($office->id, $steps[2]->user_id);
        $this->assertSame(DocumentWorkflowStep::STATUS_PENDING, $steps[2]->status);
    }

    public function test_approval_moves_document_through_all_steps(): void
    {
        [$department, $author, $head, $approver, $office] = $this->makeWorkflowActors();

        $document = $this->makeDocument($department, $author, $approver);

        $this->actingAs($author)->post(route('documents.submit', $document));
        $this->actingAs($head)->post(route('documents.approve', $document), ['comment' => 'Согласовано руководителем.']);

        $document->refresh();
        $this->assertSame($approver->id, $document->current_step?->user_id);

        $this->actingAs($approver)->post(route('documents.approve', $document), ['comment' => 'Юридических замечаний нет.']);

        $document->refresh();
        $this->assertSame($office->id, $document->current_step?->user_id);

        $this->actingAs($office)->post(route('documents.approve', $document), ['comment' => 'Документ зарегистрирован.']);

        $document->refresh();

        $this->assertSame('approved', $document->status);
        $this->assertNotNull($document->approved_at);
        $this->assertNull($document->current_step);
        $this->assertDatabaseHas('document_action_logs', [
            'document_id' => $document->id,
            'action' => 'approved',
        ]);
    }

    public function test_rejection_returns_document_to_author_and_skips_remaining_steps(): void
    {
        [$department, $author, $head, $approver] = $this->makeWorkflowActors();

        $document = $this->makeDocument($department, $author, $approver);

        $this->actingAs($author)->post(route('documents.submit', $document));
        $this->actingAs($head)->post(route('documents.approve', $document));
        $this->actingAs($approver)->post(route('documents.reject', $document), [
            'comment' => 'Нужно уточнить сроки и приложить новый график.',
        ]);

        $document->refresh();

        $this->assertSame('rejected', $document->status);
        $this->assertSame('Нужно уточнить сроки и приложить новый график.', $document->rejection_reason);
        $this->assertDatabaseHas('document_workflow_steps', [
            'document_id' => $document->id,
            'sequence' => 2,
            'status' => DocumentWorkflowStep::STATUS_REJECTED,
        ]);
        $this->assertDatabaseHas('document_workflow_steps', [
            'document_id' => $document->id,
            'sequence' => 3,
            'status' => DocumentWorkflowStep::STATUS_SKIPPED,
        ]);
    }

    public function test_office_can_archive_approved_document(): void
    {
        [$department, $author, $head, $approver, $office] = $this->makeWorkflowActors();

        $document = $this->makeDocument($department, $author, $approver);

        $this->actingAs($author)->post(route('documents.submit', $document));
        $this->actingAs($head)->post(route('documents.approve', $document));
        $this->actingAs($approver)->post(route('documents.approve', $document));
        $this->actingAs($office)->post(route('documents.approve', $document));
        $this->actingAs($office)->post(route('documents.archive', $document), [
            'comment' => 'Архивный номер присвоен.',
        ]);

        $document->refresh();

        $this->assertSame('archived', $document->status);
        $this->assertNotNull($document->archived_at);
        $this->assertDatabaseHas('document_action_logs', [
            'document_id' => $document->id,
            'action' => 'archived',
        ]);
    }

    /**
     * @return array{0: Category, 1: User, 2: User, 3: User, 4: User}
     */
    protected function makeWorkflowActors(): array
    {
        $department = Category::create([
            'name' => 'ИТ-служба',
            'code' => 'IT',
        ]);

        $author = User::factory()->create([
            'role' => User::ROLE_EMPLOYEE,
        ]);
        $author->categories()->sync([$department->id]);

        $head = User::factory()->create([
            'role' => User::ROLE_DEPARTMENT_HEAD,
        ]);
        $head->categories()->sync([$department->id]);

        $approver = User::factory()->create([
            'role' => User::ROLE_EMPLOYEE,
        ]);
        $approver->categories()->sync([$department->id]);

        $office = User::factory()->create([
            'role' => User::ROLE_OFFICE,
        ]);
        $office->categories()->sync([$department->id]);

        return [$department, $author, $head, $approver, $office];
    }

    protected function makeDocument(Category $department, User $author, User $approver): Document
    {
        return Document::create([
            'name' => 'Тестовый документ',
            'registration_number' => 'INT-' . now()->format('Y') . '-9999',
            'document_type' => 'internal',
            'status' => 'draft',
            'priority' => 'normal',
            'file' => 'test.txt',
            'summary' => 'Проверка маршрута согласования.',
            'external_partner' => null,
            'category_id' => $department->id,
            'author_id' => $author->id,
            'approver_id' => $approver->id,
            'visibility' => true,
            'workflow_round' => 0,
        ]);
    }
}

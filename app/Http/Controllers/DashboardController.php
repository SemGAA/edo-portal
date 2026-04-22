<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Document;
use App\Models\DocumentWorkflowStep;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class DashboardController extends Controller
{
    /**
     * Display the dashboard.
     */
    public function index(): View
    {
        $user = Auth::user();

        $documentsQuery = Document::query()
            ->with(['category', 'author', 'approver', 'workflowSteps.user'])
            ->visibleTo($user);

        $documents = (clone $documentsQuery)
            ->latest()
            ->take(8)
            ->get();

        $myTasks = Document::query()
            ->with(['category', 'author', 'workflowSteps.user'])
            ->visibleTo($user)
            ->whereHas('workflowSteps', function (Builder $query) use ($user) {
                $query
                    ->where('round', function ($roundQuery) {
                        $roundQuery->select('workflow_round')
                            ->from('documents')
                            ->whereColumn('documents.id', 'document_workflow_steps.document_id');
                    })
                    ->where('status', DocumentWorkflowStep::STATUS_IN_PROGRESS)
                    ->where('user_id', $user->id);
            })
            ->latest('submitted_at')
            ->take(6)
            ->get();

        $needsRevision = (clone $documentsQuery)
            ->where('author_id', $user->id)
            ->where('status', 'rejected')
            ->latest('updated_at')
            ->take(4)
            ->get();

        $stats = [
            [
                'label' => 'Всего документов',
                'value' => (clone $documentsQuery)->count(),
            ],
            [
                'label' => 'На маршруте',
                'value' => (clone $documentsQuery)->where('status', 'in_review')->count(),
            ],
            [
                'label' => 'Мои задачи',
                'value' => $myTasks->count(),
            ],
            [
                'label' => 'Просрочено',
                'value' => (clone $documentsQuery)
                    ->whereNotNull('due_at')
                    ->whereDate('due_at', '<', now()->toDateString())
                    ->whereIn('status', ['draft', 'in_review', 'rejected'])
                    ->count(),
            ],
        ];

        $statusSummary = collect(Document::STATUS_LABELS)
            ->map(function (string $label, string $status) use ($documentsQuery) {
                return [
                    'status' => $status,
                    'label' => $label,
                    'count' => (clone $documentsQuery)->where('status', $status)->count(),
                ];
            })
            ->values();

        $departments = $user->canSeeAllDocuments()
            ? Category::orderBy('name')->get()
            : $user->categories()->orderBy('name')->get();

        $adminSummary = null;
        if ($user->canSeeAllDocuments()) {
            $adminSummary = [
                'users' => User::count(),
                'departments' => Category::count(),
                'approvalLoad' => Document::where('status', 'in_review')->count(),
                'officeQueue' => DocumentWorkflowStep::where('status', DocumentWorkflowStep::STATUS_IN_PROGRESS)
                    ->where('role_code', DocumentWorkflowStep::ROLE_OFFICE)
                    ->count(),
            ];
        }

        return view('dashboard', [
            'documents' => $documents,
            'myTasks' => $myTasks,
            'needsRevision' => $needsRevision,
            'stats' => $stats,
            'statusSummary' => $statusSummary,
            'departments' => $departments,
            'adminSummary' => $adminSummary,
        ]);
    }
}

<?php

namespace App\Http\Livewire;

use App\Models\Category;
use App\Models\Document;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithPagination;

class DocumentJournal extends Component
{
    use WithPagination;

    public string $search = '';

    public ?int $categoryId = null;

    public string $status = '';

    public bool $onlyTasks = false;

    protected string $paginationTheme = 'tailwind';

    protected $queryString = [
        'search' => ['except' => ''],
        'categoryId' => ['as' => 'category_id', 'except' => null],
        'status' => ['except' => ''],
        'onlyTasks' => ['as' => 'only_tasks', 'except' => false],
    ];

    public function mount(
        string $search = '',
        ?int $categoryId = null,
        string $status = '',
        bool $onlyTasks = false
    ): void {
        $this->search = trim($search);
        $this->categoryId = $categoryId ?: null;
        $this->status = $status;
        $this->onlyTasks = $onlyTasks;
    }

    public function updated(): void
    {
        $this->resetPage();
    }

    public function clearFilters(): void
    {
        $this->reset(['search', 'categoryId', 'status', 'onlyTasks']);
        $this->resetPage();
    }

    public function render()
    {
        $user = $this->currentUser();
        $categories = $this->availableCategories($user);

        return view('livewire.document-journal', [
            'categories' => $categories,
            'documents' => $this->documentsQuery($user)->paginate(12),
            'activeDepartmentName' => $this->categoryId
                ? optional($categories->firstWhere('id', $this->categoryId))->name
                : 'Все отделы',
        ]);
    }

    protected function documentsQuery(User $user): Builder
    {
        $search = trim($this->search);

        return Document::query()
            ->with(['category', 'author', 'approver', 'workflowSteps.user'])
            ->visibleTo($user)
            ->when($this->categoryId, fn (Builder $query) => $query->where('category_id', $this->categoryId))
            ->when($this->status !== '', fn (Builder $query) => $query->where('status', $this->status))
            ->when($this->onlyTasks, fn (Builder $query) => $query->whereHas('workflowSteps', function (Builder $stepQuery) use ($user) {
                $stepQuery
                    ->where('round', function ($roundQuery) {
                        $roundQuery->select('workflow_round')
                            ->from('documents')
                            ->whereColumn('documents.id', 'document_workflow_steps.document_id');
                    })
                    ->where('status', 'in_progress')
                    ->where('user_id', $user->id);
            }))
            ->when($search !== '', function (Builder $query) use ($search) {
                $query->where(function (Builder $builder) use ($search) {
                    $builder
                        ->where('name', 'like', '%' . $search . '%')
                        ->orWhere('registration_number', 'like', '%' . $search . '%')
                        ->orWhere('summary', 'like', '%' . $search . '%')
                        ->orWhere('external_partner', 'like', '%' . $search . '%');
                });
            })
            ->latest();
    }

    protected function availableCategories(User $user)
    {
        return $user->canSeeAllDocuments()
            ? Category::orderBy('name')->get()
            : $user->categories()->orderBy('name')->get();
    }

    protected function currentUser(): User
    {
        /** @var User $user */
        $user = Auth::user();

        return $user;
    }
}

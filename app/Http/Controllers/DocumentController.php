<?php

namespace App\Http\Controllers;

use App\Http\Requests\DocumentStore;
use App\Http\Requests\DocumentUpdate;
use App\Models\Category;
use App\Models\Document;
use App\Models\User;
use App\Services\DocumentWorkflowService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Illuminate\View\View;

class DocumentController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): View
    {
        return view('documents.index', [
            'search' => trim((string) $request->input('search', '')),
            'activeCategory' => $request->integer('category_id') ?: null,
            'activeStatus' => (string) $request->input('status', ''),
            'onlyTasks' => $request->boolean('only_tasks'),
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): View
    {
        $user = Auth::user();

        return view('documents.create', [
            'categories' => $this->availableCategories($user),
            'approvers' => $this->workflowApprovers($user),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(DocumentStore $request, DocumentWorkflowService $workflowService): RedirectResponse
    {
        $user = Auth::user();
        $validatedData = $request->validated();

        $document = Document::create([
            ...$validatedData,
            'file' => $this->storeUploadedFile($validatedData['file'], $validatedData['name']),
            'registration_number' => $this->generateRegistrationNumber($validatedData['document_type']),
            'author_id' => $user->id,
            'status' => 'draft',
            'submitted_at' => null,
            'approved_at' => null,
            'archived_at' => null,
            'rejection_reason' => null,
            'workflow_round' => 0,
        ]);

        $workflowService->log(
            $document,
            $user,
            'created',
            'Документ создан',
            'Карточка зарегистрирована в системе и готова к отправке по маршруту.'
        );

        return redirect()
            ->route('documents.show', $document)
            ->with('status', 'Документ создан. Его можно отправить на маршрут согласования.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Document $document): View
    {
        $this->authorizeView($document);

        return view('documents.show', [
            'document' => $document->load([
                'category',
                'author',
                'approver',
                'workflowSteps.user',
                'workflowSteps.department',
                'actionLogs.user',
                'actionLogs.workflowStep',
            ]),
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Document $document): View
    {
        $user = Auth::user();
        abort_unless($document->isEditableBy($user), 403, 'Редактирование недоступно для этого документа.');

        return view('documents.edit', [
            'document' => $document,
            'categories' => $this->availableCategories($user),
            'approvers' => $this->workflowApprovers($user),
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(DocumentUpdate $request, Document $document, DocumentWorkflowService $workflowService): RedirectResponse
    {
        $user = Auth::user();
        abort_unless($document->isEditableBy($user), 403, 'Редактирование недоступно для этого документа.');

        $validatedData = $request->validated();

        if ($request->hasFile('file')) {
            $validatedData['file'] = $this->replaceUploadedFile(
                oldFileName: $document->file,
                uploadedFile: $validatedData['file'],
                documentName: $validatedData['name']
            );
        } else {
            unset($validatedData['file']);
        }

        if ($document->status === 'rejected') {
            $validatedData['status'] = 'draft';
            $validatedData['submitted_at'] = null;
            $validatedData['rejection_reason'] = null;
            $validatedData['approved_at'] = null;
            $validatedData['archived_at'] = null;
        }

        $document->update($validatedData);

        $workflowService->log(
            $document,
            $user,
            'updated',
            'Карточка документа обновлена',
            $document->status === 'draft'
                ? 'Изменения сохранены, документ можно повторно отправить на согласование.'
                : 'Содержимое документа и реквизиты были обновлены.'
        );

        return redirect()
            ->route('documents.show', $document)
            ->with('status', 'Документ обновлен.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Document $document, DocumentWorkflowService $workflowService): RedirectResponse
    {
        $user = Auth::user();
        abort_unless($document->isEditableBy($user) || $user->isAdmin(), 403, 'Удаление недоступно.');

        $workflowService->log(
            $document,
            $user,
            'deleted',
            'Документ удален',
            'Карточка переведена в удаленные записи.'
        );

        $document->delete();
        $this->deleteUploadedFile($document->file);

        return redirect()
            ->route('documents.index')
            ->with('status', 'Документ удален.');
    }

    /**
     * Search resource from storage.
     */
    public function search(Request $request): RedirectResponse
    {
        return redirect()->route('documents.index', [
            'search' => $request->input('search'),
            'category_id' => $request->input('category_id'),
            'status' => $request->input('status'),
            'only_tasks' => $request->boolean('only_tasks'),
        ]);
    }

    /**
     * Submit document for approval.
     */
    public function submit(Document $document, DocumentWorkflowService $workflowService): RedirectResponse
    {
        $user = Auth::user();
        abort_unless($document->canBeSubmittedBy($user), 403, 'Отправка на согласование недоступна.');

        $workflowService->submit($document, $user);

        return redirect()
            ->route('documents.show', $document)
            ->with('status', 'Документ отправлен по маршруту согласования.');
    }

    /**
     * Approve document.
     */
    public function approve(Request $request, Document $document, DocumentWorkflowService $workflowService): RedirectResponse
    {
        $user = Auth::user();
        abort_unless($document->canBeApprovedBy($user), 403, 'Согласование недоступно.');

        $validated = $request->validate([
            'comment' => ['nullable', 'string', 'max:1000'],
        ]);

        $workflowService->approve($document, $user, trim((string) ($validated['comment'] ?? '')) ?: null);

        return redirect()
            ->route('documents.show', $document)
            ->with('status', 'Этап маршрута подтвержден.');
    }

    /**
     * Reject document.
     */
    public function reject(Request $request, Document $document, DocumentWorkflowService $workflowService): RedirectResponse
    {
        $user = Auth::user();
        abort_unless($document->canBeApprovedBy($user), 403, 'Возврат документа недоступен.');

        $validated = $request->validate([
            'comment' => ['nullable', 'string', 'max:1000'],
        ]);

        $workflowService->reject(
            $document,
            $user,
            trim((string) ($validated['comment'] ?? '')) ?: 'Нужно внести правки и повторно отправить документ.'
        );

        return redirect()
            ->route('documents.show', $document)
            ->with('status', 'Документ возвращен на доработку.');
    }

    /**
     * Archive document.
     */
    public function archive(Request $request, Document $document, DocumentWorkflowService $workflowService): RedirectResponse
    {
        $user = Auth::user();
        abort_unless($document->canBeArchivedBy($user), 403, 'Архивация недоступна.');

        $validated = $request->validate([
            'comment' => ['nullable', 'string', 'max:1000'],
        ]);

        $workflowService->archive($document, $user, trim((string) ($validated['comment'] ?? '')) ?: null);

        return redirect()
            ->route('documents.show', $document)
            ->with('status', 'Документ отправлен в электронный архив.');
    }

    /**
     * Get the base query for visible documents.
     */
    protected function baseQuery(User $user): Builder
    {
        return Document::query()
            ->with(['category', 'author', 'approver', 'workflowSteps.user'])
            ->visibleTo($user)
            ->latest();
    }

    /**
     * Get categories available to the current user.
     */
    protected function availableCategories(User $user)
    {
        return $user->canSeeAllDocuments()
            ? Category::orderBy('name')->get()
            : $user->categories()->orderBy('name')->get();
    }

    /**
     * Get the list of possible functional approvers.
     */
    protected function workflowApprovers(User $user)
    {
        return User::query()
            ->where('role', '!=', User::ROLE_OFFICE)
            ->whereKeyNot($user->id)
            ->orderBy('role')
            ->orderBy('name')
            ->get();
    }

    /**
     * Ensure document is visible to the current user.
     */
    protected function authorizeView(Document $document): void
    {
        $user = Auth::user();

        $allowed = Document::query()
            ->whereKey($document->id)
            ->visibleTo($user)
            ->exists();

        abort_unless($allowed, 403, 'Просмотр недоступен.');
    }

    /**
     * Persist uploaded file in the public documents folder.
     */
    protected function storeUploadedFile($uploadedFile, string $documentName): string
    {
        $slug = now()->format('YmdHis') . '-' . Str::slug($documentName) . '.' . $uploadedFile->extension();
        $uploadedFile->move(public_path('files'), $slug);

        return $slug;
    }

    /**
     * Replace an uploaded file and delete the old version.
     */
    protected function replaceUploadedFile(string $oldFileName, $uploadedFile, string $documentName): string
    {
        $this->deleteUploadedFile($oldFileName);

        return $this->storeUploadedFile($uploadedFile, $documentName);
    }

    /**
     * Delete uploaded file if present.
     */
    protected function deleteUploadedFile(?string $fileName): void
    {
        if (!$fileName) {
            return;
        }

        $filePath = public_path('files/' . $fileName);
        if (File::exists($filePath)) {
            File::delete($filePath);
        }
    }

    /**
     * Generate a registration number based on type and date.
     */
    protected function generateRegistrationNumber(string $documentType): string
    {
        $prefixes = [
            'internal' => 'INT',
            'incoming' => 'IN',
            'outgoing' => 'OUT',
            'contract' => 'CTR',
            'order' => 'ORD',
        ];

        $sequence = str_pad((string) ((Document::withTrashed()->max('id') ?? 0) + 1), 4, '0', STR_PAD_LEFT);

        return sprintf(
            '%s-%s-%s',
            $prefixes[$documentType] ?? 'DOC',
            now()->format('Y'),
            $sequence
        );
    }
}

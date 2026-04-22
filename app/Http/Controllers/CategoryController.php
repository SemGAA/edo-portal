<?php

namespace App\Http\Controllers;

use App\Http\Requests\CategoryStore;
use App\Http\Requests\CategoryUpdate;
use App\Models\Category;
use App\Models\Document;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class CategoryController extends Controller
{
    /**
     * Display departments list.
     */
    public function index(Request $request): View
    {
        abort_unless(Auth::user()->isAdmin(), 403);

        $search = trim((string) $request->input('search', ''));
        $categories = Category::query()
            ->when($search !== '', fn ($query) => $query->where('name', 'like', '%' . $search . '%'))
            ->orderBy('name')
            ->paginate(12)
            ->withQueryString();

        return view('categories.index', [
            'categories' => $categories,
            'search' => $search,
            'activeCategory' => null,
        ]);
    }

    /**
     * Store department.
     */
    public function store(CategoryStore $request): RedirectResponse
    {
        abort_unless(Auth::user()->isAdmin(), 403);

        Category::create($request->validated());

        return redirect()
            ->route('categories.index')
            ->with('status', 'Отдел добавлен.');
    }

    /**
     * Show documents for the given department.
     */
    public function show(Category $category): View
    {
        $user = Auth::user();
        $isAllowed = $user->canSeeAllDocuments() || $user->categories->contains($category->id);
        abort_unless($isAllowed, 403);

        $categories = $user->canSeeAllDocuments()
            ? Category::orderBy('name')->get()
            : $user->categories()->orderBy('name')->get();

        $documents = Document::query()
            ->with(['category', 'author', 'approver', 'workflowSteps.user'])
            ->visibleTo($user)
            ->where('category_id', $category->id)
            ->latest()
            ->paginate(12);

        return view('documents.index', [
            'documents' => $documents,
            'categories' => $categories,
            'search' => '',
            'name' => $category->name,
            'activeCategory' => $category->id,
            'activeStatus' => null,
            'onlyTasks' => false,
        ]);
    }

    /**
     * Update department.
     */
    public function update(CategoryUpdate $request, Category $category): RedirectResponse
    {
        abort_unless(Auth::user()->isAdmin(), 403);

        $category->update($request->validated());

        return redirect()
            ->route('categories.index')
            ->with('status', 'Отдел обновлен.');
    }

    /**
     * Delete department.
     */
    public function destroy(Category $category): RedirectResponse
    {
        abort_unless(Auth::user()->isAdmin(), 403);

        $category->delete();

        return redirect()
            ->route('categories.index')
            ->with('status', 'Отдел удален.');
    }

    /**
     * Redirect search requests to the index.
     */
    public function search(Request $request): RedirectResponse
    {
        return redirect()->route('categories.index', [
            'search' => $request->input('search'),
        ]);
    }
}

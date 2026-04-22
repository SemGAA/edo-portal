<?php

namespace App\Http\Controllers;

use App\Http\Requests\UserStore;
use App\Http\Requests\UserUpdate;
use App\Models\Category;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class UserController extends Controller
{
    /**
     * Display users list.
     */
    public function index(Request $request): View
    {
        abort_unless(auth()->user()->isAdmin(), 403);

        $search = trim((string) $request->input('search', ''));
        $users = User::query()
            ->with('categories')
            ->when($search !== '', fn ($query) => $query
                ->where('name', 'like', '%' . $search . '%')
                ->orWhere('email', 'like', '%' . $search . '%')
                ->orWhere('position', 'like', '%' . $search . '%'))
            ->orderBy('role')
            ->orderBy('name')
            ->paginate(12)
            ->withQueryString();

        return view('users.index', [
            'users' => $users,
            'categories' => Category::orderBy('name')->get(),
            'search' => $search,
        ]);
    }

    /**
     * Show the form for creating a new user.
     */
    public function create(): View
    {
        abort_unless(auth()->user()->isAdmin(), 403);

        return view('users.create', [
            'categories' => Category::orderBy('name')->get(),
        ]);
    }

    /**
     * Store a newly created user.
     */
    public function store(UserStore $request): RedirectResponse
    {
        abort_unless(auth()->user()->isAdmin(), 403);

        $validated = $request->validated();
        $categories = $validated['categories'];
        unset($validated['categories']);
        $validated['role'] = (int) ($validated['role'] ?? User::ROLE_EMPLOYEE);

        $user = User::create($validated);
        $user->categories()->sync($categories);

        event(new Registered($user));

        return redirect()
            ->route('users.index')
            ->with('status', 'Сотрудник добавлен.');
    }

    /**
     * Show the form for editing a user.
     */
    public function edit(User $user): View
    {
        abort_unless(auth()->user()->isAdmin(), 403);

        return view('users.edit', [
            'user' => $user->load('categories'),
            'categories' => Category::orderBy('name')->get(),
        ]);
    }

    /**
     * Update user.
     */
    public function update(UserUpdate $request, User $user): RedirectResponse
    {
        abort_unless(auth()->user()->isAdmin(), 403);

        $validated = $request->validated();
        $categories = $validated['categories'];
        unset($validated['categories']);

        if (empty($validated['password'])) {
            unset($validated['password']);
        }

        $validated['role'] = (int) ($validated['role'] ?? $user->role);
        $user->update($validated);
        $user->categories()->sync($categories);

        return redirect()
            ->route('users.index')
            ->with('status', 'Данные сотрудника обновлены.');
    }

    /**
     * Delete user.
     */
    public function destroy(User $user): RedirectResponse
    {
        abort_unless(auth()->user()->isAdmin(), 403);
        abort_if($user->id === auth()->id(), 422, 'Нельзя удалить самого себя.');

        $user->delete();

        return redirect()
            ->route('users.index')
            ->with('status', 'Сотрудник удален.');
    }

    /**
     * Redirect search to the index page.
     */
    public function search(Request $request): RedirectResponse
    {
        return redirect()->route('users.index', [
            'search' => $request->input('search'),
        ]);
    }
}

@extends('layouts.system')

@section('title', 'Сотрудники')
@section('page-title', 'Сотрудники')
@section('eyebrow', 'Учетные записи и доступы')

@section('page-actions')
    <a href="{{ route('users.create') }}" class="btn-primary">Добавить сотрудника</a>
@endsection

@section('content')
    <section class="panel overflow-hidden">
        <div class="panel-header">
            <div>
                <h2 class="text-base font-semibold text-slate-900">Список сотрудников</h2>
                <p class="text-sm text-slate-500">Назначайте роли и отделы, чтобы управлять доступом и маршрутом согласования.</p>
            </div>
            <form method="POST" action="{{ route('users.search') }}" class="flex gap-3">
                @csrf
                <input type="text" name="search" value="{{ $search }}" class="block rounded-md border-slate-300 shadow-sm focus:border-slate-400 focus:ring-slate-400" placeholder="Поиск по ФИО, почте, должности">
                <button type="submit" class="btn-secondary">Найти</button>
            </form>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-sm">
                <thead class="bg-slate-50 text-left text-slate-500">
                    <tr>
                        <th class="px-5 py-3 font-medium">Сотрудник</th>
                        <th class="px-5 py-3 font-medium">Должность</th>
                        <th class="px-5 py-3 font-medium">Отделы</th>
                        <th class="px-5 py-3 font-medium">Роль</th>
                        <th class="px-5 py-3 font-medium">Действия</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200 bg-white">
                    @forelse ($users as $user)
                        <tr class="hover:bg-slate-50">
                            <td class="px-5 py-4">
                                <div class="font-medium text-slate-900">{{ $user->name }}</div>
                                <div class="mt-1 text-xs text-slate-500">{{ $user->email }}</div>
                            </td>
                            <td class="px-5 py-4 text-slate-600">{{ $user->position ?: 'Не указана' }}</td>
                            <td class="px-5 py-4">
                                <div class="flex flex-wrap gap-2">
                                    @forelse ($user->categories as $category)
                                        <span class="badge-slate">{{ $category->name }}</span>
                                    @empty
                                        <span class="text-slate-400">Не назначены</span>
                                    @endforelse
                                </div>
                            </td>
                            <td class="px-5 py-4">
                                <span class="{{ $user->isAdmin() ? 'badge-blue' : ($user->isOffice() ? 'badge-amber' : 'badge-slate') }}">
                                    {{ $user->role_label }}
                                </span>
                            </td>
                            <td class="px-5 py-4">
                                <div class="flex gap-3">
                                    <a href="{{ route('users.edit', $user) }}" class="btn-secondary">Изменить</a>
                                    @if ($user->id !== auth()->id())
                                        <form method="POST" action="{{ route('users.destroy', $user) }}">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn-danger" onclick="return confirm('Удалить сотрудника?')">Удалить</button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-5 py-14 text-center text-slate-500">Сотрудники не найдены.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="border-t border-slate-200 px-5 py-4">
            {{ $users->links() }}
        </div>
    </section>
@endsection

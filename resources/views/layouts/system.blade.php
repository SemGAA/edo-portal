<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title') - {{ config('app.name') }}</title>
    @livewireStyles
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="app-shell" x-data="{ sidebarOpen: false }">
    <div class="min-h-screen lg:flex">
        <div
            x-cloak
            x-show="sidebarOpen"
            x-transition.opacity
            class="fixed inset-0 z-30 bg-slate-900/40 lg:hidden"
            @click="sidebarOpen = false"
        ></div>

        <aside
            class="fixed inset-y-0 left-0 z-40 flex w-72 -translate-x-full flex-col border-r border-slate-200 bg-white transition lg:static lg:translate-x-0"
            :class="sidebarOpen ? 'translate-x-0' : ''"
        >
            <div class="border-b border-slate-200 px-6 py-5">
                <a href="{{ route('dashboard') }}" class="flex items-center gap-3">
                    <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-slate-900 text-sm font-semibold text-white">
                        ЭДО
                    </div>
                    <div>
                        <div class="text-sm font-semibold text-slate-900">{{ config('app.name') }}</div>
                        <div class="text-xs text-slate-500">Документооборот предприятия</div>
                    </div>
                </a>
            </div>

            <div class="flex-1 overflow-y-auto px-4 py-5">
                <div class="space-y-1">
                    <a href="{{ route('dashboard') }}"
                       class="flex items-center rounded-md px-3 py-2 text-sm font-medium {{ request()->routeIs('dashboard') ? 'bg-slate-900 text-white' : 'text-slate-700 hover:bg-slate-100' }}">
                        Главная
                    </a>
                    <a href="{{ route('documents.index') }}"
                       class="flex items-center rounded-md px-3 py-2 text-sm font-medium {{ request()->routeIs('documents.*') ? 'bg-slate-900 text-white' : 'text-slate-700 hover:bg-slate-100' }}">
                        Документы
                    </a>
                    @if (auth()->user()->isAdmin())
                        <a href="{{ route('categories.index') }}"
                           class="flex items-center rounded-md px-3 py-2 text-sm font-medium {{ request()->routeIs('categories.*') ? 'bg-slate-900 text-white' : 'text-slate-700 hover:bg-slate-100' }}">
                            Отделы
                        </a>
                        <a href="{{ route('users.index') }}"
                           class="flex items-center rounded-md px-3 py-2 text-sm font-medium {{ request()->routeIs('users.*') ? 'bg-slate-900 text-white' : 'text-slate-700 hover:bg-slate-100' }}">
                            Сотрудники
                        </a>
                    @endif
                    <a href="{{ route('profile.edit') }}"
                       class="flex items-center rounded-md px-3 py-2 text-sm font-medium {{ request()->routeIs('profile.*') ? 'bg-slate-900 text-white' : 'text-slate-700 hover:bg-slate-100' }}">
                        Профиль
                    </a>
                </div>

                <div class="mt-8">
                    <div class="px-3 text-xs font-semibold uppercase tracking-wide text-slate-400">Мои отделы</div>
                    <div class="mt-3 space-y-1">
                        @forelse (auth()->user()->canSeeAllDocuments() ? \App\Models\Category::orderBy('name')->limit(6)->get() : auth()->user()->categories()->orderBy('name')->get() as $department)
                            <a href="{{ route('categories.show', $department) }}"
                               class="flex items-center justify-between rounded-md px-3 py-2 text-sm text-slate-600 hover:bg-slate-100">
                                <span>{{ $department->name }}</span>
                                @if ($department->code)
                                    <span class="badge-slate">{{ $department->code }}</span>
                                @endif
                            </a>
                        @empty
                            <div class="px-3 py-2 text-sm text-slate-400">Отделы не назначены.</div>
                        @endforelse
                    </div>
                </div>
            </div>

            <div class="border-t border-slate-200 px-4 py-4">
                <div class="px-3 text-sm font-medium text-slate-800">{{ auth()->user()->name }}</div>
                <div class="px-3 text-xs text-slate-500">{{ auth()->user()->role_label }}{{ auth()->user()->position ? ' · ' . auth()->user()->position : '' }}</div>
                <form method="POST" action="{{ route('logout') }}" class="mt-3 px-3">
                    @csrf
                    <button type="submit" class="btn-secondary w-full">Выйти</button>
                </form>
            </div>
        </aside>

        <main class="min-w-0 flex-1">
            <header class="border-b border-slate-200 bg-white/90 backdrop-blur">
                <div class="mx-auto flex max-w-7xl items-center justify-between gap-4 px-4 py-4 sm:px-6 lg:px-8">
                    <div class="flex items-center gap-3">
                        <button
                            type="button"
                            class="inline-flex h-10 w-10 items-center justify-center rounded-md border border-slate-200 text-slate-700 lg:hidden"
                            @click="sidebarOpen = !sidebarOpen"
                        >
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="h-5 w-5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 5.25h16.5M3.75 12h16.5m-16.5 6.75h16.5" />
                            </svg>
                        </button>
                        <div>
                            <div class="text-sm text-slate-500">@yield('eyebrow', 'Корпоративная система')</div>
                            <h1 class="text-xl font-semibold text-slate-900">@yield('page-title')</h1>
                        </div>
                    </div>
                    <div class="flex items-center gap-3">
                        @yield('page-actions')
                    </div>
                </div>
            </header>

            <div class="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8">
                @if (session('status'))
                    <div class="mb-5 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">
                        {{ session('status') }}
                    </div>
                @endif

                @if ($errors->any())
                    <div class="mb-5 rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">
                        <div class="font-medium">Проверьте форму:</div>
                        <ul class="mt-2 list-disc space-y-1 pl-5">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                @yield('content')
            </div>
        </main>
    </div>
    @livewireScripts
</body>
</html>

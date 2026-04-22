@extends('layouts.system')

@section('title', 'Отделы')
@section('page-title', 'Отделы предприятия')
@section('eyebrow', 'Администрирование структуры')

@section('content')
    <div class="grid gap-6 xl:grid-cols-[360px,1fr]">
        <section class="panel p-5">
            <h2 class="text-base font-semibold text-slate-900">Новый отдел</h2>
            <p class="mt-1 text-sm text-slate-500">Код отдела можно использовать в регистрационных формах и отчётах.</p>

            <form method="POST" action="{{ route('categories.store') }}" class="mt-5 space-y-4">
                @csrf
                <div>
                    <x-input-label for="department_name" :value="'Название отдела'" />
                    <x-text-input id="department_name" name="name" type="text" class="mt-1 block w-full" required />
                </div>
                <div>
                    <x-input-label for="department_code" :value="'Код отдела'" />
                    <x-text-input id="department_code" name="code" type="text" class="mt-1 block w-full" placeholder="Например, FIN" />
                </div>
                <button type="submit" class="btn-primary w-full">Добавить отдел</button>
            </form>
        </section>

        <section class="panel overflow-hidden">
            <div class="panel-header">
                <div>
                    <h2 class="text-base font-semibold text-slate-900">Справочник отделов</h2>
                    <p class="text-sm text-slate-500">Отделы назначаются сотрудникам и используются для маршрутизации документов.</p>
                </div>
                <form method="POST" action="{{ route('categories.search') }}" class="flex gap-3">
                    @csrf
                    <input type="text" name="search" value="{{ $search }}" class="block rounded-md border-slate-300 shadow-sm focus:border-slate-400 focus:ring-slate-400" placeholder="Поиск отдела">
                    <button type="submit" class="btn-secondary">Найти</button>
                </form>
            </div>

            <div class="divide-y divide-slate-200">
                @forelse ($categories as $category)
                    <div class="px-5 py-5">
                        <div class="grid gap-4 lg:grid-cols-[1fr,180px,auto] lg:items-end">
                            <form method="POST" action="{{ route('categories.update', $category) }}" class="contents">
                            @csrf
                            @method('PUT')
                            <div>
                                <x-input-label :value="'Название'" />
                                <x-text-input name="name" type="text" class="mt-1 block w-full" :value="$category->name" />
                            </div>
                            <div>
                                <x-input-label :value="'Код'" />
                                <x-text-input name="code" type="text" class="mt-1 block w-full" :value="$category->code" />
                            </div>
                            <button type="submit" class="btn-secondary">Сохранить</button>
                            </form>
                            <form method="POST" action="{{ route('categories.destroy', $category) }}">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn-danger w-full" onclick="return confirm('Удалить отдел?')">Удалить</button>
                            </form>
                        </div>
                    </div>
                @empty
                    <div class="px-5 py-12 text-center text-slate-500">Отделы еще не добавлены.</div>
                @endforelse
            </div>

            <div class="border-t border-slate-200 px-5 py-4">
                {{ $categories->links() }}
            </div>
        </section>
    </div>
@endsection

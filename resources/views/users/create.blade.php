@extends('layouts.system')

@section('title', 'Новый сотрудник')
@section('page-title', 'Новый сотрудник')
@section('eyebrow', 'Администрирование пользователей')

@section('page-actions')
    <a href="{{ route('users.index') }}" class="btn-secondary">К списку</a>
@endsection

@section('content')
    <section class="panel">
        <div class="panel-header">
            <div>
                <h2 class="text-base font-semibold text-slate-900">Карточка сотрудника</h2>
                <p class="text-sm text-slate-500">Сотрудник получит доступ к документам назначенных отделов.</p>
            </div>
        </div>
        <form method="POST" action="{{ route('users.store') }}" class="space-y-6 px-5 py-5">
            @csrf
            @include('users._form')

            <div class="flex flex-wrap items-center gap-3 border-t border-slate-200 pt-5">
                <button type="submit" class="btn-primary">Создать сотрудника</button>
                <a href="{{ route('users.index') }}" class="btn-secondary">Отмена</a>
            </div>
        </form>
    </section>
@endsection

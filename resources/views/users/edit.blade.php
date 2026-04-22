@extends('layouts.system')

@section('title', 'Редактирование сотрудника')
@section('page-title', 'Редактирование сотрудника')
@section('eyebrow', $user->email)

@section('page-actions')
    <a href="{{ route('users.index') }}" class="btn-secondary">К списку</a>
@endsection

@section('content')
    <section class="panel">
        <div class="panel-header">
            <div>
                <h2 class="text-base font-semibold text-slate-900">{{ $user->name }}</h2>
                <p class="text-sm text-slate-500">Меняйте отделы, роль и контактные данные без удаления учетной записи.</p>
            </div>
        </div>
        <form method="POST" action="{{ route('users.update', $user) }}" class="space-y-6 px-5 py-5">
            @csrf
            @method('PUT')
            @include('users._form', ['user' => $user])

            <div class="flex flex-wrap items-center gap-3 border-t border-slate-200 pt-5">
                <button type="submit" class="btn-primary">Сохранить</button>
                <a href="{{ route('users.index') }}" class="btn-secondary">Отмена</a>
            </div>
        </form>
    </section>
@endsection

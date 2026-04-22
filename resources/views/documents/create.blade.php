@extends('layouts.system')

@section('title', 'Новый документ')
@section('page-title', 'Новый документ')
@section('eyebrow', 'Регистрация документа')

@section('page-actions')
    <a href="{{ route('documents.index') }}" class="btn-secondary">К журналу</a>
@endsection

@section('content')
    <section class="panel">
        <div class="panel-header">
            <div>
                <h2 class="text-base font-semibold text-slate-900">Карточка документа</h2>
                <p class="text-sm text-slate-500">Заполните реквизиты, приложите файл и отправьте документ на согласование после сохранения.</p>
            </div>
        </div>
        <form method="POST" action="{{ route('documents.store') }}" enctype="multipart/form-data" class="space-y-6 px-5 py-5">
            @csrf
            @include('documents._form')

            <div class="flex flex-wrap items-center gap-3 border-t border-slate-200 pt-5">
                <button type="submit" class="btn-primary">Сохранить документ</button>
                <a href="{{ route('documents.index') }}" class="btn-secondary">Отмена</a>
            </div>
        </form>
    </section>
@endsection

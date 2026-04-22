@extends('layouts.system')

@section('title', 'Редактирование документа')
@section('page-title', 'Редактирование документа')
@section('eyebrow', $document->registration_number)

@section('page-actions')
    <a href="{{ route('documents.show', $document) }}" class="btn-secondary">К карточке</a>
@endsection

@section('content')
    <section class="panel">
        <div class="panel-header">
            <div>
                <h2 class="text-base font-semibold text-slate-900">{{ $document->name }}</h2>
                <p class="text-sm text-slate-500">Редактирование доступно для черновиков и документов, возвращенных на доработку.</p>
            </div>
        </div>
        <form method="POST" action="{{ route('documents.update', $document) }}" enctype="multipart/form-data" class="space-y-6 px-5 py-5">
            @csrf
            @method('PUT')
            @include('documents._form', ['document' => $document])

            <div class="flex flex-wrap items-center gap-3 border-t border-slate-200 pt-5">
                <button type="submit" class="btn-primary">Сохранить изменения</button>
                <a href="{{ route('documents.show', $document) }}" class="btn-secondary">Отмена</a>
            </div>
        </form>
    </section>
@endsection

@extends('layouts.system')

@section('title', 'Журнал документов')
@section('page-title', 'Журнал документов')
@section('eyebrow', 'Livewire-интерфейс документооборота')

@section('page-actions')
    <a href="{{ route('documents.create') }}" class="btn-primary">Новый документ</a>
@endsection

@section('content')
    @livewire('document-journal', [
        'search' => $search,
        'categoryId' => $activeCategory,
        'status' => $activeStatus,
        'onlyTasks' => $onlyTasks,
    ])
@endsection

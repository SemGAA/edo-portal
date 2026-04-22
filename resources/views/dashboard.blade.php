@extends('layouts.system')

@section('title', 'Главная')
@section('page-title', 'Панель документооборота')
@section('eyebrow', auth()->user()->canSeeAllDocuments() ? 'Контур управления маршрутом' : 'Рабочее место сотрудника')

@section('page-actions')
    <a href="{{ route('documents.create') }}" class="btn-primary">Новый документ</a>
@endsection

@php
    $badgeClass = function (string $status): string {
        return match ($status) {
            'draft' => 'badge-slate',
            'in_review' => 'badge-amber',
            'approved', 'archived' => 'badge-emerald',
            'rejected' => 'badge-rose',
            default => 'badge-blue',
        };
    };
@endphp

@section('content')
    <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
        @foreach ($stats as $stat)
            <div class="panel p-5">
                <div class="text-sm text-slate-500">{{ $stat['label'] }}</div>
                <div class="mt-3 text-3xl font-semibold text-slate-900">{{ $stat['value'] }}</div>
            </div>
        @endforeach
    </div>

    <div class="mt-6 grid gap-6 xl:grid-cols-[2fr,1fr]">
        <div class="space-y-6">
            <section class="panel overflow-hidden">
                <div class="panel-header">
                    <div>
                        <h2 class="text-base font-semibold text-slate-900">Последние документы</h2>
                        <p class="text-sm text-slate-500">Свежие карточки и текущее состояние маршрутов.</p>
                    </div>
                    <a href="{{ route('documents.index') }}" class="btn-secondary">Открыть журнал</a>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200 text-sm">
                        <thead class="bg-slate-50 text-left text-slate-500">
                            <tr>
                                <th class="px-5 py-3 font-medium">Номер</th>
                                <th class="px-5 py-3 font-medium">Документ</th>
                                <th class="px-5 py-3 font-medium">Маршрут</th>
                                <th class="px-5 py-3 font-medium">Статус</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-200 bg-white">
                            @forelse ($documents as $document)
                                <tr class="hover:bg-slate-50">
                                    <td class="px-5 py-4 font-medium text-slate-700">{{ $document->registration_number }}</td>
                                    <td class="px-5 py-4">
                                        <a href="{{ route('documents.show', $document) }}" class="font-medium text-slate-900 hover:text-slate-700">
                                            {{ $document->name }}
                                        </a>
                                        <div class="mt-1 text-xs text-slate-500">{{ $document->category?->name ?: 'Без отдела' }}</div>
                                    </td>
                                    <td class="px-5 py-4 text-slate-600">
                                        <div class="font-medium text-slate-800">{{ $document->current_step_label }}</div>
                                        <div class="mt-1 text-xs text-slate-500">{{ $document->current_step?->assignee_name ?? 'Нет активного исполнителя' }}</div>
                                    </td>
                                    <td class="px-5 py-4">
                                        <span class="{{ $badgeClass($document->status) }}">{{ $document->status_label }}</span>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="px-5 py-12 text-center text-slate-500">Документы пока не загружены.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </section>

            <section class="panel p-5">
                <div class="flex items-center justify-between">
                    <h2 class="text-base font-semibold text-slate-900">Мои активные задачи</h2>
                    <span class="badge-amber">{{ $myTasks->count() }}</span>
                </div>
                <div class="mt-4 space-y-3">
                    @forelse ($myTasks as $document)
                        <a href="{{ route('documents.show', $document) }}" class="block rounded-md border border-slate-200 px-4 py-3 hover:bg-slate-50">
                            <div class="flex items-start justify-between gap-3">
                                <div>
                                    <div class="font-medium text-slate-900">{{ $document->name }}</div>
                                    <div class="mt-1 text-xs text-slate-500">{{ $document->registration_number }}</div>
                                    <div class="mt-2 text-xs text-slate-600">{{ $document->current_step?->title }}</div>
                                </div>
                                <span class="{{ $badgeClass($document->status) }}">{{ $document->status_label }}</span>
                            </div>
                        </a>
                    @empty
                        <div class="text-sm text-slate-500">У вас нет активных этапов в маршрутах.</div>
                    @endforelse
                </div>
            </section>
        </div>

        <div class="space-y-6">
            <section class="panel p-5">
                <h2 class="text-base font-semibold text-slate-900">Статусы</h2>
                <div class="mt-4 space-y-3">
                    @foreach ($statusSummary as $item)
                        <div class="flex items-center justify-between">
                            <span class="text-sm text-slate-600">{{ $item['label'] }}</span>
                            <span class="{{ $badgeClass($item['status']) }}">{{ $item['count'] }}</span>
                        </div>
                    @endforeach
                </div>
            </section>

            <section class="panel p-5">
                <h2 class="text-base font-semibold text-slate-900">Доработка автора</h2>
                <div class="mt-4 space-y-3">
                    @forelse ($needsRevision as $document)
                        <a href="{{ route('documents.show', $document) }}" class="block rounded-md border border-rose-200 bg-rose-50 px-4 py-3 hover:bg-rose-100/70">
                            <div class="font-medium text-slate-900">{{ $document->name }}</div>
                            <div class="mt-1 text-xs text-rose-700">{{ $document->rejection_reason }}</div>
                        </a>
                    @empty
                        <div class="text-sm text-slate-500">Документов на доработке у вас нет.</div>
                    @endforelse
                </div>
            </section>

            <section class="panel p-5">
                <h2 class="text-base font-semibold text-slate-900">{{ auth()->user()->canSeeAllDocuments() ? 'Контур предприятия' : 'Мои отделы' }}</h2>
                <div class="mt-4 space-y-3">
                    @if ($adminSummary)
                        <div class="flex items-center justify-between">
                            <span class="text-sm text-slate-600">Пользователи</span>
                            <span class="badge-blue">{{ $adminSummary['users'] }}</span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-sm text-slate-600">Отделы</span>
                            <span class="badge-blue">{{ $adminSummary['departments'] }}</span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-sm text-slate-600">На согласовании</span>
                            <span class="badge-amber">{{ $adminSummary['approvalLoad'] }}</span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-sm text-slate-600">Очередь канцелярии</span>
                            <span class="badge-amber">{{ $adminSummary['officeQueue'] }}</span>
                        </div>
                    @else
                        @forelse ($departments as $department)
                            <div class="flex items-center justify-between">
                                <span class="text-sm text-slate-600">{{ $department->name }}</span>
                                @if ($department->code)
                                    <span class="badge-blue">{{ $department->code }}</span>
                                @endif
                            </div>
                        @empty
                            <div class="text-sm text-slate-500">Отделы не назначены.</div>
                        @endforelse
                    @endif
                </div>
            </section>
        </div>
    </div>
@endsection

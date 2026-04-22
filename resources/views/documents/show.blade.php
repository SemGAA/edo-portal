@extends('layouts.system')

@section('title', $document->name)
@section('page-title', 'Карточка документа')
@section('eyebrow', $document->registration_number)

@php
    $badgeClass = match ($document->status) {
        'draft' => 'badge-slate',
        'in_review' => 'badge-amber',
        'approved', 'archived' => 'badge-emerald',
        'rejected' => 'badge-rose',
        default => 'badge-blue',
    };

    $stepBadgeClass = function (string $status): string {
        return match ($status) {
            \App\Models\DocumentWorkflowStep::STATUS_IN_PROGRESS => 'badge-amber',
            \App\Models\DocumentWorkflowStep::STATUS_APPROVED => 'badge-emerald',
            \App\Models\DocumentWorkflowStep::STATUS_REJECTED => 'badge-rose',
            \App\Models\DocumentWorkflowStep::STATUS_SKIPPED => 'badge-slate',
            default => 'badge-blue',
        };
    };

    $workflowRounds = $document->workflowSteps->groupBy('round')->sortKeysDesc();
@endphp

@section('page-actions')
    <a href="{{ route('documents.index') }}" class="btn-secondary">К журналу</a>
    @if ($document->isEditableBy(auth()->user()))
        <a href="{{ route('documents.edit', $document) }}" class="btn-secondary">Редактировать</a>
    @endif
@endsection

@section('content')
    <div class="grid gap-6 xl:grid-cols-[2fr,1fr]">
        <div class="space-y-6">
            <section class="panel">
                <div class="panel-header">
                    <div>
                        <div class="flex flex-wrap items-center gap-3">
                            <h2 class="text-lg font-semibold text-slate-900">{{ $document->name }}</h2>
                            <span class="{{ $badgeClass }}">{{ $document->status_label }}</span>
                        </div>
                        <p class="mt-2 text-sm text-slate-500">{{ $document->summary ?: 'Краткое содержание не заполнено.' }}</p>
                    </div>
                    @if ($document->file)
                        <a href="{{ url('files/' . $document->file) }}" download class="btn-secondary">Скачать файл</a>
                    @endif
                </div>

                <div class="grid gap-5 px-5 py-5 md:grid-cols-2">
                    <div>
                        <div class="text-xs font-semibold uppercase tracking-wide text-slate-400">Тип документа</div>
                        <div class="mt-1 text-sm text-slate-800">{{ $document->type_label }}</div>
                    </div>
                    <div>
                        <div class="text-xs font-semibold uppercase tracking-wide text-slate-400">Приоритет</div>
                        <div class="mt-1 text-sm text-slate-800">{{ $document->priority_label }}</div>
                    </div>
                    <div>
                        <div class="text-xs font-semibold uppercase tracking-wide text-slate-400">Отдел</div>
                        <div class="mt-1 text-sm text-slate-800">{{ $document->category?->name ?: 'Не указан' }}</div>
                    </div>
                    <div>
                        <div class="text-xs font-semibold uppercase tracking-wide text-slate-400">Текущий этап</div>
                        <div class="mt-1 text-sm text-slate-800">{{ $document->current_step_label }}</div>
                        @if ($document->current_step)
                            <div class="mt-1 text-xs text-slate-500">Исполнитель: {{ $document->current_step->assignee_name }}</div>
                        @endif
                    </div>
                    <div>
                        <div class="text-xs font-semibold uppercase tracking-wide text-slate-400">Автор</div>
                        <div class="mt-1 text-sm text-slate-800">{{ $document->author?->name ?: 'Не указан' }}</div>
                    </div>
                    <div>
                        <div class="text-xs font-semibold uppercase tracking-wide text-slate-400">Дополнительный согласующий</div>
                        <div class="mt-1 text-sm text-slate-800">{{ $document->approver?->name ?: 'Не назначен' }}</div>
                    </div>
                    <div>
                        <div class="text-xs font-semibold uppercase tracking-wide text-slate-400">Срок исполнения</div>
                        <div class="mt-1 text-sm text-slate-800">{{ optional($document->due_at)->format('d.m.Y') ?: 'Без срока' }}</div>
                    </div>
                    <div>
                        <div class="text-xs font-semibold uppercase tracking-wide text-slate-400">Контрагент / адресат</div>
                        <div class="mt-1 text-sm text-slate-800">{{ $document->external_partner ?: 'Не указан' }}</div>
                    </div>
                    <div>
                        <div class="text-xs font-semibold uppercase tracking-wide text-slate-400">Отправлен на маршрут</div>
                        <div class="mt-1 text-sm text-slate-800">{{ optional($document->submitted_at)->format('d.m.Y H:i') ?: 'Еще не отправлен' }}</div>
                    </div>
                    <div>
                        <div class="text-xs font-semibold uppercase tracking-wide text-slate-400">Согласован / архив</div>
                        <div class="mt-1 text-sm text-slate-800">
                            {{ optional($document->approved_at)->format('d.m.Y H:i') ?: 'Нет даты согласования' }}
                            @if ($document->archived_at)
                                <span class="mt-1 block text-xs text-slate-500">Архив: {{ $document->archived_at->format('d.m.Y H:i') }}</span>
                            @endif
                        </div>
                    </div>
                </div>

                @if ($document->rejection_reason)
                    <div class="border-t border-slate-200 px-5 py-5">
                        <div class="rounded-lg border border-rose-200 bg-rose-50 px-4 py-4">
                            <div class="text-sm font-medium text-rose-700">Причина возврата</div>
                            <div class="mt-2 text-sm text-rose-700">{{ $document->rejection_reason }}</div>
                        </div>
                    </div>
                @endif
            </section>

            <section class="panel overflow-hidden">
                <div class="panel-header">
                    <div>
                        <h2 class="text-base font-semibold text-slate-900">Маршрут согласования</h2>
                        <p class="text-sm text-slate-500">Все этапы по раундам отправки, включая комментарии и исполнителей.</p>
                    </div>
                </div>
                <div class="space-y-6 px-5 py-5">
                    @forelse ($workflowRounds as $round => $steps)
                        <div>
                            <div class="mb-3 flex items-center justify-between">
                                <h3 class="text-sm font-semibold text-slate-900">Раунд {{ $round }}</h3>
                                <span class="badge-slate">{{ $steps->count() }} этапа</span>
                            </div>
                            <div class="space-y-3">
                                @foreach ($steps as $step)
                                    <div class="rounded-lg border border-slate-200 px-4 py-4">
                                        <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                                            <div>
                                                <div class="flex items-center gap-2">
                                                    <span class="text-sm font-semibold text-slate-900">{{ $step->sequence }}. {{ $step->title }}</span>
                                                    <span class="{{ $stepBadgeClass($step->status) }}">{{ $step->status_label }}</span>
                                                </div>
                                                <div class="mt-2 text-sm text-slate-600">
                                                    {{ $step->assignee_name }}
                                                    @if ($step->department)
                                                        <span class="text-slate-400">· {{ $step->department->name }}</span>
                                                    @endif
                                                </div>
                                                @if ($step->comment)
                                                    <div class="mt-3 rounded-md bg-slate-50 px-3 py-3 text-sm text-slate-600">{{ $step->comment }}</div>
                                                @endif
                                            </div>
                                            <div class="text-xs text-slate-500 sm:text-right">
                                                <div>Старт: {{ optional($step->started_at)->format('d.m.Y H:i') ?: '—' }}</div>
                                                <div class="mt-1">Решение: {{ optional($step->acted_at)->format('d.m.Y H:i') ?: '—' }}</div>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @empty
                        <div class="text-sm text-slate-500">Маршрут еще не запускался.</div>
                    @endforelse
                </div>
            </section>

            <section class="panel overflow-hidden">
                <div class="panel-header">
                    <div>
                        <h2 class="text-base font-semibold text-slate-900">Журнал действий</h2>
                        <p class="text-sm text-slate-500">История создания, отправки, согласования, возвратов и архивирования.</p>
                    </div>
                </div>
                <div class="divide-y divide-slate-200">
                    @forelse ($document->actionLogs as $log)
                        <div class="px-5 py-4">
                            <div class="flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between">
                                <div>
                                    <div class="text-sm font-medium text-slate-900">{{ $log->title }}</div>
                                    <div class="mt-1 text-xs text-slate-500">
                                        {{ $log->actor_name }}
                                        @if ($log->workflowStep)
                                            <span class="text-slate-400">· {{ $log->workflowStep->title }}</span>
                                        @endif
                                    </div>
                                    @if ($log->description)
                                        <div class="mt-2 text-sm text-slate-600">{{ $log->description }}</div>
                                    @endif
                                </div>
                                <div class="text-xs text-slate-500">{{ $log->created_at->format('d.m.Y H:i') }}</div>
                            </div>
                        </div>
                    @empty
                        <div class="px-5 py-10 text-sm text-slate-500">Журнал пока пуст.</div>
                    @endforelse
                </div>
            </section>
        </div>

        <div class="space-y-6">
            <section class="panel p-5">
                <h2 class="text-base font-semibold text-slate-900">Действия</h2>
                <div class="mt-4 flex flex-col gap-3">
                    @if ($document->canBeSubmittedBy(auth()->user()))
                        <form method="POST" action="{{ route('documents.submit', $document) }}">
                            @csrf
                            <button type="submit" class="btn-primary w-full">Отправить на согласование</button>
                        </form>
                    @endif

                    @if ($document->canBeApprovedBy(auth()->user()))
                        <form method="POST" action="{{ route('documents.approve', $document) }}" class="space-y-3">
                            @csrf
                            <textarea name="comment" rows="3" class="block w-full rounded-md border-slate-300 shadow-sm focus:border-slate-400 focus:ring-slate-400" placeholder="Комментарий к согласованию (необязательно)"></textarea>
                            <button type="submit" class="btn-primary w-full">Подтвердить этап</button>
                        </form>

                        <form method="POST" action="{{ route('documents.reject', $document) }}" class="space-y-3">
                            @csrf
                            <textarea name="comment" rows="3" class="block w-full rounded-md border-slate-300 shadow-sm focus:border-slate-400 focus:ring-slate-400" placeholder="Что нужно доработать"></textarea>
                            <button type="submit" class="btn-danger w-full">Вернуть на доработку</button>
                        </form>
                    @endif

                    @if ($document->canBeArchivedBy(auth()->user()))
                        <form method="POST" action="{{ route('documents.archive', $document) }}" class="space-y-3">
                            @csrf
                            <textarea name="comment" rows="2" class="block w-full rounded-md border-slate-300 shadow-sm focus:border-slate-400 focus:ring-slate-400" placeholder="Комментарий к архивированию (необязательно)"></textarea>
                            <button type="submit" class="btn-secondary w-full">Передать в архив</button>
                        </form>
                    @endif

                    @if ($document->isEditableBy(auth()->user()) || auth()->user()->isAdmin())
                        <form method="POST" action="{{ route('documents.destroy', $document) }}">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn-danger w-full" onclick="return confirm('Удалить документ?')">Удалить документ</button>
                        </form>
                    @endif
                </div>
            </section>

            <section class="panel p-5">
                <h2 class="text-base font-semibold text-slate-900">Рабочая памятка</h2>
                <div class="mt-4 space-y-4 text-sm text-slate-600">
                    <div>
                        <div class="font-medium text-slate-800">1. Подготовка</div>
                        <div class="mt-1">Автор оформляет карточку, загружает файл и заполняет реквизиты документа.</div>
                    </div>
                    <div>
                        <div class="font-medium text-slate-800">2. Маршрут</div>
                        <div class="mt-1">Система автоматически строит цепочку из руководителя отдела, согласующего и канцелярии.</div>
                    </div>
                    <div>
                        <div class="font-medium text-slate-800">3. Решение</div>
                        <div class="mt-1">На любом этапе документ можно согласовать или вернуть на доработку с комментарием.</div>
                    </div>
                    <div>
                        <div class="font-medium text-slate-800">4. Архив</div>
                        <div class="mt-1">После завершения маршрута карточка передается в электронный архив с записью в журнале.</div>
                    </div>
                </div>
            </section>
        </div>
    </div>
@endsection

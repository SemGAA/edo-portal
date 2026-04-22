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

<section class="panel overflow-hidden">
    <div class="panel-header">
        <div>
            <h2 class="text-base font-semibold text-slate-900">Поиск и фильтры</h2>
            <p class="text-sm text-slate-500">
                {{ $activeDepartmentName }} · фильтрация обновляет журнал без перезагрузки страницы.
            </p>
        </div>

        <div class="grid w-full gap-3 sm:max-w-5xl sm:grid-cols-2 xl:grid-cols-5">
            <select wire:model="categoryId" class="block rounded-md border-slate-300 shadow-sm focus:border-slate-400 focus:ring-slate-400">
                <option value="">Все отделы</option>
                @foreach ($categories as $category)
                    <option value="{{ $category->id }}">{{ $category->name }}</option>
                @endforeach
            </select>

            <select wire:model="status" class="block rounded-md border-slate-300 shadow-sm focus:border-slate-400 focus:ring-slate-400">
                <option value="">Все статусы</option>
                @foreach (\App\Models\Document::STATUS_LABELS as $status => $label)
                    <option value="{{ $status }}">{{ $label }}</option>
                @endforeach
            </select>

            <input
                type="text"
                wire:model.debounce.350ms="search"
                class="block rounded-md border-slate-300 shadow-sm focus:border-slate-400 focus:ring-slate-400 xl:col-span-2"
                placeholder="Номер, название, контрагент"
            >

            <div class="flex items-center gap-3">
                <label class="inline-flex flex-1 items-center gap-2 rounded-md border border-slate-200 px-3 py-2 text-sm text-slate-600">
                    <input type="checkbox" wire:model="onlyTasks" class="rounded border-slate-300 text-slate-900 shadow-sm focus:ring-slate-400">
                    Только мои этапы
                </label>
                <button type="button" wire:click="clearFilters" class="btn-secondary whitespace-nowrap">Сбросить</button>
            </div>
        </div>
    </div>

    <div class="relative">
        <div wire:loading.delay class="absolute inset-0 z-10 bg-white/70 text-sm font-medium text-slate-600 backdrop-blur-sm">
            <div class="flex h-full items-center justify-center">
                Обновляем журнал...
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-sm">
                <thead class="bg-slate-50 text-left text-slate-500">
                    <tr>
                        <th class="px-5 py-3 font-medium">Номер</th>
                        <th class="px-5 py-3 font-medium">Документ</th>
                        <th class="px-5 py-3 font-medium">Отдел</th>
                        <th class="px-5 py-3 font-medium">Автор</th>
                        <th class="px-5 py-3 font-medium">Текущий этап</th>
                        <th class="px-5 py-3 font-medium">Статус</th>
                        <th class="px-5 py-3 font-medium">Срок</th>
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
                                <div class="mt-1 flex flex-wrap gap-2">
                                    <span class="badge-blue">{{ $document->type_label }}</span>
                                    <span class="badge-slate">{{ $document->priority_label }}</span>
                                    @if ($document->visibility)
                                        <span class="badge-slate">Доступен отделу</span>
                                    @endif
                                </div>
                            </td>
                            <td class="px-5 py-4 text-slate-600">{{ $document->category?->name }}</td>
                            <td class="px-5 py-4 text-slate-600">{{ $document->author?->name ?: 'Не указан' }}</td>
                            <td class="px-5 py-4">
                                <div class="font-medium text-slate-800">{{ $document->current_step_label }}</div>
                                <div class="mt-1 text-xs text-slate-500">
                                    {{ $document->current_step?->assignee_name ?? 'Нет активного исполнителя' }}
                                </div>
                            </td>
                            <td class="px-5 py-4">
                                <span class="{{ $badgeClass($document->status) }}">{{ $document->status_label }}</span>
                            </td>
                            <td class="px-5 py-4 text-slate-600">{{ optional($document->due_at)->format('d.m.Y') ?: 'Без срока' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-5 py-14 text-center text-slate-500">
                                Документы по текущему запросу не найдены.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="border-t border-slate-200 px-5 py-4">
        {{ $documents->links() }}
    </div>
</section>

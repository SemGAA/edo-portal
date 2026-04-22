@php
    $document = $document ?? null;
@endphp

<div class="grid gap-5 lg:grid-cols-2">
    <div class="lg:col-span-2">
        <x-input-label for="name" :value="'Название документа'" />
        <x-text-input id="name" name="name" type="text" class="mt-1 block w-full" :value="old('name', $document?->name)" required />
    </div>

    <div>
        <x-input-label for="document_type" :value="'Тип документа'" />
        <select id="document_type" name="document_type" class="mt-1 block w-full rounded-md border-slate-300 shadow-sm focus:border-slate-400 focus:ring-slate-400">
            @foreach (\App\Models\Document::TYPE_LABELS as $value => $label)
                <option value="{{ $value }}" @selected(old('document_type', $document?->document_type ?? 'internal') === $value)>{{ $label }}</option>
            @endforeach
        </select>
    </div>

    <div>
        <x-input-label for="priority" :value="'Приоритет'" />
        <select id="priority" name="priority" class="mt-1 block w-full rounded-md border-slate-300 shadow-sm focus:border-slate-400 focus:ring-slate-400">
            @foreach (\App\Models\Document::PRIORITY_LABELS as $value => $label)
                <option value="{{ $value }}" @selected(old('priority', $document?->priority ?? 'normal') === $value)>{{ $label }}</option>
            @endforeach
        </select>
    </div>

    <div>
        <x-input-label for="category_id" :value="'Отдел'" />
        <select id="category_id" name="category_id" class="mt-1 block w-full rounded-md border-slate-300 shadow-sm focus:border-slate-400 focus:ring-slate-400">
            @foreach ($categories as $category)
                <option value="{{ $category->id }}" @selected((int) old('category_id', $document?->category_id) === $category->id)>{{ $category->name }}</option>
            @endforeach
        </select>
    </div>

    <div>
        <x-input-label for="approver_id" :value="'Дополнительный согласующий'" />
        <select id="approver_id" name="approver_id" class="mt-1 block w-full rounded-md border-slate-300 shadow-sm focus:border-slate-400 focus:ring-slate-400">
            <option value="">Не назначен</option>
            @foreach ($approvers as $approver)
                <option value="{{ $approver->id }}" @selected((int) old('approver_id', $document?->approver_id) === $approver->id)>
                    {{ $approver->name }}{{ $approver->position ? ' - ' . $approver->position : '' }}
                </option>
            @endforeach
        </select>
        <p class="mt-2 text-xs text-slate-500">Руководитель отдела и канцелярия будут добавлены в маршрут автоматически.</p>
    </div>

    <div>
        <x-input-label for="external_partner" :value="'Контрагент / адресат'" />
        <x-text-input id="external_partner" name="external_partner" type="text" class="mt-1 block w-full" :value="old('external_partner', $document?->external_partner)" />
    </div>

    <div>
        <x-input-label for="due_at" :value="'Срок исполнения'" />
        <x-text-input id="due_at" name="due_at" type="date" class="mt-1 block w-full" :value="old('due_at', optional($document?->due_at)->format('Y-m-d'))" />
    </div>

    <div class="lg:col-span-2">
        <x-input-label for="summary" :value="'Краткое содержание'" />
        <textarea id="summary" name="summary" rows="4" class="mt-1 block w-full rounded-md border-slate-300 shadow-sm focus:border-slate-400 focus:ring-slate-400">{{ old('summary', $document?->summary) }}</textarea>
    </div>

    <div class="lg:col-span-2">
        <label for="dropzone-file" class="flex cursor-pointer flex-col items-center justify-center rounded-lg border border-dashed border-slate-300 bg-slate-50 px-6 py-8 text-center transition hover:border-slate-400 hover:bg-slate-100">
            <div id="upload-icon" class="mb-3 text-slate-400">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="h-10 w-10">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 16.5V8.25m0 0l-3 3m3-3l3 3M3.75 15a4.5 4.5 0 004.5 4.5h7.5a4.5 4.5 0 001.102-8.863 3.75 3.75 0 00-7.42-1.989A4.501 4.501 0 003.75 15z" />
                </svg>
            </div>
            <div class="text-sm font-medium text-slate-700">
                {{ $document ? 'Заменить файл документа' : 'Загрузить файл документа' }}
            </div>
            <div id="file-name" class="mt-2 text-sm text-slate-500">
                @if ($document?->file)
                    Текущий файл: <span class="font-medium text-slate-800">{{ $document->file }}</span>
                @else
                    PDF, DOCX, XLSX, CSV, PPTX или TXT
                @endif
            </div>
            <input id="dropzone-file" name="file" type="file" class="hidden" {{ $document ? '' : 'required' }}>
        </label>
    </div>

    <div class="lg:col-span-2">
        <label class="inline-flex items-center gap-3 rounded-md border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-700">
            <input type="checkbox" name="visibility" value="1" class="rounded border-slate-300 text-slate-900 shadow-sm focus:ring-slate-400" @checked(old('visibility', $document?->visibility ?? true))>
            Показывать документ сотрудникам назначенного отдела
        </label>
    </div>
</div>

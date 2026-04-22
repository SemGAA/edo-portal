@php
    $user = $user ?? null;
    $selectedCategories = collect(old('categories', $user?->categories?->pluck('id')->all() ?? []))->map(fn ($value) => (int) $value)->all();
@endphp

<div class="grid gap-5 lg:grid-cols-2">
    <div>
        <x-input-label for="name" :value="'ФИО'" />
        <x-text-input id="name" name="name" type="text" class="mt-1 block w-full" :value="old('name', $user?->name)" required />
    </div>

    <div>
        <x-input-label for="email" :value="'Email'" />
        <x-text-input id="email" name="email" type="email" class="mt-1 block w-full" :value="old('email', $user?->email)" required />
    </div>

    <div>
        <x-input-label for="position" :value="'Должность'" />
        <x-text-input id="position" name="position" type="text" class="mt-1 block w-full" :value="old('position', $user?->position)" />
    </div>

    <div>
        <x-input-label for="phone" :value="'Телефон'" />
        <x-text-input id="phone" name="phone" type="text" class="mt-1 block w-full" :value="old('phone', $user?->phone)" />
    </div>

    <div>
        <x-input-label for="password" :value="$user ? 'Новый пароль' : 'Пароль'" />
        <x-text-input id="password" name="password" type="password" class="mt-1 block w-full" :required="!$user" />
    </div>

    <div>
        <x-input-label for="password_confirmation" :value="'Подтверждение пароля'" />
        <x-text-input id="password_confirmation" name="password_confirmation" type="password" class="mt-1 block w-full" :required="!$user" />
    </div>

    <div>
        <x-input-label for="role" :value="'Роль'" />
        <select id="role" name="role" class="mt-1 block w-full rounded-md border-slate-300 shadow-sm focus:border-slate-400 focus:ring-slate-400">
            @foreach (\App\Models\User::ROLE_LABELS as $value => $label)
                <option value="{{ $value }}" @selected((string) old('role', $user?->role ?? \App\Models\User::ROLE_EMPLOYEE) === (string) $value)>{{ $label }}</option>
            @endforeach
        </select>
    </div>

    <div class="lg:col-span-2">
        <x-input-label :value="'Отделы'" />
        <div class="mt-2 grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
            @foreach ($categories as $category)
                <label class="inline-flex items-center gap-3 rounded-md border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-700">
                    <input type="checkbox" name="categories[]" value="{{ $category->id }}" class="rounded border-slate-300 text-slate-900 shadow-sm focus:ring-slate-400" @checked(in_array($category->id, $selectedCategories, true))>
                    <span>{{ $category->name }}</span>
                    @if ($category->code)
                        <span class="badge-slate">{{ $category->code }}</span>
                    @endif
                </label>
            @endforeach
        </div>
    </div>
</div>

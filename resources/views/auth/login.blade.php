@section('title', 'Вход')
<x-guest-layout>
    <x-auth-session-status class="mb-4" :status="session('status')" />

    <form method="POST" action="{{ route('login') }}" class="space-y-5">
        @csrf

        <div>
            <x-input-label for="email" :value="'Email'" />
            <x-text-input id="email" class="mt-1 block w-full" type="email" name="email" :value="old('email')" required autofocus autocomplete="username" />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <div>
            <x-input-label for="password" :value="'Пароль'" />
            <x-text-input id="password" class="mt-1 block w-full" type="password" name="password" required autocomplete="current-password" />
            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <label for="remember_me" class="inline-flex items-center gap-2 text-sm text-slate-600">
            <input id="remember_me" type="checkbox" class="rounded border-slate-300 text-slate-900 shadow-sm focus:ring-slate-400" name="remember">
            <span>Запомнить вход</span>
        </label>

        <button type="submit" class="btn-primary w-full">
            Войти
        </button>
    </form>

    <div class="mt-6 rounded-md bg-slate-50 p-4 text-sm text-slate-600">
        <div class="font-medium text-slate-900">Демо-доступ</div>
        <div class="mt-2">Администратор: <span class="font-medium">admin@edo.local</span></div>
        <div>Пароль: <span class="font-medium">Admin12345</span></div>
    </div>
</x-guest-layout>

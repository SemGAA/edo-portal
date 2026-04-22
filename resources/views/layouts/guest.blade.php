<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Вход') - {{ config('app.name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-slate-100 text-slate-900">
    <div class="mx-auto flex min-h-screen max-w-6xl flex-col justify-center px-4 py-10 lg:flex-row lg:items-center lg:gap-12">
        <div class="max-w-xl lg:flex-1">
            <div class="inline-flex items-center gap-3 rounded-md bg-white px-4 py-3 shadow-sm">
                <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-slate-900 text-sm font-semibold text-white">
                    ЭДО
                </div>
                <div>
                    <div class="text-sm font-semibold">{{ config('app.name') }}</div>
                    <div class="text-xs text-slate-500">Документооборот предприятия</div>
                </div>
            </div>
            <h1 class="mt-8 max-w-xl text-4xl font-semibold tracking-tight text-slate-900">
                Все входящие, внутренние и договорные документы в одном рабочем контуре.
            </h1>
            <p class="mt-4 max-w-lg text-base text-slate-600">
                Отслеживайте статус согласования, сроки исполнения, ответственных сотрудников и архив прямо из браузера.
            </p>
            <div class="mt-8 grid gap-3 sm:grid-cols-2">
                <div class="panel p-4">
                    <div class="text-sm font-medium text-slate-900">Единый журнал</div>
                    <div class="mt-2 text-sm text-slate-500">Поиск по номеру, типу документа, отделу и контрагенту.</div>
                </div>
                <div class="panel p-4">
                    <div class="text-sm font-medium text-slate-900">Согласование</div>
                    <div class="mt-2 text-sm text-slate-500">Черновики, очередь на визирование, возврат на доработку и архив.</div>
                </div>
            </div>
        </div>

        <div class="mt-10 w-full max-w-md lg:mt-0">
            <div class="panel overflow-hidden">
                <div class="border-b border-slate-200 px-6 py-5">
                    <div class="text-lg font-semibold text-slate-900">Вход в систему</div>
                    <div class="mt-1 text-sm text-slate-500">Используйте учетную запись администратора или сотрудника.</div>
                </div>
                <div class="px-6 py-6">
                    {{ $slot }}
                </div>
            </div>
        </div>
    </div>
</body>
</html>

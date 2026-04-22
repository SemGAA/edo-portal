@extends('layouts.system')

@section('title', 'Профиль')
@section('page-title', 'Профиль')
@section('eyebrow', 'Личные настройки')

@section('content')
    <div class="space-y-6">
        <section class="panel p-5">
            @include('profile.partials.update-profile-information-form')
        </section>

        <section class="panel p-5">
            @include('profile.partials.update-password-form')
        </section>

        <section class="panel p-5">
            @include('profile.partials.delete-user-form')
        </section>
    </div>
@endsection

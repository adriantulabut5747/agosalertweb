@extends('layouts.app')
@section('title', 'Log in')
@section('main_class', 'auth')
@section('bare', '1')

{{-- The app's LoginScreen (lib/screens/login_screen.dart): >= 900 px,
     Tugak beside a 400 px form; narrower, Tugak above it. --}}
@section('content')
    <div class="auth-wrap">
        @include('partials.auth-hello')
        <div class="auth-form">
            <h1>Welcome back</h1>
            <p class="lede">Your reports, your votes, your barangay. Right where you left them.</p>
            <form method="POST" action="{{ route('login') }}">
                @csrf
                <div class="field"><i class="i" data-i="mail"></i>
                    <input type="email" name="email" value="{{ old('email') }}" placeholder="Email" autocomplete="email" required autofocus></div>
                <div class="field pw"><i class="i" data-i="lock"></i>
                    <input type="password" name="password" placeholder="Password" autocomplete="current-password" required>
                    <button type="button" class="eye" aria-label="Show password"><i class="i" data-i="eye"></i></button></div>
                <div class="forgot"><a href="#" onclick="document.getElementById('forgot').showModal();return false">Forgot password?</a></div>
                <button class="btn blue wide" type="submit">Log in</button>
            </form>
            <p class="switch">New here? <a href="{{ route('register') }}">Create a free account</a></p>
            <a class="guest" href="{{ route('home') }}">Continue as guest</a>
        </div>
    </div>

    <dialog class="sheet" id="forgot">
        <form method="POST" action="{{ route('password.forgot') }}">
            @csrf
            <h3>Reset your password</h3>
            <p>We'll email you a link to set a new one.</p>
            <div class="field" style="text-align:left"><i class="i" data-i="mail"></i><input type="email" name="email" placeholder="Email" required></div>
            <button class="btn blue wide" type="submit" style="margin-top:14px">Send reset link</button>
            <button class="btn gray wide" type="button" onclick="this.closest('dialog').close()">Cancel</button>
        </form>
    </dialog>
@endsection

@include('partials.auth-script')

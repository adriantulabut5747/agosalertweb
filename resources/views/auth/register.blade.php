@extends('layouts.app')
@section('title', 'Create account')
@section('main_class', 'auth')
@section('bare', '1')

{{-- The app's LoginScreen in sign-up mode: name, email, password, barangay. --}}
@section('content')
    <div class="auth-wrap">
        @include('partials.auth-hello')
        <div class="auth-form">
            <h1>Join the watch</h1>
            <p class="lede">Free, and it takes a minute. Post floods, vote on what's real, and put your barangay first.</p>
            <form method="POST" action="{{ route('register') }}">
                @csrf
                <div class="field"><i class="i" data-i="person"></i>
                    <input type="text" name="full_name" value="{{ old('full_name') }}" placeholder="Full name" autocomplete="name" required></div>
                <div class="field"><i class="i" data-i="mail"></i>
                    <input type="email" name="email" value="{{ old('email') }}" placeholder="Email" autocomplete="email" required></div>
                <div class="field pw"><i class="i" data-i="lock"></i>
                    <input type="password" name="password" placeholder="Password (at least 6 characters)" autocomplete="new-password" minlength="6" required>
                    <button type="button" class="eye" aria-label="Show password"><i class="i" data-i="eye"></i></button></div>
                <div class="field"><i class="i" data-i="placemark"></i>
                    <select name="barangay" required>
                        <option value="">Your barangay</option>
                        @foreach ($barangays as $b)<option @selected(old('barangay') === $b)>{{ $b }}</option>@endforeach
                    </select></div>
                <p class="notice">Your name, email and barangay make your account. What you report (the spot, photo and note) is public with your name
                    for 14 days. By creating an account you agree to the Terms.
                    <a href="{{ asset('terms.html') }}">Terms of Use</a> · <a href="{{ asset('privacy.html') }}">Privacy Policy</a></p>
                <button class="btn blue wide" type="submit">Create my account</button>
            </form>
            <p class="switch">Already in? <a href="{{ route('login') }}">Log in</a></p>
            <a class="guest" href="{{ route('home') }}">Continue as guest</a>
        </div>
    </div>
@endsection

@include('partials.auth-script')

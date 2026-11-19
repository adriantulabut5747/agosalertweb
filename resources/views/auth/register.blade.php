@extends('layouts.app')
@section('title', 'Create account')
@section('main_class', 'narrow')

@section('content')
    <div style="max-width:420px;margin:0 auto;text-align:center">
        <h1 class="page-title">Join the watch</h1>
        <p class="page-sub" style="font-size:17px">Free, and it takes a minute. Post floods, vote on what's real, and put your barangay first.</p>

        <form method="POST" action="{{ route('register') }}" style="text-align:left">
            @csrf
            <div class="field"><i class="i" data-i="person_fill"></i>
                <input type="text" name="full_name" value="{{ old('full_name') }}" placeholder="Full name" autocomplete="name" required></div>
            <div class="field" style="margin-top:10px"><i class="i" data-i="bell_fill"></i>
                <input type="email" name="email" value="{{ old('email') }}" placeholder="Email" autocomplete="email" required></div>
            <div class="field" style="margin-top:10px"><i class="i" data-i="shield_fill"></i>
                <input type="password" name="password" placeholder="Password (at least 6 characters)" autocomplete="new-password" required></div>
            <div class="field" style="margin-top:10px"><i class="i" data-i="shield_fill"></i>
                <input type="password" name="password_confirmation" placeholder="Password again" autocomplete="new-password" required></div>
            <select name="barangay" required style="margin-top:10px">
                <option value="">Choose your barangay</option>
                @foreach ($barangays as $b)<option @selected(old('barangay') === $b)>{{ $b }}</option>@endforeach
            </select>
            @if ($errors->any())<ul class="errors">@foreach ($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>@endif
            <button class="btn blue wide" type="submit" style="margin-top:18px">Create my account</button>
            <p class="small muted" style="text-align:center;margin:12px 0 0">
                Your name, email and barangay make your account. What you report is public with your name for 14 days.
                By creating an account you agree to the
                <a href="https://adriantulabut5747.github.io/AgosAlert/terms.html">Terms</a> ·
                <a href="https://adriantulabut5747.github.io/AgosAlert/privacy.html">Privacy Policy</a>
            </p>
        </form>
        <p style="margin-top:18px" class="muted">Already in? <a href="{{ route('login') }}">Log in</a></p>
    </div>
@endsection

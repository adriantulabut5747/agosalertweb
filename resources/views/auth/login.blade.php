@extends('layouts.app')
@section('title', 'Log in')
@section('main_class', 'narrow')

@section('content')
    <div style="max-width:420px;margin:0 auto;text-align:center">
        {{-- A mini landing hero: Tugak tipping his hat in a kBlue pool. --}}
        <div style="position:relative;height:190px">
            <div style="position:absolute;left:50%;bottom:6px;width:220px;height:46px;transform:translateX(-50%);border-radius:50%;background:radial-gradient(closest-side,rgba(10,132,255,.55),transparent)"></div>
            <img src="{{ asset('img/tugak/tip.webp') }}" alt="Tugak tipping his hat" style="position:relative;height:190px">
        </div>
        <h1 class="page-title">Welcome back</h1>
        <p class="page-sub" style="font-size:17px">Your reports, your votes, your barangay. Right where you left them.</p>

        <form method="POST" action="{{ route('login') }}" style="text-align:left">
            @csrf
            <div class="field"><i class="i" data-i="person_fill"></i>
                <input type="email" name="email" value="{{ old('email') }}" placeholder="Email" autocomplete="email" required autofocus></div>
            <div class="field" style="margin-top:10px"><i class="i" data-i="shield_fill"></i>
                <input type="password" name="password" placeholder="Password" autocomplete="current-password" required></div>
            <div style="text-align:right;margin-top:8px">
                <a class="small" href="#" onclick="document.getElementById('forgot').showModal();return false">Forgot password?</a>
            </div>
            @if ($errors->any())<ul class="errors">@foreach ($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>@endif
            <button class="btn blue wide" type="submit" style="margin-top:16px">Log in</button>
        </form>
        <p style="margin-top:18px" class="muted">New here? <a href="{{ route('register') }}">Create a free account</a></p>
        <a href="{{ route('home') }}" class="muted" style="text-decoration:underline">Continue as guest</a>
    </div>

    <dialog id="forgot" style="width:min(420px,calc(100vw - 32px));border:1px solid var(--edge-lit);border-radius:28px;background:#12131b;color:var(--text);padding:24px">
        <form method="POST" action="{{ route('password.forgot') }}">
            @csrf
            <h3 class="page-title" style="font-size:20px;text-align:center">Reset your password</h3>
            <p class="small muted" style="text-align:center">We'll email you a link to set a new one.</p>
            <div class="field"><i class="i" data-i="person_fill"></i><input type="email" name="email" placeholder="Email" required></div>
            <button class="btn blue wide" type="submit" style="margin-top:14px">Send reset link</button>
            <button class="btn gray wide" type="button" style="margin-top:8px" onclick="this.closest('dialog').close()">Cancel</button>
        </form>
    </dialog>
@endsection

@extends('layouts.app')
@section('title', 'Keep your account?')
@section('main_class', 'narrow')
@section('bare', '1')

{{-- The app's askKeepAccount sheet (account.dart), as a page: shown right
     after logging in to an account waiting to be deleted. --}}
@section('content')
    <div class="card sheetcard">
        <span class="badge-lg blue"><i class="i" data-i="person_fill"></i></span>
        <h3>Keep your account?</h3>
        <p>You asked to delete it. It goes for good on {{ $when }}, with your reports, photos and votes, unless you keep it now.</p>
        <form method="POST" action="{{ route('account.keep') }}">
            @csrf <button class="btn white wide" type="submit">Keep my account</button>
        </form>
        <form method="POST" action="{{ route('account.leave') }}">
            @csrf <button class="btn gray wide" type="submit">Log out</button>
        </form>
    </div>
@endsection

{{-- LoginScreen._hello in the app: Tugak tipping his hat in a kBlue pool.
     No bubble at rest; when something is wrong he says it in one (there's
     no red error line) and switches to the think pose. --}}
@php $line = session('tugak') ?? session('error') ?? ($errors->any() ? $errors->first() : null); @endphp
<div class="auth-hello">
    @if ($line)
        <div class="say" id="auth-say" role="alert"><div class="who">TUGAK</div><p>{{ $line }}</p></div>
    @endif
    <div class="pool"><img src="{{ asset('img/tugak/'.($line ? 'think' : 'tip').'.webp') }}" alt="Tugak"></div>
</div>

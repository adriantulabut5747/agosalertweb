@push('scripts')
    <script>
        // Show / hide the password (the eye in the app's field).
        document.querySelectorAll('.pw .eye').forEach(function (b) {
            b.addEventListener('click', function () {
                var input = b.parentNode.querySelector('input'), show = input.type === 'password';
                input.type = show ? 'text' : 'password';
                b.querySelector('i').dataset.i = show ? 'eye_slash' : 'eye';
                b.setAttribute('aria-label', show ? 'Hide password' : 'Show password');
            });
        });
        // Tugak's line: bring it into view (phones: it sits above the form).
        var say = document.getElementById('auth-say');
        if (say) say.scrollIntoView({ block: 'center' });
    </script>
@endpush

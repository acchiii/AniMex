@extends('layouts.app')

@push('scripts')
@if(config('services.recaptcha.enabled'))
<script src="https://www.google.com/recaptcha/api.js?render={{ config('services.recaptcha.site_key') }}" async defer></script>
<script>
function executeRegisterCaptcha(form) {
    grecaptcha.ready(function() {
        grecaptcha.execute('{{ config('services.recaptcha.site_key') }}', {action: 'register'}).then(function(token) {
            let hiddenInput = form.querySelector('input[name="g-recaptcha-response"]');
            if (!hiddenInput) {
                hiddenInput = document.createElement('input');
                hiddenInput.type = 'hidden';
                hiddenInput.name = 'g-recaptcha-response';
                form.appendChild(hiddenInput);
            }
            hiddenInput.value = token;
            form.submit();
        });
    });
}
</script>
@endif
@endpush

@section('content')
<div class="max-w-md mx-auto px-4 py-12">
    <div class="bg-gray-900 rounded-lg p-8 border border-gray-800">
        <h1 class="text-2xl font-bold text-white mb-6 text-center">Create Your Account</h1>

        @if(session('error'))
            <div class="bg-red-500/10 border border-red-500 text-red-500 px-4 py-3 rounded-lg mb-4">
                {{ session('error') }}
            </div>
        @endif

        <form method="POST" action="{{ route('register') }}" class="space-y-4" onsubmit="event.preventDefault(); executeRegisterCaptcha(this);">
            @csrf

            <div>
                <label for="name" class="block text-sm font-medium text-gray-300 mb-1">Name</label>
                <input type="text" name="name" id="name" required autofocus
                    class="w-full bg-gray-800 border border-gray-700 rounded-lg px-4 py-2 text-white focus:outline-none focus:ring-2 focus:ring-purple-500">
                @error('name')
                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="email" class="block text-sm font-medium text-gray-300 mb-1">Email</label>
                <input type="email" name="email" id="email" required
                    class="w-full bg-gray-800 border border-gray-700 rounded-lg px-4 py-2 text-white focus:outline-none focus:ring-2 focus:ring-purple-500">
                @error('email')
                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="password" class="block text-sm font-medium text-gray-300 mb-1">Password</label>
                <input type="password" name="password" id="password" required
                    class="w-full bg-gray-800 border border-gray-700 rounded-lg px-4 py-2 text-white focus:outline-none focus:ring-2 focus:ring-purple-500">
                @error('password')
                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="password_confirmation" class="block text-sm font-medium text-gray-300 mb-1">Confirm Password</label>
                <input type="password" name="password_confirmation" id="password_confirmation" required
                    class="w-full bg-gray-800 border border-gray-700 rounded-lg px-4 py-2 text-white focus:outline-none focus:ring-2 focus:ring-purple-500">
            </div>

            @error('g-recaptcha-response')
                <p class="text-red-500 text-sm">{{ $message }}</p>
            @enderror

            <button type="submit" class="w-full bg-purple-600 hover:bg-purple-700 text-white font-medium py-2 rounded-lg transition">
                Create Account
            </button>
        </form>

        <p class="text-gray-400 text-center mt-4">
            Already have an account?
            <a href="{{ route('login') }}" class="text-purple-500 hover:text-purple-400">Log in</a>
        </p>
    </div>
</div>
@endsection

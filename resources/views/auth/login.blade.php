@extends('layouts.app')

@push('scripts')
@if(config('services.recaptcha.enabled'))
<script src="https://www.google.com/recaptcha/api.js?render={{ config('services.recaptcha.site_key') }}" async defer></script>
<script>
function executeLoginCaptcha(form) {
    grecaptcha.ready(function() {
        grecaptcha.execute('{{ config('services.recaptcha.site_key') }}', {action: 'login'}).then(function(token) {
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
        <h1 class="text-2xl font-bold text-white mb-6 text-center">Welcome Back</h1>

        @if(session('error'))
            <div class="bg-red-500/10 border border-red-500 text-red-500 px-4 py-3 rounded-lg mb-4">
                {{ session('error') }}
            </div>
        @endif

        <form method="POST" action="{{ route('login') }}" class="space-y-4" @if(config('services.recaptcha.enabled')) onsubmit="event.preventDefault(); executeLoginCaptcha(this);" @endif>

            @csrf

            <div>
                <label for="email" class="block text-sm font-medium text-gray-300 mb-1">Email</label>
                <input type="email" name="email" id="email" required autofocus
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

            <div class="flex items-center justify-between">
                <label class="flex items-center">
                    <input type="checkbox" name="remember" class="rounded bg-gray-800 border-gray-700 text-purple-500 focus:ring-purple-500">
                    <span class="ml-2 text-sm text-gray-300">Remember me</span>
                </label>
            </div>

            @error('g-recaptcha-response')
                <p class="text-red-500 text-sm">{{ $message }}</p>
            @enderror

            <button type="submit" class="w-full bg-purple-600 hover:bg-purple-700 text-white font-medium py-2 rounded-lg transition">
                Log In
            </button>
        </form>

        <p class="text-gray-400 text-center mt-4">
            Don't have an account?
            <a href="{{ route('register') }}" class="text-purple-500 hover:text-purple-400">Sign up</a>
        </p>
    </div>
</div>
@endsection

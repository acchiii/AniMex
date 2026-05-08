@extends('layouts.app')

@section('content')
<div class="max-w-2xl mx-auto px-4 py-10">
    <div class="bg-white/80 dark:bg-[#0a0a0f]/80 backdrop-blur-lg border border-gray-200 dark:border-gray-800/60 rounded-2xl p-6">
        <div class="flex items-start justify-between gap-4 mb-6">
            <div>
                <h1 class="text-2xl font-bold">Profile</h1>
                <p class="text-sm text-gray-500 dark:text-gray-400">Update your details and verify your email.</p>
            </div>
        </div>

        @if(session('success'))
            <div class="mb-4 rounded-lg border border-green-500/30 bg-green-500/10 px-4 py-3 text-green-600 dark:text-green-400">
                {{ session('success') }}
            </div>
        @endif

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div class="space-y-4">
                <h2 class="font-semibold text-gray-900 dark:text-gray-100">Edit information</h2>

                <form method="POST" action="{{ route('profile.update') }}" class="space-y-4">
                    @csrf

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-200" for="name">Name</label>
                        <input
                            id="name"
                            name="name"
                            value="{{ old('name', $user->name) }}"
                            required
                            class="mt-1 w-full rounded-xl border-gray-300 dark:border-gray-700 bg-white/60 dark:bg-white/5 px-3 py-2 text-sm text-gray-900 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-purple-500/50"
                        >
                        @error('name')
                            <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-200" for="username">Username</label>
                        <input
                            id="username"
                            name="username"
                            value="{{ old('username', $user->username) }}"
                            class="mt-1 w-full rounded-xl border-gray-300 dark:border-gray-700 bg-white/60 dark:bg-white/5 px-3 py-2 text-sm text-gray-900 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-purple-500/50"
                        >
                        @error('username')
                            <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                        @enderror
                    </div>

                    <button
                        type="submit"
                        class="w-full md:w-auto bg-purple-600 hover:bg-purple-700 text-white font-medium px-4 py-2.5 rounded-xl transition shadow-sm shadow-purple-600/20"
                    >
                        Save changes
                    </button>
                </form>
            </div>

            <div class="space-y-4">
                <h2 class="font-semibold text-gray-900 dark:text-gray-100">Email verification</h2>

                <div class="rounded-xl border border-gray-200 dark:border-gray-800/60 bg-white/60 dark:bg-white/5 p-4">
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <p class="text-sm text-gray-600 dark:text-gray-300">Email</p>
                            <p class="font-medium">{{ $user->email }}</p>
                        </div>

                        @if($user->hasVerifiedEmail())
                            <span class="inline-flex items-center rounded-full bg-green-500/15 text-green-600 dark:text-green-400 px-3 py-1 text-xs font-semibold">Verified</span>
                        @else
                            <span class="inline-flex items-center rounded-full bg-amber-500/15 text-amber-600 dark:text-amber-400 px-3 py-1 text-xs font-semibold">Not verified</span>
                        @endif
                    </div>

                    @if(!$user->hasVerifiedEmail())
                        <form method="POST" action="{{ route('verification.resend') }}" class="mt-4">
                            @csrf
                            <button
                                type="submit"
                                class="w-full bg-gray-900 hover:bg-black text-white font-medium px-4 py-2.5 rounded-xl transition"
                            >
                                Resend verification email
                            </button>
                            <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">
                                If you don’t see it, check your spam folder.
                            </p>
                        </form>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection


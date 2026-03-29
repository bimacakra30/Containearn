@extends('layouts.master')

@section('content')
<div class="min-h-screen flex items-center justify-center px-4 py-12">

    <div class="pointer-events-none fixed inset-0 overflow-hidden -z-10" aria-hidden="true">
        <div class="absolute -top-32 -left-24 w-[520px] h-[520px] rounded-full bg-blue-100 opacity-60 blur-3xl"></div>
        <div class="absolute -top-20 right-0 w-[400px] h-[400px] rounded-full bg-amber-100 opacity-50 blur-3xl"></div>
        <div class="absolute bottom-0 left-1/2 -translate-x-1/2 w-[600px] h-[300px] rounded-full bg-indigo-50 opacity-40 blur-3xl"></div>
    </div>

    <div class="w-full max-w-md fade-in">

        <div class="glass rounded-3xl px-8 py-10 sm:px-10">

            <div class="flex flex-col items-center mb-8">
                <div class="w-14 h-14 rounded-2xl bg-gradient-to-br from-blue-500 to-indigo-600 flex items-center justify-center shadow-lg mb-4">
                    <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 5.25a3 3 0 013 3m3 0a6 6 0 01-7.029 5.912c-.563-.097-1.159.026-1.563.43L10.5 17.25H8.25v2.25H6v2.25H2.25v-2.818c0-.597.237-1.17.659-1.591l6.499-6.499c.404-.404.527-1 .43-1.563A6 6 0 1121.75 8.25z" />
                    </svg>
                </div>
                <h1 class="font-display text-2xl font-bold text-slate-800 tracking-tight">Forgot Password?</h1>
                <p class="text-sm text-slate-500 mt-1 text-center leading-relaxed max-w-xs">
                    No worries. Enter your email and we'll send you a reset link.
                </p>
            </div>

            @if (session('status'))
                <div class="mb-6 px-4 py-3 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-700 text-sm flex items-center gap-2">
                    <svg class="w-4 h-4 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.857-9.809a.75.75 0 00-1.214-.882l-3.483 4.79-1.88-1.88a.75.75 0 10-1.06 1.061l2.5 2.5a.75.75 0 001.137-.089l4-5.5z" clip-rule="evenodd" />
                    </svg>
                    {{ session('status') }}
                </div>
            @endif

            <form method="POST" action="{{ route('password.email') }}" x-data="{ submitting: false }" @submit="submitting = true">
                @csrf

                <div class="mb-6" x-data="{ focused: false }">
                    <label for="email"
                        class="block text-xs font-semibold uppercase tracking-widest mb-2 transition-colors duration-200"
                        :class="focused ? 'text-indigo-600' : 'text-slate-500'">
                        Email Address
                    </label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none">
                            <svg class="w-4 h-4 transition-colors duration-200"
                                :class="focused ? 'text-indigo-500' : 'text-slate-400'"
                                fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 01-2.25 2.25h-15a2.25 2.25 0 01-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25m19.5 0v.243a2.25 2.25 0 01-1.07 1.916l-7.5 4.615a2.25 2.25 0 01-2.36 0L3.32 8.91a2.25 2.25 0 01-1.07-1.916V6.75" />
                            </svg>
                        </div>
                        <input
                            id="email"
                            type="email"
                            name="email"
                            value="{{ old('email') }}"
                            required
                            autofocus
                            autocomplete="email"
                            @focus="focused = true"
                            @blur="focused = false"
                            placeholder="your@email.com"
                            class="w-full pl-10 pr-4 py-3 rounded-xl border text-sm text-slate-800 placeholder-slate-400 transition-all duration-200 outline-none
                                {{ $errors->get('email') ? 'border-rose-400 bg-rose-50 focus:border-rose-500 focus:ring-2 focus:ring-rose-200' : 'border-slate-200 bg-white/70 focus:border-indigo-400 focus:ring-2 focus:ring-indigo-100' }}"
                        />
                    </div>
                    @foreach ($errors->get('email') as $error)
                        <p class="mt-1.5 text-xs text-rose-500 flex items-center gap-1">
                            <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-8-5a.75.75 0 01.75.75v4.5a.75.75 0 01-1.5 0v-4.5A.75.75 0 0110 5zm0 10a1 1 0 100-2 1 1 0 000 2z" clip-rule="evenodd"/></svg>
                            {{ $error }}
                        </p>
                    @endforeach
                </div>

                <button
                    type="submit"
                    :disabled="submitting"
                    class="w-full py-3 px-6 rounded-xl font-semibold text-sm text-white
                        bg-gradient-to-r from-blue-500 via-indigo-500 to-indigo-600
                        hover:from-blue-600 hover:via-indigo-600 hover:to-indigo-700
                        shadow-md shadow-indigo-200
                        transition-all duration-200 active:scale-[0.98]
                        focus:outline-none focus:ring-2 focus:ring-indigo-400 focus:ring-offset-2
                        disabled:opacity-70 disabled:cursor-not-allowed"
                >
                    <span x-show="!submitting" class="flex items-center justify-center gap-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 12L3.269 3.126A59.768 59.768 0 0121.485 12 59.77 59.77 0 013.27 20.876L5.999 12zm0 0h7.5" />
                        </svg>
                        Send Reset Link
                    </span>
                    <span x-show="submitting" x-cloak class="flex items-center justify-center gap-2">
                        <svg class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                        </svg>
                        Sending...
                    </span>
                </button>

                <p class="text-center text-sm text-slate-500 mt-5">
                    Remember your password?
                    <a href="{{ route('login') }}"
                        class="text-indigo-600 hover:text-indigo-800 font-medium hover:underline underline-offset-2 transition-colors duration-150">
                        Back to Sign In
                    </a>
                </p>

            </form>
        </div>
    </div>
</div>
@endsection
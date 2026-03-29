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
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                    </svg>
                </div>
                <h1 class="font-display text-2xl font-bold text-slate-800 tracking-tight">Welcome Back</h1>
                <p class="text-sm text-slate-500 mt-1">Sign in to your platform account</p>
            </div>

            @if (session('status'))
                <div class="mb-5 px-4 py-3 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-700 text-sm flex items-center gap-2">
                    <svg class="w-4 h-4 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.857-9.809a.75.75 0 00-1.214-.882l-3.483 4.79-1.88-1.88a.75.75 0 10-1.06 1.061l2.5 2.5a.75.75 0 001.137-.089l4-5.5z" clip-rule="evenodd" />
                    </svg>
                    {{ session('status') }}
                </div>
            @endif

            <form method="POST" action="{{ route('login') }}" x-data="loginForm()" @submit="submitting = true">
                @csrf

                <div class="mb-5" x-data="{ focused: false }">
                    <label for="email"
                        class="block text-xs font-semibold uppercase tracking-widest mb-2 transition-colors duration-200"
                        :class="focused ? 'text-indigo-600' : 'text-slate-500'">
                        Email
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
                            autocomplete="username"
                            @focus="focused = true"
                            @blur="focused = false"
                            class="w-full pl-10 pr-4 py-3 rounded-xl border text-sm text-slate-800 placeholder-slate-400 transition-all duration-200 outline-none
                                {{ $errors->get('email') ? 'border-rose-400 bg-rose-50 focus:border-rose-500 focus:ring-2 focus:ring-rose-200' : 'border-slate-200 bg-white/70 focus:border-indigo-400 focus:ring-2 focus:ring-indigo-100' }}"
                            placeholder="name@email.com"
                        />
                    </div>
                    @foreach ($errors->get('email') as $error)
                        <p class="mt-1.5 text-xs text-rose-500 flex items-center gap-1">
                            <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-8-5a.75.75 0 01.75.75v4.5a.75.75 0 01-1.5 0v-4.5A.75.75 0 0110 5zm0 10a1 1 0 100-2 1 1 0 000 2z" clip-rule="evenodd"/></svg>
                            {{ $error }}
                        </p>
                    @endforeach
                </div>

                <div class="mb-5" x-data="{ focused: false, show: false }">
                    <label for="password"
                        class="block text-xs font-semibold uppercase tracking-widest mb-2 transition-colors duration-200"
                        :class="focused ? 'text-indigo-600' : 'text-slate-500'">
                        Password
                    </label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none">
                            <svg class="w-4 h-4 transition-colors duration-200"
                                :class="focused ? 'text-indigo-500' : 'text-slate-400'"
                                fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 10-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 002.25-2.25v-6.75a2.25 2.25 0 00-2.25-2.25H6.75a2.25 2.25 0 00-2.25 2.25v6.75a2.25 2.25 0 002.25 2.25z" />
                            </svg>
                        </div>
                        <input
                            id="password"
                            :type="show ? 'text' : 'password'"
                            name="password"
                            required
                            autocomplete="current-password"
                            @focus="focused = true"
                            @blur="focused = false"
                            class="w-full pl-10 pr-11 py-3 rounded-xl border text-sm text-slate-800 placeholder-slate-400 transition-all duration-200 outline-none
                                {{ $errors->get('password') ? 'border-rose-400 bg-rose-50 focus:border-rose-500 focus:ring-2 focus:ring-rose-200' : 'border-slate-200 bg-white/70 focus:border-indigo-400 focus:ring-2 focus:ring-indigo-100' }}"
                            placeholder="••••••••"
                        />

                        <button
                            type="button"
                            @click="show = !show"
                            class="absolute inset-y-0 right-0 pr-3.5 flex items-center text-slate-400 hover:text-indigo-500 transition-colors duration-150"
                            :aria-label="show ? 'Hide password' : 'Show password'"
                        >
                            <svg x-show="!show" class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z" />
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                            </svg>
                            <svg x-show="show" x-cloak class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 001.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.45 10.45 0 0112 4.5c4.756 0 8.773 3.162 10.065 7.498a10.523 10.523 0 01-4.293 5.774M6.228 6.228L3 3m3.228 3.228l3.65 3.65m7.894 7.894L21 21m-3.228-3.228l-3.65-3.65m0 0a3 3 0 10-4.243-4.243m4.242 4.242L9.88 9.88" />
                            </svg>
                        </button>
                    </div>
                    @foreach ($errors->get('password') as $error)
                        <p class="mt-1.5 text-xs text-rose-500 flex items-center gap-1">
                            <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-8-5a.75.75 0 01.75.75v4.5a.75.75 0 01-1.5 0v-4.5A.75.75 0 0110 5zm0 10a1 1 0 100-2 1 1 0 000 2z" clip-rule="evenodd"/></svg>
                            {{ $error }}
                        </p>
                    @endforeach
                </div>

                <div class="flex items-center justify-between mb-6">
                    <label for="remember_me" class="inline-flex items-center gap-2 cursor-pointer select-none group">
                        <input
                            id="remember_me"
                            type="checkbox"
                            name="remember"
                            class="w-4 h-4 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500 cursor-pointer"
                        />
                        <span class="text-sm text-slate-600 group-hover:text-slate-800 transition-colors">Remember me</span>
                    </label>

                    <div class="flex items-center gap-3">
                        @if (Route::has('password.request'))
                            <a href="{{ route('password.request') }}"
                                class="text-sm text-indigo-600 hover:text-indigo-800 font-medium transition-colors duration-150 hover:underline underline-offset-2">
                                Forgot password?
                            </a>
                        @endif

                        @if (Route::has('register'))
                            <span class="text-slate-300 select-none">|</span>
                            <a href="{{ route('register') }}"
                                class="text-sm text-slate-500 hover:text-indigo-600 font-medium transition-colors duration-150 hover:underline underline-offset-2">
                                Register
                            </a>
                        @endif
                    </div>
                </div>

                <button
                    type="submit"
                    :disabled="submitting"
                    class="w-full relative overflow-hidden py-3 px-6 rounded-xl font-semibold text-sm text-white
                        bg-gradient-to-r from-blue-500 via-indigo-500 to-indigo-600
                        hover:from-blue-600 hover:via-indigo-600 hover:to-indigo-700
                        shadow-md shadow-indigo-200
                        transition-all duration-200 active:scale-[0.98]
                        focus:outline-none focus:ring-2 focus:ring-indigo-400 focus:ring-offset-2
                        disabled:opacity-70 disabled:cursor-not-allowed"
                >
                    <span x-show="!submitting" class="flex items-center justify-center gap-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0013.5 3h-6a2.25 2.25 0 00-2.25 2.25v13.5A2.25 2.25 0 007.5 21h6a2.25 2.25 0 002.25-2.25V15m3 0l3-3m0 0l-3-3m3 3H9" />
                        </svg>
                        Sign In
                    </span>
                    <span x-show="submitting" x-cloak class="flex items-center justify-center gap-2">
                        <svg class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                        </svg>
                        Processing...
                    </span>
                </button>

            </form>
        </div>
    </div>
</div>

<script>
    function loginForm() {
        return {
            submitting: false,
        }
    }
</script>
@endsection
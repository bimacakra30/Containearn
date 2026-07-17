@extends('layouts.master')

@section('title', 'Check Your Email — Containearn')

@section('content')
<div class="min-h-screen flex items-center justify-center px-4 py-12">

    <div class="pointer-events-none fixed inset-0 overflow-hidden -z-10" aria-hidden="true">
        <div class="absolute -top-32 -left-24 w-[520px] h-[520px] rounded-full bg-blue-100 opacity-60 blur-3xl"></div>
        <div class="absolute -top-20 right-0 w-[400px] h-[400px] rounded-full bg-indigo-100 opacity-50 blur-3xl"></div>
        <div class="absolute bottom-0 left-1/2 -translate-x-1/2 w-[600px] h-[300px] rounded-full bg-indigo-50 opacity-40 blur-3xl"></div>
    </div>

    <div class="w-full max-w-md fade-in">
        <div class="glass rounded-3xl px-8 py-10 sm:px-10 text-center">

            <div class="flex justify-center mb-6">
                <div class="w-20 h-20 rounded-2xl bg-gradient-to-br from-blue-500 to-indigo-600 flex items-center justify-center shadow-lg">
                    <svg class="w-10 h-10 text-white" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M21.75 6.75v10.5a2.25 2.25 0 01-2.25 2.25h-15a2.25 2.25 0 01-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25m19.5 0v.243a2.25 2.25 0 01-1.07 1.916l-7.5 4.615a2.25 2.25 0 01-2.36 0L3.32 8.91a2.25 2.25 0 01-1.07-1.916V6.75" />
                    </svg>
                </div>
            </div>

            <h1 class="font-display text-2xl font-bold text-slate-800 tracking-tight mb-2">
                Verify Your Email
            </h1>
            <p class="text-sm text-slate-500 mb-6">
                We have sent a verification link to
                @if($email)
                <span class="font-semibold text-indigo-600">{{ $email }}</span>
                @else
                the email address you registered.
                @endif
            </p>

            <div class="bg-indigo-50 border border-indigo-200 rounded-2xl px-5 py-4 mb-6 text-left space-y-2">
                <div class="flex items-start gap-3">
                    <svg class="w-5 h-5 text-indigo-500 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a.75.75 0 000 1.5h.253a.25.25 0 01.244.304l-.459 2.066A1.75 1.75 0 0010.747 15H11a.75.75 0 000-1.5h-.253a.25.25 0 01-.244-.304l.459-2.066A1.75 1.75 0 009.253 9H9z" clip-rule="evenodd" />
                    </svg>
                    <div>
                        <p class="text-xs font-semibold text-indigo-700 uppercase tracking-wider mb-0.5">The next step</p>
                        <ol class="text-sm text-slate-600 space-y-1 list-decimal list-inside">
                            <li>Open your email inbox</li>
                            <li>Search for an email from <span class="font-medium">Containearn</span></li>
                            <li>Click the <span class="font-medium">"Verify Email"</span> link</li>
                            <li>Login to start learning!</li>
                        </ol>
                    </div>
                </div>
            </div>

            @if (session('status') == 'verification-link-sent')
            <div class="mb-4 px-4 py-3 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-700 text-sm flex items-center gap-2">
                <svg class="w-4 h-4 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.857-9.809a.75.75 0 00-1.214-.882l-3.483 4.79-1.88-1.88a.75.75 0 10-1.06 1.061l2.5 2.5a.75.75 0 001.137-.089l4-5.5z" clip-rule="evenodd" />
                </svg>
                A new verification link has been sent to your email address.
            </div>
            @endif

            <p class="text-xs text-slate-400 mb-2">
                Didn't receive an email? Check your <span class="font-medium">Spam / Junk</span> or click the button below.
            </p>

            <div class="mt-4 pt-4 border-t border-slate-100">
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit"
                        class="text-sm text-indigo-600 hover:text-indigo-800 font-medium transition-colors duration-150 bg-transparent border-none cursor-pointer">
                        ← Back to Login
                    </button>
                </form>
            </div>

        </div>
    </div>
</div>
@endsection
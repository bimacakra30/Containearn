@extends('layouts.master')

@section('title', 'Email Verification — Containearn')

@section('content')
<div class="min-h-screen flex items-center justify-center px-4 py-12">

    <div class="pointer-events-none fixed inset-0 overflow-hidden -z-10" aria-hidden="true">
        <div class="absolute -top-32 -left-24 w-[520px] h-[520px] rounded-full bg-amber-100 opacity-60 blur-3xl"></div>
        <div class="absolute -top-20 right-0 w-[400px] h-[400px] rounded-full bg-orange-100 opacity-50 blur-3xl"></div>
        <div class="absolute bottom-0 left-1/2 -translate-x-1/2 w-[600px] h-[300px] rounded-full bg-yellow-50 opacity-40 blur-3xl"></div>
    </div>

    <div class="w-full max-w-md fade-in">
        <div class="glass rounded-3xl px-8 py-10 sm:px-10 text-center">

            <div class="flex justify-center mb-6">
                <div class="w-20 h-20 rounded-2xl bg-gradient-to-br from-amber-400 to-orange-500 flex items-center justify-center shadow-lg">
                    <svg class="w-10 h-10 text-white" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" />
                    </svg>
                </div>
            </div>

            <h1 class="font-display text-2xl font-bold text-slate-800 tracking-tight mb-2">
                Email Not Verified
            </h1>
            <p class="text-sm text-slate-500 mb-6">
                Before proceeding, please verify your email address.
                Please check your inbox and click the verification link we sent you.
            </p>

            @if (session('status') == 'verification-link-sent')
            <div class="mb-5 px-4 py-3 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-700 text-sm flex items-center gap-2">
                <svg class="w-4 h-4 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.857-9.809a.75.75 0 00-1.214-.882l-3.483 4.79-1.88-1.88a.75.75 0 10-1.06 1.061l2.5 2.5a.75.75 0 001.137-.089l4-5.5z" clip-rule="evenodd" />
                </svg>
                A new verification link has been sent to your email.
            </div>
            @endif

            <div class="bg-amber-50 border border-amber-200 rounded-2xl px-5 py-4 mb-6 text-left">
                <div class="flex items-start gap-3">
                    <svg class="w-5 h-5 text-amber-500 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a.75.75 0 000 1.5h.253a.25.25 0 01.244.304l-.459 2.066A1.75 1.75 0 0010.747 15H11a.75.75 0 000-1.5h-.253a.25.25 0 01-.244-.304l.459-2.066A1.75 1.75 0 009.253 9H9z" clip-rule="evenodd" />
                    </svg>
                    <p class="text-sm text-slate-600">
                        Didn't receive the email? Check your <span class="font-medium">Spam / Junk</span> folder or resend the verification link.
                    </p>
                </div>
            </div>

            <div class="space-y-3">
                <form method="POST" action="{{ route('verification.send') }}">
                    @csrf
                    <button
                        type="submit"
                        id="resend-verification-btn"
                        class="w-full py-3 px-4 rounded-xl text-sm font-semibold text-white bg-gradient-to-r from-amber-500 to-orange-500 hover:from-amber-600 hover:to-orange-600 shadow-md shadow-amber-200 transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-amber-400 focus:ring-offset-2">
                        Resend Verification Email
                    </button>
                </form>

                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button
                        type="submit"
                        id="logout-from-verify-btn"
                        class="w-full py-3 px-4 rounded-xl text-sm font-semibold text-slate-600 border border-slate-200 bg-white/70 hover:bg-slate-50 transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-slate-300">
                        Log Out
                    </button>
                </form>
            </div>

        </div>
    </div>
</div>
@endsection
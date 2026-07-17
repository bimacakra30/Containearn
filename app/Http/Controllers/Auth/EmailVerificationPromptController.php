<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class EmailVerificationPromptController extends Controller
{
    /**
     * Display the email verification prompt.
     */
    public function __invoke(Request $request): RedirectResponse|View
    {
        if ($request->user()->hasVerifiedEmail()) {
            $destination = $request->user()->isAdmin()
                ? route('admin.dashboard')
                : route('mahasiswa.dashboard');

            return redirect()->intended($destination);
        }

        return view('auth.verification-pending', [
            'email' => $request->user()->email,
        ]);
    }
}

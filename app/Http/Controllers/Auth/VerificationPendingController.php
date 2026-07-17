<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\View\View;

class VerificationPendingController extends Controller
{
    public function __invoke(Request $request): View
    {
        $email = $request->session()->get('email', '');

        return view('auth.verification-pending', compact('email'));
    }
}

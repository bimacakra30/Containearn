<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class RegisteredUserController extends Controller
{
    /**
     * Display the registration view.
     */
    public function create(): View
    {
        return view('auth.register');
    }

    /**
     * Handle an incoming registration request.
     *
     * @throws ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'identity_id' => ['required', 'string', 'unique:user'],
            'name' => ['required', 'string', 'max:60'],
            'email' => ['required', 'email', 'max:40', 'unique:user'],
            'class' => ['required', Rule::in(['A', 'B', 'C', 'D'])],
            'password' => ['required', 'string', 'min:8', 'max:60', 'confirmed'],
        ]);

        $user = User::create([
            'identity_id' => $request->identity_id,
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => 'mahasiswa',
            'class' => $request->class,
            'status' => 'inactive',
        ]);
        Auth::login($user);
        event(new Registered($user));
        return redirect()->route('verification.notice');
    }
}

<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class ProfileController extends Controller
{
    public function update(Request $request)
    {
        $validated = $request->validate([
            'identity_id' => 'nullable|string|max:50|unique:users,identity_id,' . $request->user()->id,
            'name'     => 'required|string|max:255',
            'email'    => 'required|email|unique:users,email,' . $request->user()->id,
            'password' => 'nullable|min:8|confirmed',
        ]);

        $request->user()->update([
            'identity_id' => $validated['identity_id'],
            'name'  => $validated['name'],
            'email' => $validated['email'],
            ...(!empty($validated['password'])
                ? ['password' => Hash::make($validated['password'])]
                : []
            ),
        ]);

        return back()->with('success', 'Profile updated successfully.');
    }

    public function destroy(Request $request)
    {
        abort_if($request->user()->role === 'superadmin', 403, 'Superadmin cannot delete their own account.');

        $request->validate([
            'password' => 'required|current_password',
        ]);

        $user = $request->user();
        auth()->logout();
        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }
}

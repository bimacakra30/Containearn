<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class ProfileController extends Controller
{
    public function update(Request $request)
    {
        $validated = $request->validate([
            'identity_id' => [
                'required',
                'string',
                'regex:/^[0-9]+$/',
                'max:18',
                Rule::unique('user', 'identity_id')->ignore($request->user()->getKey(), 'id_user'),
            ],
            'name' => 'required|string|max:60',
            'email' => [
                'required',
                'email',
                'max:40',
                Rule::unique('user', 'email')->ignore($request->user()->getKey(), 'id_user'),
            ],
            'class' => [
                'nullable',
                Rule::requiredIf($request->user()->role === 'mahasiswa'),
                Rule::in(['A', 'B', 'C', 'D']),
            ],
            'password' => 'nullable|string|min:8|max:60|confirmed',
        ]);

        $request->user()->update([
            'identity_id' => $validated['identity_id'],
            'name' => $validated['name'],
            'email' => $validated['email'],
            'class' => $request->user()->role === 'mahasiswa' ? $validated['class'] : null,
            ...(! empty($validated['password'])
                ? ['password' => Hash::make($validated['password'])]
                : []
            ),
        ]);

        return back()->with('success', 'Profile updated successfully.');
    }
}
<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class ProfileController extends Controller
{
    public function edit(Request $request)
    {
        return view("pages.Profile.Edit", [
            'user' => $request->user(),
        ]);
    }

    public function update(Request $request)
    {
        $user = $request->user();

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'username' => [
                'required',
                'string',
                'max:255',
                Rule::unique('users', 'username')->ignore($user->id),
            ],
            'phone' => [
                'required',
                'string',
                'max:20',
                Rule::unique('users', 'phone')->ignore($user->id),
            ],
            'password' => ['nullable', 'string', 'min:8', 'confirmed'],
        ]);

        $dataUpdate = [
            'name' => $validated['name'],
            'username' => $validated['username'],
            'phone' => $validated['phone'],
        ];

        if (!empty($validated['password'])) {
            $dataUpdate['password'] = Hash::make($validated['password']);
        }

        $user->update($dataUpdate);

        return redirect()->route('profile.edit')->with('success', 'Profile berhasil diperbarui');
    }
}

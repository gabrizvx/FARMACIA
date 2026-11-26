<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;
use RuntimeException;

class PasswordController extends Controller
{
    public function edit(): View
    {
        return view('account.password');
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => [
                'required',
                'string',
                Password::min(8),
                'confirmed',
                'different:current_password',
            ],
        ]);

        $user = $request->user();
        $user->password = $validated['password'];

        if (! $user->save()) {
            throw new RuntimeException('No se pudo actualizar la contraseña.');
        }

        Auth::logoutOtherDevices($validated['password']);
        $request->session()->regenerate();
        $request->session()->regenerateToken();

        return to_route('password.edit')
            ->with('ok', 'Tu contraseña se actualizó correctamente.');
    }
}

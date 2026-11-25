<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AuthController extends Controller
{
    public function showLogin(): View
    {
        return view('auth.login');
    }

    public function login(Request $request): RedirectResponse
    {
        $credenciales = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ], [
            'email.required' => 'Ingresa tu correo electrónico.',
            'email.email' => 'El formato del correo no es válido.',
            'password.required' => 'Ingresa tu contraseña.',
        ]);

        $recordar = $request->boolean('remember');

        if (Auth::attempt($credenciales, $recordar)) {
            $request->session()->regenerate();

            return redirect()
                ->intended(route('sales.index'))
                ->with('ok', 'Bienvenido al sistema');
        }

        return back()
            ->withErrors(['email' => 'Las credenciales ingresadas son incorrectas.'])
            ->onlyInput('email');
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()
            ->route('login')
            ->with('ok', 'Sesión cerrada correctamente');
    }
}

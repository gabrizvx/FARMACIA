<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Role;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class UserController extends Controller
{
    private const VISTA_INDICE = 'settings.users.index';
    private const VISTA_CREAR = 'settings.users.create';
    private const VISTA_EDITAR = 'settings.users.edit';

    public function index(): View
    {
        $coleccionUsuarios = User::query()
            ->with('role')
            ->latest('id')
            ->get();

        return view(self::VISTA_INDICE, [
            'users' => $coleccionUsuarios,
        ]);
    }

    public function create(): View
    {
        return view(self::VISTA_CREAR, [
            'roles' => $this->listarRoles(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $atributos = $this->obtenerDatosValidados($request);

        $nuevoUsuario = new User();
        $nuevoUsuario->fill($atributos)->save();

        return redirect()
            ->route('users.index')
            ->with('ok', 'Usuario registrado');
    }

    public function edit(User $user): View
    {
        return view(self::VISTA_EDITAR, [
            'user' => $user,
            'roles' => $this->listarRoles(),
        ]);
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $atributos = $this->obtenerDatosValidados($request, $user->id);

        if (!$request->filled('password')) {
            unset($atributos['password']);
        }

        $user->fill($atributos)->save();

        return redirect()
            ->route('users.index')
            ->with('ok', 'Usuario actualizado');
    }

    public function destroy(User $user): RedirectResponse
    {
        $user->loadCount('sales');

        if ($user->sales_count > 0) {
            return redirect()
                ->route('users.index')
                ->with('error', 'No se puede eliminar el usuario porque tiene ventas registradas');
        }

        $user->delete();

        return redirect()
            ->route('users.index')
            ->with('ok', 'Usuario eliminado');
    }

    private function obtenerDatosValidados(Request $request, ?int $usuarioId = null): array
    {
        $reglaDni = Rule::unique('users', 'dni');
        $reglaEmail = Rule::unique('users', 'email');
        $reglaClave = ['required', 'string', 'min:8', 'confirmed'];

        if ($usuarioId !== null) {
            $reglaDni->ignore($usuarioId);
            $reglaEmail->ignore($usuarioId);
            $reglaClave[0] = 'nullable';
        }

        return $request->validate([
            'role_id' => ['required', 'integer', 'exists:roles,id'],
            'name' => ['required', 'string', 'max:255'],
            'dni' => ['required', 'digits:8', $reglaDni],
            'phone' => ['nullable', 'string', 'max:15'],
            'tuition' => ['nullable', 'string', 'max:20'],
            'email' => ['required', 'email', 'max:255', $reglaEmail],
            'password' => $reglaClave,
        ]);
    }

    private function listarRoles(): Collection
    {
        return Role::query()
            ->orderBy('name')
            ->get();
    }
}

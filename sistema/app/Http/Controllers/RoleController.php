<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Role;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class RoleController extends Controller
{
    private const VISTA_INDICE = 'settings.roles.index';
    private const VISTA_CREAR = 'settings.roles.create';
    private const VISTA_EDITAR = 'settings.roles.edit';

    public function index(): View
    {
        $coleccionRoles = Role::query()
            ->latest('id')
            ->get();

        return view(self::VISTA_INDICE, [
            'roles' => $coleccionRoles,
        ]);
    }

    public function create(): View
    {
        return view(self::VISTA_CREAR);
    }

    public function store(Request $request): RedirectResponse
    {
        $atributos = $this->obtenerDatosValidados($request);

        $nuevoRol = new Role();
        $nuevoRol->fill($atributos)->save();

        return redirect()
            ->route('roles.index')
            ->with('ok', 'Rol registrado');
    }

    public function edit(Role $role): View
    {
        return view(self::VISTA_EDITAR, [
            'role' => $role,
        ]);
    }

    public function update(Request $request, Role $role): RedirectResponse
    {
        $atributos = $this->obtenerDatosValidados($request, $role->id);

        $role->fill($atributos)->save();

        return redirect()
            ->route('roles.index')
            ->with('ok', 'Rol actualizado');
    }

    public function destroy(Role $role): RedirectResponse
    {
        $role->loadCount('users');

        if ($role->users_count > 0) {
            return redirect()
                ->route('roles.index')
                ->with('error', 'No se puede eliminar el rol porque tiene usuarios asignados');
        }

        $role->delete();

        return redirect()
            ->route('roles.index')
            ->with('ok', 'Rol eliminado');
    }

    private function obtenerDatosValidados(Request $request, ?int $rolId = null): array
    {
        $reglaNombreUnico = Rule::unique('roles', 'name');

        if ($rolId !== null) {
            $reglaNombreUnico->ignore($rolId);
        }

        return $request->validate([
            'name' => ['required', 'string', 'max:50', $reglaNombreUnico],
            'description' => ['nullable', 'string', 'max:255'],
        ]);
    }
}

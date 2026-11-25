<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Customer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class CustomerController extends Controller
{
    public function index(): View
    {
        $listaClientes = Customer::query()
            ->orderByDesc('created_at')
            ->get();

        return view('management.customers.index', [
            'customers' => $listaClientes,
        ]);
    }

    public function create(): View
    {
        return view('management.customers.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $datos = $this->validarCliente($request);

        Customer::create($datos);

        return redirect()
            ->route('customers.index')
            ->with('ok', 'Cliente registrado');
    }

    public function edit(Customer $customer): View
    {
        return view('management.customers.edit', [
            'customer' => $customer,
        ]);
    }

    public function update(Request $request, Customer $customer): RedirectResponse
    {
        $datos = $this->validarCliente($request, $customer);

        $customer->update($datos);

        return redirect()
            ->route('customers.index')
            ->with('ok', 'Cliente actualizado');
    }

    public function destroy(Customer $customer): RedirectResponse
    {
        if ($customer->sales()->count() > 0) {
            return redirect()
                ->route('customers.index')
                ->with('error', 'No se puede eliminar el cliente porque tiene ventas registradas');
        }

        $customer->delete();

        return redirect()
            ->route('customers.index')
            ->with('ok', 'Cliente eliminado');
    }

    private function validarCliente(Request $request, ?Customer $cliente = null): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'dni' => [
                'required',
                'digits:8',
                $cliente !== null
                    ? Rule::unique('customers', 'dni')->ignore($cliente->id)
                    : 'unique:customers,dni',
            ],
            'phone' => ['required', 'digits:9'],
            'age' => ['required', 'integer', 'min:0', 'max:120'],
        ]);
    }
}

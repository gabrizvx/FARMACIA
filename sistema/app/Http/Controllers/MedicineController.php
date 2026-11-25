<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Medicine;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class MedicineController extends Controller
{
    public const LIMITE_STOCK_BAJO = 10;

    private const DISCO_ALMACENAMIENTO = 'public';
    private const CARPETA_IMAGENES = 'medicines';

    public function index(Request $request): View
    {
        $filtros = [
            'search' => $request->string('search')->trim()->value(),
            'category_id' => $request->input('category_id'),
            'low_stock' => $request->boolean('low_stock'),
        ];

        $listaMedicamentos = Medicine::query()
            ->with('category')
            ->when(
                $filtros['search'] !== '',
                fn ($q) => $q->where('name', 'like', "%{$filtros['search']}%")
            )
            ->when(
                !empty($filtros['category_id']),
                fn ($q) => $q->where('category_id', $filtros['category_id'])
            )
            ->when(
                $filtros['low_stock'],
                fn ($q) => $q->where('stock', '<=', self::LIMITE_STOCK_BAJO)
            )
            ->orderBy('name')
            ->get();

        return view('management.medicines.index', [
            'medicines' => $listaMedicamentos,
            'categories' => $this->listarCategorias(),
            'lowStock' => self::LIMITE_STOCK_BAJO,
        ]);
    }

    public function create(): View
    {
        return view('management.medicines.create', [
            'categories' => $this->listarCategorias(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $datosValidados = $this->validarFormulario($request);

        if ($request->hasFile('image')) {
            $datosValidados['image'] = $this->subirArchivo($request);
        }

        Medicine::query()->create($datosValidados);

        return redirect()
            ->route('medicines.index')
            ->with('ok', 'Medicamento registrado');
    }

    public function edit(Medicine $medicine): View
    {
        return view('management.medicines.edit', [
            'medicine' => $medicine,
            'categories' => $this->listarCategorias(),
        ]);
    }

    public function update(Request $request, Medicine $medicine): RedirectResponse
    {
        $datosValidados = $this->validarFormulario($request);

        if ($request->hasFile('image')) {
            $this->borrarArchivo($medicine->image);
            $datosValidados['image'] = $this->subirArchivo($request);
        } else {
            unset($datosValidados['image']);
        }

        $medicine->fill($datosValidados)->save();

        return redirect()
            ->route('medicines.index')
            ->with('ok', 'Medicamento actualizado');
    }

    public function destroy(Medicine $medicine): RedirectResponse
    {
        if ($medicine->detailSales()->exists()) {
            return redirect()
                ->route('medicines.index')
                ->with('error', 'No se puede eliminar el medicamento porque tiene ventas registradas');
        }

        $this->borrarArchivo($medicine->image);
        $medicine->delete();

        return redirect()
            ->route('medicines.index')
            ->with('ok', 'Medicamento eliminado');
    }

    private function validarFormulario(Request $request): array
    {
        return $request->validate([
            'category_id' => ['required', 'integer', 'exists:categories,id'],
            'name' => ['required', 'string', 'max:120'],
            'price' => ['required', 'numeric', 'min:0'],
            'stock' => ['required', 'integer', 'min:0'],
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ]);
    }

    private function listarCategorias(): Collection
    {
        return Category::query()
            ->orderBy('name')
            ->get();
    }

    private function subirArchivo(Request $request): string
    {
        return $request->file('image')->store(
            self::CARPETA_IMAGENES,
            self::DISCO_ALMACENAMIENTO
        );
    }

    private function borrarArchivo(?string $rutaArchivo): void
    {
        if (!empty($rutaArchivo)) {
            Storage::disk(self::DISCO_ALMACENAMIENTO)->delete($rutaArchivo);
        }
    }
}

<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use RuntimeException;

class ProfileController extends Controller
{
    public function edit(Request $request): View
    {
        $user = $request->user()->loadMissing(['role', 'preferences']);

        return view('account.profile', [
            'user' => $user,
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $user = $request->user();
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($user->id),
            ],
            'phone' => ['nullable', 'string', 'max:15', 'regex:/^\+?[0-9\s().-]{7,15}$/'],
            'profile_photo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'remove_photo' => ['sometimes', 'boolean'],
        ]);

        $previousPhotoPath = $user->profile_photo_path;
        $newPhotoPath = null;
        $removePhoto = $request->boolean('remove_photo');

        if ($request->hasFile('profile_photo')) {
            $newPhotoPath = $request->file('profile_photo')->store('profiles', 'public');

            if ($newPhotoPath === false) {
                throw new RuntimeException('No se pudo guardar la fotografía de perfil.');
            }

            $user->profile_photo_path = $newPhotoPath;
        } elseif ($removePhoto) {
            $user->profile_photo_path = null;
        }

        $user->fill([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'] ?? null,
        ]);

        if (! $user->save()) {
            if ($newPhotoPath !== null) {
                $this->deletePhoto($newPhotoPath);
            }

            throw new RuntimeException('No se pudieron guardar los cambios del perfil.');
        }

        if (($newPhotoPath !== null || $removePhoto) && $previousPhotoPath !== null) {
            $this->deletePhoto($previousPhotoPath);
        }

        return to_route('profile.edit')
            ->with('ok', 'Tu perfil se actualizó correctamente.');
    }

    private function deletePhoto(string $path): void
    {
        if (! str_starts_with($path, 'profiles/')) {
            return;
        }

        if (! Storage::disk('public')->delete($path)) {
            Log::warning('No se pudo eliminar una fotografía de perfil.', [
                'path' => $path,
            ]);
        }
    }
}

<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class PreferenceController extends Controller
{
    private const DEFAULTS = [
        'theme' => 'system',
        'accent_color' => 'brand',
        'density' => 'comfortable',
        'sidebar_collapsed' => false,
    ];

    public function edit(Request $request): View
    {
        $preferences = $request->user()
            ->preferences()
            ->firstOrNew([], self::DEFAULTS);

        return view('account.preferences', [
            'preferences' => $preferences,
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'theme' => ['required', Rule::in(['light', 'dark', 'system'])],
            'accent_color' => ['required', Rule::in(['brand', 'blue', 'teal', 'violet'])],
            'density' => ['required', Rule::in(['comfortable', 'compact'])],
            'sidebar_collapsed' => ['required', 'boolean'],
        ]);

        $validated['sidebar_collapsed'] = (bool) $validated['sidebar_collapsed'];
        $request->user()->preferences()->updateOrCreate([], $validated);

        return to_route('preferences.edit')
            ->with('ok', 'Tus preferencias se guardaron correctamente.');
    }

    public function reset(Request $request): RedirectResponse
    {
        $request->user()
            ->preferences()
            ->updateOrCreate([], self::DEFAULTS);

        return to_route('preferences.edit')
            ->with('ok', 'Se restauraron tus preferencias predeterminadas.');
    }
}

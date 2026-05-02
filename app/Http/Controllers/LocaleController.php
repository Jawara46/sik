<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class LocaleController extends Controller
{
    public function update(Request $request): JsonResponse|RedirectResponse
    {
        $validated = $request->validate([
            'locale' => ['required', 'string', 'in:id,en'],
        ]);

        $request->session()->put('locale', $validated['locale']);

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'locale' => $validated['locale'],
            ]);
        }

        return back();
    }
}

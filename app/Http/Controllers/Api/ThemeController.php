<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CssTheme;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ThemeController extends Controller
{
    /**
     * Get all themes.
     */
    public function index()
    {
        $themes = CssTheme::where('type', 'site')
            ->orderBy('name')
            ->get();

        return response()->json([
            'themes' => $themes,
        ]);
    }

    /**
     * Get a single theme.
     */
    public function show(CssTheme $theme)
    {
        return response()->json([
            'theme' => $theme,
        ]);
    }

    /**
     * Create a new theme.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'is_dark' => 'required|boolean',
            'default_dark' => 'nullable|boolean',
            'default_light' => 'nullable|boolean',
            'values' => 'required|array',
            'font' => 'nullable|array',
        ]);

        DB::transaction(function () use ($validated, &$theme) {
            // Clear existing defaults if setting new default
            if ($validated['default_dark'] ?? false) {
                CssTheme::where('default_dark', true)->update(['default_dark' => false]);
            }
            if ($validated['default_light'] ?? false) {
                CssTheme::where('default_light', true)->update(['default_light' => false]);
            }

            $theme = CssTheme::create([
                'name' => $validated['name'],
                'type' => 'site',
                'is_dark' => $validated['is_dark'],
                'default_dark' => $validated['default_dark'] ?? false,
                'default_light' => $validated['default_light'] ?? false,
                'values' => $validated['values'],
                'font' => $validated['font'] ?? null,
            ]);
        });

        return response()->json([
            'message' => 'Theme created successfully',
            'theme' => $theme,
        ], 201);
    }

    /**
     * Update an existing theme.
     */
    public function update(Request $request, CssTheme $theme)
    {
        $validated = $request->validate([
            'name' => 'nullable|string|max:255',
            'is_dark' => 'nullable|boolean',
            'default_dark' => 'nullable|boolean',
            'default_light' => 'nullable|boolean',
            'values' => 'nullable|array',
            'font' => 'nullable|array',
        ]);

        DB::transaction(function () use ($validated, $theme) {
            // Clear existing defaults if setting new default
            if ($validated['default_dark'] ?? false) {
                CssTheme::where('default_dark', true)
                    ->where('id', '!=', $theme->id)
                    ->update(['default_dark' => false]);
            }
            if ($validated['default_light'] ?? false) {
                CssTheme::where('default_light', true)
                    ->where('id', '!=', $theme->id)
                    ->update(['default_light' => false]);
            }

            $theme->update($validated);
        });

        return response()->json([
            'message' => 'Theme updated successfully',
            'theme' => $theme->fresh(),
        ]);
    }

    /**
     * Delete a theme.
     */
    public function destroy(CssTheme $theme)
    {
        // Prevent deleting default themes
        if ($theme->default_dark || $theme->default_light) {
            return response()->json([
                'message' => 'Cannot delete default theme. Set another theme as default first.',
            ], 422);
        }

        $theme->delete();

        return response()->json([
            'message' => 'Theme deleted successfully',
        ]);
    }
}

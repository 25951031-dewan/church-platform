<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\LandingPageSection;
use Illuminate\Http\Request;

class LandingPageController extends Controller
{
    /**
     * Get all landing page sections (for admin editor).
     */
    public function index()
    {
        $sections = LandingPageSection::all();

        return response()->json([
            'sections' => $sections,
        ]);
    }

    /**
     * Get visible landing page sections (for public landing page).
     */
    public function publicSections()
    {
        $sections = LandingPageSection::where('is_visible', true)->get();

        return response()->json([
            'sections' => $sections,
        ]);
    }

    /**
     * Update all landing page sections at once.
     */
    public function update(Request $request)
    {
        $validated = $request->validate([
            'sections' => 'required|array',
            'sections.*.id' => 'nullable|exists:landing_page_sections,id',
            'sections.*.name' => 'required|string',
            'sections.*.sort_order' => 'required|integer',
            'sections.*.is_visible' => 'required|boolean',
            'sections.*.config' => 'nullable|array',
        ]);

        // Delete sections not in the request
        $ids = collect($validated['sections'])->pluck('id')->filter();
        LandingPageSection::whereNotIn('id', $ids)->delete();

        // Update or create sections
        foreach ($validated['sections'] as $sectionData) {
            if (isset($sectionData['id'])) {
                LandingPageSection::where('id', $sectionData['id'])->update([
                    'name' => $sectionData['name'],
                    'sort_order' => $sectionData['sort_order'],
                    'is_visible' => $sectionData['is_visible'],
                    'config' => $sectionData['config'] ?? null,
                ]);
            } else {
                LandingPageSection::create([
                    'name' => $sectionData['name'],
                    'sort_order' => $sectionData['sort_order'],
                    'is_visible' => $sectionData['is_visible'],
                    'config' => $sectionData['config'] ?? null,
                ]);
            }
        }

        $sections = LandingPageSection::all();

        return response()->json([
            'message' => 'Landing page sections updated successfully',
            'sections' => $sections,
        ]);
    }
}

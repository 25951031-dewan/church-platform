<?php

namespace Plugins\Faq\Controllers;

use App\Services\PlatformModeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Plugins\Faq\Models\Faq;
use Plugins\Faq\Models\FaqCategory;

class FaqController extends Controller
{
    public function __construct(private readonly PlatformModeService $platform) {}

    /**
     * GET /api/v1/faq
     * Return published categories with their published FAQs.
     */
    public function index(Request $request): JsonResponse
    {
        $churchId = $this->platform->defaultChurch();

        $categories = FaqCategory::active()
            ->forChurch($churchId)
            ->with(['publishedFaqs' => fn ($q) => $q->forChurch($churchId)])
            ->orderBy('sort_order')
            ->get();

        return response()->json($categories);
    }

    /**
     * GET /api/v1/faq/{id}
     */
    public function show(int $id): JsonResponse
    {
        $faq = Faq::published()->findOrFail($id);
        $faq->incrementViews();

        return response()->json($faq->load('category'));
    }
}

<?php

namespace Plugins\Faq\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Str;
use Plugins\Faq\Models\Faq;
use Plugins\Faq\Models\FaqCategory;

class FaqAdminController extends Controller
{
    // ─── Categories ───────────────────────────────────────────────────────────

    public function indexCategories(): JsonResponse
    {
        return response()->json(FaqCategory::orderBy('sort_order')->get());
    }

    public function storeCategory(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name'        => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'sort_order'  => ['nullable', 'integer'],
            'church_id'   => ['nullable', 'integer', 'exists:churches,id'],
            'is_active'   => ['boolean'],
        ]);

        $data['slug'] = Str::slug($data['name']);

        return response()->json(FaqCategory::create($data), 201);
    }

    public function updateCategory(Request $request, FaqCategory $category): JsonResponse
    {
        $data = $request->validate([
            'name'        => ['sometimes', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'sort_order'  => ['nullable', 'integer'],
            'is_active'   => ['boolean'],
        ]);

        if (isset($data['name'])) {
            $data['slug'] = Str::slug($data['name']);
        }

        $category->update($data);

        return response()->json($category);
    }

    public function destroyCategory(FaqCategory $category): JsonResponse
    {
        $category->delete();

        return response()->json(null, 204);
    }

    // ─── FAQs ─────────────────────────────────────────────────────────────────

    public function indexFaqs(Request $request): JsonResponse
    {
        $faqs = Faq::with('category')
            ->when($request->category_id, fn ($q, $id) => $q->where('faq_category_id', $id))
            ->orderBy('sort_order')
            ->paginate($request->integer('per_page', 25));

        return response()->json($faqs);
    }

    public function storeFaq(Request $request): JsonResponse
    {
        $data = $request->validate([
            'faq_category_id' => ['required', 'integer', 'exists:faq_categories,id'],
            'question'        => ['required', 'string'],
            'answer'          => ['required', 'string'],
            'sort_order'      => ['nullable', 'integer'],
            'church_id'       => ['nullable', 'integer', 'exists:churches,id'],
            'is_published'    => ['boolean'],
        ]);

        $data['author_id'] = $request->user()?->id;

        return response()->json(Faq::create($data), 201);
    }

    public function updateFaq(Request $request, Faq $faq): JsonResponse
    {
        $data = $request->validate([
            'faq_category_id' => ['sometimes', 'integer', 'exists:faq_categories,id'],
            'question'        => ['sometimes', 'string'],
            'answer'          => ['sometimes', 'string'],
            'sort_order'      => ['nullable', 'integer'],
            'is_published'    => ['boolean'],
        ]);

        $faq->update($data);

        return response()->json($faq);
    }

    public function destroyFaq(Faq $faq): JsonResponse
    {
        $faq->delete();

        return response()->json(null, 204);
    }
}

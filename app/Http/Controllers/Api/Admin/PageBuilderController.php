<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Page;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class PageBuilderController extends Controller
{
    /**
     * GET /api/v1/admin/pages
     * List all pages, optionally scoped to a church.
     */
    public function index(Request $request): JsonResponse
    {
        $pages = Page::when(
            $request->filled('church_id'),
            fn ($q) => $q->where('church_id', $request->integer('church_id')),
            fn ($q) => $q->whereNull('church_id')
        )
            ->orderByDesc('updated_at')
            ->get(['id', 'title', 'slug', 'status', 'use_builder', 'church_id', 'updated_at']);

        return response()->json($pages);
    }

    /**
     * POST /api/v1/admin/pages
     */
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'title'            => ['required', 'string', 'max:255'],
            'slug'             => ['nullable', 'string', 'max:255', 'unique:pages,slug'],
            'content'          => ['nullable', 'string'],
            'template'         => ['nullable', 'string'],
            'meta_title'       => ['nullable', 'string', 'max:255'],
            'meta_description' => ['nullable', 'string', 'max:500'],
            'status'           => ['nullable', 'in:published,draft'],
            'church_id'        => ['nullable', 'integer', 'exists:churches,id'],
        ]);

        $data['slug']      = $data['slug'] ?? Str::slug($data['title']);
        $data['author_id'] = $request->user()?->id;
        $data['status']    = $data['status'] ?? 'draft';

        return response()->json(Page::create($data), 201);
    }

    /**
     * GET /api/v1/admin/pages/{page}/builder
     * Return full builder state for the editor.
     */
    public function getBuilder(Page $page): JsonResponse
    {
        return response()->json([
            'id'           => $page->id,
            'title'        => $page->title,
            'builder_data' => $page->builder_data ? json_decode($page->builder_data, true) : null,
            'builder_html' => $page->builder_html,
            'builder_css'  => $page->builder_css,
            'use_builder'  => $page->use_builder,
        ]);
    }

    /**
     * PUT /api/v1/admin/pages/{page}/builder
     * Save GrapesJS output (JSON state + rendered HTML/CSS).
     */
    public function saveBuilder(Request $request, Page $page): JsonResponse
    {
        $data = $request->validate([
            'builder_data' => ['required', 'array'],
            'builder_html' => ['required', 'string'],
            'builder_css'  => ['nullable', 'string'],
        ]);

        $page->update([
            'builder_data' => json_encode($data['builder_data']),
            'builder_html' => $data['builder_html'],
            'builder_css'  => $data['builder_css'] ?? '',
            'use_builder'  => true,
        ]);

        return response()->json(['saved' => true]);
    }

    /**
     * PATCH /api/v1/admin/pages/{page}
     * Update page metadata (title, slug, status, etc.).
     */
    public function update(Request $request, Page $page): JsonResponse
    {
        $data = $request->validate([
            'title'            => ['sometimes', 'string', 'max:255'],
            'slug'             => ['sometimes', 'string', 'max:255', 'unique:pages,slug,'.$page->id],
            'content'          => ['nullable', 'string'],
            'meta_title'       => ['nullable', 'string', 'max:255'],
            'meta_description' => ['nullable', 'string', 'max:500'],
            'status'           => ['sometimes', 'in:published,draft'],
            'use_builder'      => ['sometimes', 'boolean'],
        ]);

        $page->update($data);

        return response()->json($page);
    }

    /**
     * DELETE /api/v1/admin/pages/{page}
     */
    public function destroy(Page $page): JsonResponse
    {
        $page->delete();

        return response()->json(null, 204);
    }
}

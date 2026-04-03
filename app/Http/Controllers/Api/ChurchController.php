<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Church;
use App\Notifications\ChurchStatusChanged;
use App\Services\ChurchImportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ChurchController extends Controller
{
    /**
     * List all churches (admin).
     */
    public function index(Request $request): JsonResponse
    {
        $query = Church::with('admin:id,name,email')->latest();

        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('city', 'like', "%{$search}%")
                  ->orWhere('denomination', 'like', "%{$search}%");
            });
        }

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        $churches = $query->paginate($request->get('per_page', 15));

        return response()->json($churches);
    }

    /**
     * List approved churches (public directory).
     */
    public function directory(Request $request): JsonResponse
    {
        $query = Church::approved()->latest();

        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('city', 'like', "%{$search}%")
                  ->orWhere('denomination', 'like', "%{$search}%");
            });
        }

        if ($request->has('city')) {
            $query->where('city', $request->city);
        }

        if ($request->has('denomination')) {
            $query->where('denomination', $request->denomination);
        }

        $churches = $query->paginate($request->get('per_page', 12));

        return response()->json($churches);
    }

    /**
     * Show a single church by slug (public).
     */
    public function showBySlug(string $slug): JsonResponse
    {
        $church = Church::where('slug', $slug)->approved()->firstOrFail();

        return response()->json([
            'success' => true,
            'data' => $church,
        ]);
    }

    /**
     * Increment view count for a church.
     */
    public function incrementView(string $slug): JsonResponse
    {
        $church = Church::where('slug', $slug)->firstOrFail();
        $church->increment('view_count');

        return response()->json([
            'success' => true,
            'view_count' => $church->view_count,
        ]);
    }

    /**
     * Store a new church (Super Admin).
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name'              => 'required|string|max:255',
            'email'             => 'nullable|email|max:255',
            'phone'             => 'nullable|string|max:50',
            'website'           => 'nullable|url|max:255',
            'address'           => 'nullable|string|max:500',
            'city'              => 'nullable|string|max:100',
            'state'             => 'nullable|string|max:100',
            'zip_code'          => 'nullable|string|max:20',
            'country'           => 'nullable|string|max:100',
            'latitude'          => 'nullable|numeric|between:-90,90',
            'longitude'         => 'nullable|numeric|between:-180,180',
            'service_hours'     => 'nullable|json',
            'denomination'      => 'nullable|string|max:100',
            'year_founded'      => 'nullable|integer|min:1000|max:' . date('Y'),
            'short_description' => 'nullable|string|max:500',
            'history'           => 'nullable|string',
            'mission_statement' => 'nullable|string|max:1000',
            'vision_statement'  => 'nullable|string|max:1000',
            'logo'              => 'nullable|image|mimes:jpeg,png,jpg,gif,webp,svg|max:2048',
            'cover_photo'       => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:5120',
            'primary_color'     => 'nullable|string|max:20',
            'secondary_color'   => 'nullable|string|max:20',
            'meta_title'        => 'nullable|string|max:70',
            'meta_description'  => 'nullable|string|max:160',
            'facebook_url'      => 'nullable|url|max:255',
            'instagram_url'     => 'nullable|url|max:255',
            'youtube_url'       => 'nullable|url|max:255',
            'twitter_url'       => 'nullable|url|max:255',
            'tiktok_url'        => 'nullable|url|max:255',
            'admin_user_id'     => 'nullable|exists:users,id',
            'status'            => 'nullable|in:pending,approved,rejected',
            'is_featured'       => 'nullable|boolean',
        ]);

        // Generate unique slug
        $slug = Str::slug($validated['name']);
        $originalSlug = $slug;
        $counter = 1;
        while (Church::where('slug', $slug)->exists()) {
            $slug = $originalSlug . '-' . $counter++;
        }
        $validated['slug'] = $slug;
        $validated['created_by'] = Auth::id();
        $validated['status'] = $validated['status'] ?? 'pending';

        // Handle service_hours JSON
        if (isset($validated['service_hours']) && is_string($validated['service_hours'])) {
            $validated['service_hours'] = json_decode($validated['service_hours'], true);
        }

        // Handle file uploads
        if ($request->hasFile('logo')) {
            $validated['logo'] = $request->file('logo')->store('churches/logos', 'public');
        }

        if ($request->hasFile('cover_photo')) {
            $validated['cover_photo'] = $request->file('cover_photo')->store('churches/covers', 'public');
        }

        $church = Church::create($validated);

        // If admin_user_id is set, update that user's church_id
        if (!empty($validated['admin_user_id'])) {
            \App\Models\User::where('id', $validated['admin_user_id'])->update(['church_id' => $church->id]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Church created successfully.',
            'data' => $church->load('admin:id,name,email'),
        ], 201);
    }

    /**
     * Show a single church for editing (admin).
     */
    public function show(Church $church): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $church->load('admin:id,name,email'),
        ]);
    }

    /**
     * Update a church (Super Admin or Church Admin for own church).
     */
    public function update(Request $request, Church $church): JsonResponse
    {
        $user = Auth::user();

        // Church Admin can only edit their own church
        if ($user->church_id && $user->church_id !== $church->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $validated = $request->validate([
            'name'              => 'sometimes|required|string|max:255',
            'email'             => 'nullable|email|max:255',
            'phone'             => 'nullable|string|max:50',
            'website'           => 'nullable|url|max:255',
            'address'           => 'nullable|string|max:500',
            'city'              => 'nullable|string|max:100',
            'state'             => 'nullable|string|max:100',
            'zip_code'          => 'nullable|string|max:20',
            'country'           => 'nullable|string|max:100',
            'latitude'          => 'nullable|numeric|between:-90,90',
            'longitude'         => 'nullable|numeric|between:-180,180',
            'service_hours'     => 'nullable',
            'denomination'      => 'nullable|string|max:100',
            'year_founded'      => 'nullable|integer|min:1000|max:' . date('Y'),
            'short_description' => 'nullable|string|max:500',
            'history'           => 'nullable|string',
            'mission_statement' => 'nullable|string|max:1000',
            'vision_statement'  => 'nullable|string|max:1000',
            'logo'              => 'nullable|image|mimes:jpeg,png,jpg,gif,webp,svg|max:2048',
            'cover_photo'       => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:5120',
            'primary_color'     => 'nullable|string|max:20',
            'secondary_color'   => 'nullable|string|max:20',
            'meta_title'        => 'nullable|string|max:70',
            'meta_description'  => 'nullable|string|max:160',
            'facebook_url'      => 'nullable|url|max:255',
            'instagram_url'     => 'nullable|url|max:255',
            'youtube_url'       => 'nullable|url|max:255',
            'twitter_url'       => 'nullable|url|max:255',
            'tiktok_url'        => 'nullable|url|max:255',
            'admin_user_id'     => 'nullable|exists:users,id',
            'status'            => 'nullable|in:pending,approved,rejected',
            'is_featured'       => 'nullable|boolean',
        ]);

        // Re-generate slug if name changed
        if (isset($validated['name']) && $validated['name'] !== $church->name) {
            $slug = Str::slug($validated['name']);
            $originalSlug = $slug;
            $counter = 1;
            while (Church::where('slug', $slug)->where('id', '!=', $church->id)->exists()) {
                $slug = $originalSlug . '-' . $counter++;
            }
            $validated['slug'] = $slug;
        }

        // Handle service_hours JSON
        if (isset($validated['service_hours']) && is_string($validated['service_hours'])) {
            $validated['service_hours'] = json_decode($validated['service_hours'], true);
        }

        // Handle file uploads
        if ($request->hasFile('logo')) {
            if ($church->logo) {
                Storage::disk('public')->delete($church->logo);
            }
            $validated['logo'] = $request->file('logo')->store('churches/logos', 'public');
        }

        if ($request->hasFile('cover_photo')) {
            if ($church->cover_photo) {
                Storage::disk('public')->delete($church->cover_photo);
            }
            $validated['cover_photo'] = $request->file('cover_photo')->store('churches/covers', 'public');
        }

        $church->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Church updated successfully.',
            'data' => $church->fresh()->load('admin:id,name,email'),
        ]);
    }

    /**
     * Upload a document for a church.
     */
    public function uploadDocument(Request $request, Church $church): JsonResponse
    {
        $request->validate([
            'document' => 'required|file|mimes:pdf,doc,docx|max:10240',
            'document_name' => 'required|string|max:255',
        ]);

        $path = $request->file('document')->store('churches/documents', 'public');

        $documents = $church->documents ?? [];
        $documents[] = [
            'name' => $request->document_name,
            'file_path' => $path,
            'uploaded_at' => now()->toDateTimeString(),
        ];

        $church->update(['documents' => $documents]);

        return response()->json([
            'success' => true,
            'message' => 'Document uploaded successfully.',
            'data' => $church->fresh(),
        ]);
    }

    /**
     * Delete a document from a church.
     */
    public function deleteDocument(Request $request, Church $church): JsonResponse
    {
        $request->validate(['index' => 'required|integer|min:0']);

        $documents = $church->documents ?? [];
        $index = $request->index;

        if (isset($documents[$index])) {
            Storage::disk('public')->delete($documents[$index]['file_path']);
            array_splice($documents, $index, 1);
            $church->update(['documents' => $documents]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Document deleted successfully.',
            'data' => $church->fresh(),
        ]);
    }

    /**
     * Approve or reject a church. Sends notification to the church admin.
     */
    public function updateStatus(Request $request, Church $church): JsonResponse
    {
        $validated = $request->validate([
            'status'          => 'required|in:approved,rejected,pending',
            'rejection_notes' => 'nullable|string|max:1000',
        ]);

        $church->update([
            'status'          => $validated['status'],
            'rejection_notes' => $validated['rejection_notes'] ?? null,
        ]);

        // Notify the assigned church admin
        if (in_array($validated['status'], ['approved', 'rejected'], true)) {
            $admin = $church->admin;
            if ($admin) {
                try {
                    $admin->notify(new ChurchStatusChanged(
                        $church,
                        $validated['status'],
                        $validated['rejection_notes'] ?? null
                    ));
                } catch (\Throwable $e) {
                    // Don't fail the request if notification fails
                }
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Church status updated to ' . $validated['status'] . '.',
            'data' => $church->fresh(),
        ]);
    }

    /**
     * Import churches from a CSV file.
     * POST /api/churches/admin/import-csv
     */
    public function importCsv(Request $request): JsonResponse
    {
        $request->validate([
            'file' => 'required|file|mimes:csv,txt|max:10240',
        ]);

        $service = app(ChurchImportService::class);
        $result = $service->import($request->file('file'));

        return response()->json([
            'success' => true,
            'message' => "Import complete. Created: {$result['created']}, Skipped: {$result['skipped']}.",
            'data'    => $result,
        ]);
    }

    /**
     * Download a sample CSV template for church import.
     * GET /api/churches/admin/import-csv/sample
     */
    public function sampleCsv(): \Illuminate\Http\Response
    {
        $service = app(ChurchImportService::class);
        $csv = $service->generateSampleCsv();

        return response($csv, 200, [
            'Content-Type'        => 'text/csv',
            'Content-Disposition' => 'attachment; filename="church-import-template.csv"',
        ]);
    }

    /**
     * Toggle featured status.
     */
    public function toggleFeatured(Church $church): JsonResponse
    {
        $church->update(['is_featured' => !$church->is_featured]);

        return response()->json([
            'success' => true,
            'message' => $church->is_featured ? 'Church marked as featured.' : 'Church removed from featured.',
            'data' => $church->fresh(),
        ]);
    }

    /**
     * Get the church for the current Church Admin user.
     */
    public function myChurch(): JsonResponse
    {
        $user = Auth::user();

        $church = Church::where('admin_user_id', $user->id)->first();

        if (!$church) {
            return response()->json([
                'success' => false,
                'message' => 'No church assigned to your account.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $church,
        ]);
    }

    /**
     * Remove the specified church.
     */
    public function destroy(Church $church): JsonResponse
    {
        if ($church->logo) {
            Storage::disk('public')->delete($church->logo);
        }
        if ($church->cover_photo) {
            Storage::disk('public')->delete($church->cover_photo);
        }
        if ($church->documents) {
            foreach ($church->documents as $doc) {
                Storage::disk('public')->delete($doc['file_path']);
            }
        }

        // Clear church_id from assigned users
        \App\Models\User::where('church_id', $church->id)->update(['church_id' => null]);

        $church->delete();

        return response()->json([
            'success' => true,
            'message' => 'Church deleted successfully.',
        ]);
    }

    /**
     * Get list of users for admin assignment dropdown.
     */
    public function availableAdmins(): JsonResponse
    {
        $users = \App\Models\User::select('id', 'name', 'email')
            ->orderBy('name')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $users,
        ]);
    }
}

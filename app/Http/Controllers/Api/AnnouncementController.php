<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Announcement;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AnnouncementController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Announcement::query()->orderBy('priority', 'desc')->latest();
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")->orWhere('content', 'like', "%{$search}%");
            });
        }
        return response()->json($query->paginate(15));
    }

    public function active(): JsonResponse
    {
        $announcements = Announcement::active()->orderBy('priority', 'desc')->get();
        return response()->json(['data' => $announcements]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'content' => 'nullable|string',
            'type' => 'nullable|in:general,urgent,event,blog',
            'link' => 'nullable|string|max:500',
            'is_active' => 'nullable|boolean',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'priority' => 'nullable|integer|min:0|max:100',
        ]);
        $announcement = Announcement::create($validated);
        return response()->json(['message' => 'Announcement created.', 'data' => $announcement], 201);
    }

    public function update(Request $request, Announcement $announcement): JsonResponse
    {
        $validated = $request->validate([
            'title' => 'sometimes|required|string|max:255',
            'content' => 'nullable|string',
            'type' => 'nullable|in:general,urgent,event,blog',
            'link' => 'nullable|string|max:500',
            'is_active' => 'nullable|boolean',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date',
            'priority' => 'nullable|integer|min:0|max:100',
        ]);
        $announcement->update($validated);
        return response()->json(['message' => 'Announcement updated.', 'data' => $announcement->fresh()]);
    }

    public function destroy(Announcement $announcement): JsonResponse
    {
        $announcement->delete();
        return response()->json(['message' => 'Announcement deleted.']);
    }
}

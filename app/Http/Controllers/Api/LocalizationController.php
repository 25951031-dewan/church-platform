<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Localization;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LocalizationController extends Controller
{
    /**
     * List all available localizations.
     */
    public function index(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => Localization::orderBy('name')->get(['id', 'name', 'language', 'updated_at']),
        ]);
    }

    /**
     * Get a single localization with all translation lines.
     */
    public function show(Localization $localization): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $localization,
        ]);
    }

    /**
     * Create a new localization.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'language' => 'required|string|max:10|unique:localizations,language',
        ]);

        // Start with default English lines
        $validated['lines'] = $this->getDefaultLines();

        $localization = Localization::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Localization created.',
            'data' => $localization,
        ], 201);
    }

    /**
     * Update localization lines.
     */
    public function update(Request $request, Localization $localization): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'sometimes|string|max:100',
            'lines' => 'sometimes|array',
        ]);

        $localization->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Localization updated.',
            'data' => $localization->fresh(),
        ]);
    }

    /**
     * Delete a localization.
     */
    public function destroy(Localization $localization): JsonResponse
    {
        if ($localization->language === 'en') {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete the default English localization.',
            ], 422);
        }

        $localization->delete();

        return response()->json([
            'success' => true,
            'message' => 'Localization deleted.',
        ]);
    }

    /**
     * Get translations for a specific language (public).
     */
    public function getTranslations(string $language): JsonResponse
    {
        $localization = Localization::where('language', $language)->first();

        if (!$localization) {
            return response()->json([
                'success' => true,
                'data' => $this->getDefaultLines(),
            ]);
        }

        return response()->json([
            'success' => true,
            'data' => $localization->lines ?? $this->getDefaultLines(),
        ]);
    }

    private function getDefaultLines(): array
    {
        return [
            // Navigation
            'nav.home' => 'Home',
            'nav.about' => 'About',
            'nav.sermons' => 'Sermons',
            'nav.events' => 'Events',
            'nav.ministries' => 'Ministries',
            'nav.blog' => 'Blog',
            'nav.contact' => 'Contact',
            'nav.give' => 'Give',
            'nav.login' => 'Login',
            'nav.register' => 'Register',
            'nav.more' => 'More',

            // Common
            'common.read_more' => 'Read More',
            'common.load_more' => 'Load More',
            'common.share' => 'Share',
            'common.search' => 'Search',
            'common.submit' => 'Submit',
            'common.cancel' => 'Cancel',
            'common.save' => 'Save',
            'common.delete' => 'Delete',
            'common.edit' => 'Edit',
            'common.back' => 'Back',
            'common.next' => 'Next',
            'common.previous' => 'Previous',
            'common.close' => 'Close',
            'common.view_all' => 'View All',

            // Homepage sections
            'home.welcome' => 'Welcome',
            'home.verse_of_day' => 'Verse of the Day',
            'home.todays_blessing' => "Today's Blessing",
            'home.latest_sermons' => 'Latest Sermons',
            'home.upcoming_events' => 'Upcoming Events',
            'home.latest_posts' => 'Latest Posts',
            'home.prayer_requests' => 'Prayer Requests',
            'home.testimonies' => 'Testimonies',
            'home.our_ministries' => 'Our Ministries',
            'home.photo_gallery' => 'Photo Gallery',
            'home.newsletter' => 'Newsletter',
            'home.quick_contact' => 'Quick Contact',

            // Sermons
            'sermons.title' => 'Sermons',
            'sermons.watch' => 'Watch',
            'sermons.listen' => 'Listen',
            'sermons.notes' => 'Notes',
            'sermons.speaker' => 'Speaker',
            'sermons.series' => 'Series',
            'sermons.date' => 'Date',

            // Events
            'events.title' => 'Events',
            'events.register' => 'Register',
            'events.location' => 'Location',
            'events.date_time' => 'Date & Time',
            'events.spots_left' => 'spots left',

            // Prayer
            'prayer.submit_request' => 'Submit Prayer Request',
            'prayer.prayed' => 'Prayed',
            'prayer.pray_for_this' => 'Pray for this',

            // Contact
            'contact.title' => 'Contact Us',
            'contact.name' => 'Name',
            'contact.email' => 'Email',
            'contact.phone' => 'Phone',
            'contact.message' => 'Message',
            'contact.send' => 'Send Message',
            'contact.success' => 'Message sent successfully!',

            // Auth
            'auth.login' => 'Login',
            'auth.register' => 'Register',
            'auth.logout' => 'Logout',
            'auth.email' => 'Email',
            'auth.password' => 'Password',
            'auth.confirm_password' => 'Confirm Password',
            'auth.forgot_password' => 'Forgot Password?',
            'auth.remember_me' => 'Remember Me',
            'auth.no_account' => "Don't have an account?",
            'auth.have_account' => 'Already have an account?',

            // Footer
            'footer.service_times' => 'Service Times',
            'footer.quick_links' => 'Quick Links',
            'footer.connect' => 'Connect With Us',
            'footer.subscribe' => 'Subscribe to Newsletter',

            // Mobile
            'mobile.install_app' => 'Install App',
            'mobile.add_home' => 'Add to Home Screen',
            'mobile.pull_refresh' => 'Pull to refresh',
        ];
    }
}

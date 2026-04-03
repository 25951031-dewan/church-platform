@extends('installer.layout')

@section('content')
<div class="p-8">
    <div class="text-center mb-8">
        <div class="inline-flex items-center justify-center w-12 h-12 bg-indigo-50 rounded-xl mb-4">
            <svg class="w-6 h-6 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
            </svg>
        </div>
        <h2 class="text-2xl font-bold text-gray-800">Church Information</h2>
        <p class="text-gray-500 mt-2">Tell us about your church</p>
    </div>

    @if($errors->any())
        <div class="mb-6 bg-red-50 border border-red-200 rounded-xl p-4">
            <div class="flex items-start">
                <svg class="w-5 h-5 text-red-500 mt-0.5 mr-3 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <ul class="text-sm text-red-600 space-y-1">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        </div>
    @endif

    <form action="{{ url('/install/church') }}" method="POST" class="space-y-5">
        @csrf

        <div>
            <label for="church_name" class="block text-sm font-semibold text-gray-700 mb-1.5">Church Name <span class="text-red-500">*</span></label>
            <input type="text" name="church_name" id="church_name" value="{{ old('church_name') }}" required
                   class="w-full px-4 py-2.5 border border-gray-200 rounded-xl text-gray-700 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-colors bg-gray-50 focus:bg-white"
                   placeholder="Grace Community Church">
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
            <div>
                <label for="church_email" class="block text-sm font-semibold text-gray-700 mb-1.5">Church Email</label>
                <input type="email" name="church_email" id="church_email" value="{{ old('church_email') }}"
                       class="w-full px-4 py-2.5 border border-gray-200 rounded-xl text-gray-700 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-colors bg-gray-50 focus:bg-white"
                       placeholder="info@church.com">
            </div>
            <div>
                <label for="church_phone" class="block text-sm font-semibold text-gray-700 mb-1.5">Phone Number</label>
                <input type="text" name="church_phone" id="church_phone" value="{{ old('church_phone') }}"
                       class="w-full px-4 py-2.5 border border-gray-200 rounded-xl text-gray-700 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-colors bg-gray-50 focus:bg-white"
                       placeholder="+1 (555) 123-4567">
            </div>
        </div>

        <div>
            <label for="church_address" class="block text-sm font-semibold text-gray-700 mb-1.5">Address</label>
            <input type="text" name="church_address" id="church_address" value="{{ old('church_address') }}"
                   class="w-full px-4 py-2.5 border border-gray-200 rounded-xl text-gray-700 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-colors bg-gray-50 focus:bg-white"
                   placeholder="123 Faith Street, City, State 12345">
        </div>

        <div>
            <label for="church_description" class="block text-sm font-semibold text-gray-700 mb-1.5">Church Description</label>
            <textarea name="church_description" id="church_description" rows="3"
                      class="w-full px-4 py-2.5 border border-gray-200 rounded-xl text-gray-700 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-colors bg-gray-50 focus:bg-white"
                      placeholder="A brief description of your church...">{{ old('church_description') }}</textarea>
        </div>

        <div class="border-t border-gray-100 pt-5">
            <h3 class="text-sm font-semibold text-gray-500 uppercase tracking-wider mb-4">Social Media (Optional)</h3>
            <div class="space-y-4">
                <div>
                    <label for="facebook_url" class="block text-sm font-semibold text-gray-700 mb-1.5">Facebook URL</label>
                    <input type="url" name="facebook_url" id="facebook_url" value="{{ old('facebook_url') }}"
                           class="w-full px-4 py-2.5 border border-gray-200 rounded-xl text-gray-700 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-colors bg-gray-50 focus:bg-white"
                           placeholder="https://facebook.com/yourchurch">
                </div>
                <div>
                    <label for="youtube_url" class="block text-sm font-semibold text-gray-700 mb-1.5">YouTube URL</label>
                    <input type="url" name="youtube_url" id="youtube_url" value="{{ old('youtube_url') }}"
                           class="w-full px-4 py-2.5 border border-gray-200 rounded-xl text-gray-700 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-colors bg-gray-50 focus:bg-white"
                           placeholder="https://youtube.com/@yourchurch">
                </div>
                <div>
                    <label for="instagram_url" class="block text-sm font-semibold text-gray-700 mb-1.5">Instagram URL</label>
                    <input type="url" name="instagram_url" id="instagram_url" value="{{ old('instagram_url') }}"
                           class="w-full px-4 py-2.5 border border-gray-200 rounded-xl text-gray-700 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-colors bg-gray-50 focus:bg-white"
                           placeholder="https://instagram.com/yourchurch">
                </div>
            </div>
        </div>

        <div class="flex items-center justify-between pt-2">
            <a href="{{ url('/install/admin') }}"
               class="inline-flex items-center px-4 py-2.5 text-gray-600 hover:text-gray-800 font-medium rounded-xl hover:bg-gray-100 transition-colors">
                <svg class="w-5 h-5 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M11 17l-5-5m0 0l5-5m-5 5h12" />
                </svg>
                Back
            </a>
            <button type="submit"
                    class="inline-flex items-center px-6 py-3 bg-indigo-600 hover:bg-indigo-700 text-white font-semibold rounded-xl transition-all duration-200 shadow-lg shadow-indigo-200 hover:shadow-xl hover:shadow-indigo-300 hover:-translate-y-0.5">
                Continue
                <svg class="w-5 h-5 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M13 7l5 5m0 0l-5 5m5-5H6" />
                </svg>
            </button>
        </div>
    </form>
</div>
@endsection

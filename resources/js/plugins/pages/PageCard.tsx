import React from 'react'

interface Page {
  id: number
  name: string
  slug: string
  description?: string
  profile_image?: string
  cover_image?: string
  is_verified: boolean
  approved_members_count: number
}

interface PageCardProps {
  page: Page
  onFollow?: (id: number) => void
  isFollowing?: boolean
}

export function PageCard({ page, onFollow, isFollowing }: PageCardProps) {
  return (
    <div className="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden hover:shadow-md transition-shadow">
      <div className="h-20 bg-gradient-to-br from-blue-400 to-indigo-500 overflow-hidden">
        {page.cover_image && (
          <img src={page.cover_image} alt="" className="w-full h-full object-cover" />
        )}
      </div>

      <div className="p-4 -mt-6">
        <div className="flex items-end gap-3 mb-3">
          <div className="w-12 h-12 rounded-xl border-2 border-white shadow bg-white overflow-hidden flex-shrink-0">
            {page.profile_image ? (
              <img src={page.profile_image} alt={page.name} className="w-full h-full object-cover" />
            ) : (
              <div className="w-full h-full bg-blue-100 flex items-center justify-center">
                <span className="text-blue-600 font-bold text-lg">{page.name[0]}</span>
              </div>
            )}
          </div>
          <div className="flex-1 min-w-0 pb-1">
            <div className="flex items-center gap-1">
              <h3 className="font-semibold text-gray-900 truncate text-sm">{page.name}</h3>
              {page.is_verified && (
                <svg className="w-4 h-4 text-blue-500 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                  <path fillRule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clipRule="evenodd" />
                </svg>
              )}
            </div>
            <p className="text-xs text-gray-500">
              {page.approved_members_count?.toLocaleString() ?? 0} followers
            </p>
          </div>
        </div>

        {page.description && (
          <p className="text-sm text-gray-600 line-clamp-2 mb-3">{page.description}</p>
        )}

        {onFollow && (
          <button
            onClick={() => onFollow(page.id)}
            className={`w-full py-1.5 rounded-lg text-sm font-medium transition-colors ${
              isFollowing
                ? 'bg-gray-100 text-gray-700 hover:bg-gray-200'
                : 'bg-blue-600 text-white hover:bg-blue-700'
            }`}
          >
            {isFollowing ? 'Following' : 'Follow'}
          </button>
        )}
      </div>
    </div>
  )
}

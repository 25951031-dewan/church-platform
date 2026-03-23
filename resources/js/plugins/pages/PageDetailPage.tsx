import React, { useState } from 'react'
import { useParams, useNavigate } from 'react-router-dom'
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query'
import axios from 'axios'

function PageFeed({ page }: { page: any }) {
  const { data: feedData, isLoading: feedLoading } = useQuery({
    queryKey: ['page-feed', page?.id],
    queryFn: () => axios.get(`/api/v1/feed/page/${page.id}`).then(r => r.data),
    enabled: !!page?.id,
  })

  return (
    <div className="px-6 pt-2 pb-6 border-t border-gray-100">
      <h2 className="text-base font-semibold text-gray-900 mb-4">Posts</h2>
      {feedLoading && <p className="text-sm text-gray-400 text-center py-8">Loading posts…</p>}
      {!feedLoading && feedData?.data?.length === 0 && (
        <p className="text-sm text-gray-400 text-center py-8">No posts yet.</p>
      )}
      {feedData?.data?.map((post: any) => (
        <div key={post.id} className="mb-4 p-4 bg-gray-50 rounded-xl">
          <div className="flex items-center gap-2 mb-2">
            <img
              src={post.entity_actor?.profile_image ?? `https://ui-avatars.com/api/?name=${encodeURIComponent(page.name)}`}
              className="w-8 h-8 rounded-lg object-cover"
              alt=""
            />
            <div>
              <p className="text-sm font-semibold text-gray-900">{page.name}</p>
              <p className="text-xs text-gray-400">{new Date(post.created_at).toLocaleDateString()}</p>
            </div>
          </div>
          {post.body && <p className="text-sm text-gray-700 leading-relaxed">{post.body}</p>}
        </div>
      ))}
    </div>
  )
}

export function PageDetailPage() {
  const { slug }   = useParams<{ slug: string }>()
  const navigate   = useNavigate()
  const qc         = useQueryClient()
  const [following, setFollowing] = useState(false)

  const { data: page, isLoading, isError } = useQuery({
    queryKey: ['page', slug],
    queryFn:  () => axios.get(`/api/v1/pages/${slug}`).then(r => r.data),
  })

  const followMutation = useMutation({
    mutationFn: () =>
      following
        ? axios.delete(`/api/v1/pages/${page.id}/follow`)
        : axios.post(`/api/v1/pages/${page.id}/follow`),
    onSuccess: () => {
      setFollowing(f => !f)
      qc.invalidateQueries({ queryKey: ['page', slug] })
    },
  })

  if (isLoading) {
    return (
      <div className="max-w-3xl mx-auto">
        <div className="h-48 bg-gray-200 rounded-b-xl animate-pulse" />
        <div className="px-6 pt-4 space-y-3">
          <div className="h-6 bg-gray-200 rounded w-1/3 animate-pulse" />
          <div className="h-4 bg-gray-100 rounded w-1/2 animate-pulse" />
        </div>
      </div>
    )
  }

  if (isError || !page) {
    return (
      <div className="max-w-3xl mx-auto px-6 py-16 text-center">
        <p className="text-gray-500">Page not found.</p>
        <button onClick={() => navigate('/pages')} className="mt-4 text-blue-600 hover:underline text-sm">
          ← Back to pages
        </button>
      </div>
    )
  }

  return (
    <div className="max-w-3xl mx-auto">
      {/* Cover image */}
      <div className="h-52 bg-gradient-to-br from-blue-500 to-indigo-600 rounded-b-xl overflow-hidden">
        {page.cover_image && (
          <img src={page.cover_image} alt="" className="w-full h-full object-cover" />
        )}
      </div>

      {/* Header row */}
      <div className="px-6 pb-4 -mt-12 flex items-end gap-4">
        <div className="w-24 h-24 rounded-2xl border-4 border-white shadow-md bg-white overflow-hidden flex-shrink-0">
          {page.profile_image ? (
            <img src={page.profile_image} alt={page.name} className="w-full h-full object-cover" />
          ) : (
            <div className="w-full h-full bg-blue-100 flex items-center justify-center">
              <span className="text-4xl font-bold text-blue-600">{page.name[0]}</span>
            </div>
          )}
        </div>

        <div className="flex-1 pt-14">
          <div className="flex items-center gap-2 flex-wrap">
            <h1 className="text-2xl font-bold text-gray-900">{page.name}</h1>
            {page.is_verified && (
              <span className="inline-flex items-center gap-1 text-blue-600 text-sm font-medium bg-blue-50 px-2 py-0.5 rounded-full">
                <svg className="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 20 20">
                  <path fillRule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clipRule="evenodd" />
                </svg>
                Verified
              </span>
            )}
          </div>
          <p className="text-gray-500 text-sm mt-0.5">
            {page.approved_members_count?.toLocaleString() ?? 0} followers
          </p>
        </div>

        <button
          onClick={() => followMutation.mutate()}
          disabled={followMutation.isPending}
          className={`px-5 py-2 rounded-xl font-medium text-sm transition-colors ${
            following
              ? 'bg-gray-100 text-gray-700 hover:bg-gray-200'
              : 'bg-blue-600 text-white hover:bg-blue-700'
          }`}
        >
          {following ? 'Following' : 'Follow'}
        </button>
      </div>

      {/* About */}
      {page.description && (
        <div className="px-6 py-4 border-t border-gray-100">
          <p className="text-gray-700 leading-relaxed">{page.description}</p>
        </div>
      )}

      {/* Contact info */}
      {(page.website || page.address || page.phone) && (
        <div className="px-6 py-4 border-t border-gray-100 grid sm:grid-cols-2 gap-3 text-sm text-gray-600">
          {page.website && (
            <a
              href={page.website}
              target="_blank"
              rel="noreferrer"
              className="flex items-center gap-2 text-blue-600 hover:underline"
            >
              🌐 {page.website}
            </a>
          )}
          {page.address && <span className="flex items-center gap-2">📍 {page.address}</span>}
          {page.phone   && <span className="flex items-center gap-2">📞 {page.phone}</span>}
        </div>
      )}

      {/* Entity-scoped feed */}
      <PageFeed page={page} />
    </div>
  )
}

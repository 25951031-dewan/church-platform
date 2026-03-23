import React, { useState } from 'react'
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query'
import { useNavigate } from 'react-router-dom'
import axios from 'axios'
import { PageCard } from './PageCard'

export function PagesPage() {
  const [search, setSearch]     = useState('')
  const [followed, setFollowed] = useState<Set<number>>(new Set())
  const qc                      = useQueryClient()
  const navigate                = useNavigate()

  const { data, isLoading } = useQuery({
    queryKey: ['pages', search],
    queryFn:  () =>
      axios.get('/api/v1/pages', { params: { search: search || undefined } }).then(r => r.data),
  })

  const followMutation = useMutation({
    mutationFn: (id: number) =>
      followed.has(id)
        ? axios.delete(`/api/v1/pages/${id}/follow`)
        : axios.post(`/api/v1/pages/${id}/follow`),
    onSuccess: (_, id) => {
      setFollowed(prev => {
        const next = new Set(prev)
        next.has(id) ? next.delete(id) : next.add(id)
        return next
      })
      qc.invalidateQueries({ queryKey: ['pages'] })
    },
  })

  return (
    <div className="max-w-5xl mx-auto px-4 py-6">
      <div className="flex items-center justify-between mb-6">
        <div>
          <h1 className="text-2xl font-bold text-gray-900">Ministry Pages</h1>
          <p className="text-sm text-gray-500 mt-0.5">Follow church departments and ministries</p>
        </div>
        <button
          onClick={() => navigate('/pages/create')}
          className="px-4 py-2 bg-blue-600 text-white rounded-lg text-sm font-medium hover:bg-blue-700 transition-colors"
        >
          + Create Page
        </button>
      </div>

      <input
        type="search"
        placeholder="Search ministry pages…"
        value={search}
        onChange={e => setSearch(e.target.value)}
        className="w-full mb-6 px-4 py-2.5 border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500 text-sm"
      />

      {isLoading && (
        <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
          {[...Array(6)].map((_, i) => (
            <div key={i} className="h-44 bg-gray-100 rounded-xl animate-pulse" />
          ))}
        </div>
      )}

      {!isLoading && (
        <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
          {data?.data?.map((page: any) => (
            <div key={page.id} onClick={() => navigate(`/pages/${page.slug}`)} className="cursor-pointer">
              <PageCard
                page={page}
                isFollowing={followed.has(page.id)}
                onFollow={(id) => {
                  followMutation.mutate(id)
                }}
              />
            </div>
          ))}
        </div>
      )}

      {!isLoading && data?.data?.length === 0 && (
        <div className="text-center py-16 text-gray-500">
          <p className="text-lg">No pages found.</p>
          <p className="text-sm mt-1">Be the first to create a ministry page.</p>
        </div>
      )}
    </div>
  )
}

import {useState, useEffect} from 'react';
import {useParams, useNavigate} from 'react-router';
import {
  useArticle,
  useCreateArticle,
  useUpdateArticle,
  useArticleCategories,
  useTags,
  useCreateTag,
} from '../queries';
import {TiptapEditor} from '../components/TiptapEditor';

export function ArticleEditorPage() {
  const {slug} = useParams<{slug?: string}>();
  const isEditing = !!slug;
  const navigate = useNavigate();

  const {data: existing, isLoading} = useArticle(slug ?? '');
  const {data: categories} = useArticleCategories();
  const {data: allTags} = useTags();

  const createArticle = useCreateArticle();
  const updateArticle = useUpdateArticle(slug ?? '');
  const createTag = useCreateTag();

  const [title, setTitle] = useState('');
  const [content, setContent] = useState('');
  const [excerpt, setExcerpt] = useState('');
  const [coverImage, setCoverImage] = useState('');
  const [categoryId, setCategoryId] = useState<number | ''>('');
  const [selectedTagIds, setSelectedTagIds] = useState<number[]>([]);
  const [status, setStatus] = useState<'draft' | 'published' | 'scheduled'>('draft');
  const [publishedAt, setPublishedAt] = useState('');
  const [metaTitle, setMetaTitle] = useState('');
  const [metaDescription, setMetaDescription] = useState('');
  const [newTagName, setNewTagName] = useState('');

  useEffect(() => {
    if (existing) {
      setTitle(existing.title);
      setContent(existing.content ?? '');
      setExcerpt(existing.excerpt ?? '');
      setCoverImage(existing.cover_image ?? '');
      setCategoryId(existing.category_id ?? '');
      setSelectedTagIds(existing.tags.map(t => t.id));
      setStatus(existing.status);
      setPublishedAt(existing.published_at?.slice(0, 16) ?? '');
      setMetaTitle(existing.meta_title ?? '');
      setMetaDescription(existing.meta_description ?? '');
    }
  }, [existing]);

  function toggleTag(tagId: number) {
    setSelectedTagIds(prev =>
      prev.includes(tagId) ? prev.filter(id => id !== tagId) : [...prev, tagId]
    );
  }

  async function handleAddTag() {
    const name = newTagName.trim();
    if (!name) return;
    const tag = await createTag.mutateAsync({name});
    setSelectedTagIds(prev => [...prev, tag.id]);
    setNewTagName('');
  }

  function handleSubmit(e: React.FormEvent) {
    e.preventDefault();

    const payload = {
      title,
      content,
      excerpt: excerpt || undefined,
      cover_image: coverImage || undefined,
      category_id: categoryId || undefined,
      tag_ids: selectedTagIds,
      status,
      published_at: status === 'scheduled' ? publishedAt : undefined,
      meta_title: metaTitle || undefined,
      meta_description: metaDescription || undefined,
    };

    if (isEditing) {
      updateArticle.mutate(payload, {
        onSuccess: (article) => navigate(`/blog/${article.slug}`),
      });
    } else {
      createArticle.mutate(payload as any, {
        onSuccess: (article) => navigate(`/blog/${article.slug}`),
      });
    }
  }

  if (isEditing && isLoading) {
    return <div className="flex items-center justify-center h-64">Loading...</div>;
  }

  const isPending = createArticle.isPending || updateArticle.isPending;

  return (
    <form onSubmit={handleSubmit} className="max-w-4xl mx-auto px-4 py-6">
      <div className="flex items-center justify-between mb-6">
        <h1 className="text-2xl font-bold text-gray-900 dark:text-white">
          {isEditing ? 'Edit Article' : 'New Article'}
        </h1>
        <div className="flex items-center gap-3">
          <select
            value={status}
            onChange={e => setStatus(e.target.value as typeof status)}
            className="border border-gray-300 dark:border-gray-600 rounded-lg px-3 py-2 text-sm bg-white dark:bg-gray-800 text-gray-900 dark:text-white"
          >
            <option value="draft">Draft</option>
            <option value="published">Published</option>
            <option value="scheduled">Scheduled</option>
          </select>
          <button
            type="submit"
            disabled={isPending || !title}
            className="px-5 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700 disabled:opacity-50 text-sm font-medium"
          >
            {isPending ? 'Saving...' : isEditing ? 'Save Changes' : 'Create Article'}
          </button>
        </div>
      </div>

      <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {/* Main content */}
        <div className="lg:col-span-2 space-y-5">
          <div>
            <label className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
              Title <span className="text-red-500">*</span>
            </label>
            <input
              type="text"
              required
              value={title}
              onChange={e => setTitle(e.target.value)}
              placeholder="Article title..."
              className="w-full border border-gray-300 dark:border-gray-600 rounded-lg px-3 py-2 text-gray-900 dark:text-white bg-white dark:bg-gray-800 text-lg font-medium placeholder-gray-400 focus:ring-2 focus:ring-primary-500 focus:border-transparent"
            />
          </div>

          <div>
            <label className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
              Content
            </label>
            <TiptapEditor
              content={content}
              onChange={setContent}
              placeholder="Write your article..."
            />
          </div>

          <div>
            <label className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
              Excerpt
            </label>
            <textarea
              value={excerpt}
              onChange={e => setExcerpt(e.target.value)}
              rows={3}
              placeholder="Short summary..."
              className="w-full border border-gray-300 dark:border-gray-600 rounded-lg px-3 py-2 text-sm text-gray-900 dark:text-white bg-white dark:bg-gray-800 placeholder-gray-400 focus:ring-2 focus:ring-primary-500 focus:border-transparent"
            />
          </div>
        </div>

        {/* Sidebar */}
        <div className="space-y-5">
          {/* Publish date (scheduled only) */}
          {status === 'scheduled' && (
            <div>
              <label className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                Publish Date
              </label>
              <input
                type="datetime-local"
                value={publishedAt}
                onChange={e => setPublishedAt(e.target.value)}
                className="w-full border border-gray-300 dark:border-gray-600 rounded-lg px-3 py-2 text-sm text-gray-900 dark:text-white bg-white dark:bg-gray-800"
              />
            </div>
          )}

          {/* Cover image */}
          <div>
            <label className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
              Cover Image URL
            </label>
            <input
              type="url"
              value={coverImage}
              onChange={e => setCoverImage(e.target.value)}
              placeholder="https://..."
              className="w-full border border-gray-300 dark:border-gray-600 rounded-lg px-3 py-2 text-sm text-gray-900 dark:text-white bg-white dark:bg-gray-800 placeholder-gray-400 focus:ring-2 focus:ring-primary-500 focus:border-transparent"
            />
            {coverImage && (
              <img src={coverImage} alt="" className="mt-2 rounded-lg aspect-video object-cover w-full" />
            )}
          </div>

          {/* Category */}
          <div>
            <label className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
              Category
            </label>
            <select
              value={categoryId}
              onChange={e => setCategoryId(e.target.value ? Number(e.target.value) : '')}
              className="w-full border border-gray-300 dark:border-gray-600 rounded-lg px-3 py-2 text-sm bg-white dark:bg-gray-800 text-gray-900 dark:text-white"
            >
              <option value="">No category</option>
              {categories?.map(cat => (
                <option key={cat.id} value={cat.id}>{cat.name}</option>
              ))}
            </select>
          </div>

          {/* Tags */}
          <div>
            <label className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
              Tags
            </label>
            <div className="flex flex-wrap gap-1.5 mb-2">
              {allTags?.map(tag => (
                <button
                  key={tag.id}
                  type="button"
                  onClick={() => toggleTag(tag.id)}
                  className={`px-2.5 py-1 text-xs rounded-full border transition-colors ${
                    selectedTagIds.includes(tag.id)
                      ? 'bg-primary-600 text-white border-primary-600'
                      : 'bg-white dark:bg-gray-800 text-gray-600 dark:text-gray-400 border-gray-300 dark:border-gray-600'
                  }`}
                >
                  #{tag.name}
                </button>
              ))}
            </div>
            <div className="flex gap-2">
              <input
                type="text"
                value={newTagName}
                onChange={e => setNewTagName(e.target.value)}
                placeholder="New tag..."
                className="flex-1 border border-gray-300 dark:border-gray-600 rounded-lg px-3 py-1.5 text-xs bg-white dark:bg-gray-800 text-gray-900 dark:text-white placeholder-gray-400"
                onKeyDown={e => e.key === 'Enter' && (e.preventDefault(), handleAddTag())}
              />
              <button
                type="button"
                onClick={handleAddTag}
                disabled={!newTagName.trim() || createTag.isPending}
                className="px-3 py-1.5 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-lg text-xs hover:bg-gray-200 dark:hover:bg-gray-600 disabled:opacity-50"
              >
                Add
              </button>
            </div>
          </div>

          {/* SEO */}
          <div>
            <label className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
              Meta Title
            </label>
            <input
              type="text"
              value={metaTitle}
              onChange={e => setMetaTitle(e.target.value)}
              maxLength={255}
              className="w-full border border-gray-300 dark:border-gray-600 rounded-lg px-3 py-2 text-sm bg-white dark:bg-gray-800 text-gray-900 dark:text-white placeholder-gray-400"
            />
          </div>
          <div>
            <label className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
              Meta Description
            </label>
            <textarea
              value={metaDescription}
              onChange={e => setMetaDescription(e.target.value)}
              rows={2}
              maxLength={500}
              className="w-full border border-gray-300 dark:border-gray-600 rounded-lg px-3 py-2 text-sm bg-white dark:bg-gray-800 text-gray-900 dark:text-white placeholder-gray-400"
            />
          </div>
        </div>
      </div>

      {(createArticle.isError || updateArticle.isError) && (
        <p className="mt-4 text-sm text-red-600 dark:text-red-400">
          Failed to save. Please check your inputs and try again.
        </p>
      )}
    </form>
  );
}

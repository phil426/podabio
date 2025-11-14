import { useEffect, useMemo, useRef, useState } from 'react';
import { LuX, LuSave, LuUpload, LuTrash2 } from 'react-icons/lu';

import { useBlogPost, useCreateBlogPostMutation, useUpdateBlogPostMutation, useBlogCategories } from '../../api/blog';
import type { BlogPost } from '../../api/blog';
import { type TabColorTheme } from '../layout/tab-colors';

import styles from './blog-post-inspector.module.css';

interface BlogPostInspectorProps {
  postId: number | null;
  activeColor: TabColorTheme;
  onClose: () => void;
}

export function BlogPostInspector({ postId, activeColor, onClose }: BlogPostInspectorProps): JSX.Element {
  const { data: postData } = useBlogPost(postId);
  const { data: categoriesData } = useBlogCategories();
  const createMutation = useCreateBlogPostMutation();
  const updateMutation = useUpdateBlogPostMutation();

  const post = postData?.post;
  const categories = categoriesData?.categories ?? [];
  const isNewPost = postId === null;

  const [title, setTitle] = useState('');
  const [slug, setSlug] = useState('');
  const [content, setContent] = useState('');
  const [excerpt, setExcerpt] = useState('');
  const [categoryId, setCategoryId] = useState<number | null>(null);
  const [published, setPublished] = useState(false);
  const [featuredImage, setFeaturedImage] = useState('');
  const [saveStatus, setSaveStatus] = useState<'idle' | 'saving' | 'success' | 'error'>('idle');
  const [errorMessage, setErrorMessage] = useState<string | null>(null);

  const titleInputRef = useRef<HTMLInputElement>(null);
  const slugInputRef = useRef<HTMLInputElement>(null);
  const contentTextareaRef = useRef<HTMLTextAreaElement>(null);

  // Auto-generate slug from title
  useEffect(() => {
    if (isNewPost && title && !slug) {
      const generatedSlug = title
        .toLowerCase()
        .trim()
        .replace(/[^a-z0-9]+/g, '-')
        .replace(/^-+|-+$/g, '');
      setSlug(generatedSlug);
    }
  }, [title, slug, isNewPost]);

  // Load post data
  useEffect(() => {
    if (post) {
      setTitle(post.title ?? '');
      setSlug(post.slug ?? '');
      setContent(post.content ?? '');
      setExcerpt(post.excerpt ?? '');
      setCategoryId(post.category_id ?? null);
      setPublished(post.published === 1);
      setFeaturedImage(post.featured_image ?? '');
      setSaveStatus('idle');
      setErrorMessage(null);
    } else if (isNewPost) {
      setTitle('');
      setSlug('');
      setContent('');
      setExcerpt('');
      setCategoryId(null);
      setPublished(false);
      setFeaturedImage('');
      setSaveStatus('idle');
      setErrorMessage(null);
    }
  }, [post, isNewPost]);

  const hasChanges = useMemo(() => {
    if (!post && !isNewPost) return false;
    if (isNewPost) {
      return title.trim() !== '' || content.trim() !== '';
    }
    return (
      title !== (post?.title ?? '') ||
      slug !== (post?.slug ?? '') ||
      content !== (post?.content ?? '') ||
      excerpt !== (post?.excerpt ?? '') ||
      categoryId !== (post?.category_id ?? null) ||
      published !== (post?.published === 1) ||
      featuredImage !== (post?.featured_image ?? '')
    );
  }, [post, isNewPost, title, slug, content, excerpt, categoryId, published, featuredImage]);

  const handleSave = async () => {
    if (!title.trim() || !slug.trim()) {
      setErrorMessage('Title and slug are required');
      setSaveStatus('error');
      return;
    }

    setSaveStatus('saving');
    setErrorMessage(null);

    try {
      if (isNewPost) {
        await createMutation.mutateAsync({
          title: title.trim(),
          slug: slug.trim(),
          content: content.trim(),
          excerpt: excerpt.trim() || undefined,
          category_id: categoryId,
          published,
          featured_image: featuredImage.trim() || undefined
        });
      } else {
        await updateMutation.mutateAsync({
          post_id: postId!,
          title: title.trim(),
          slug: slug.trim(),
          content: content.trim(),
          excerpt: excerpt.trim() || undefined,
          category_id: categoryId,
          published,
          featured_image: featuredImage.trim() || undefined
        });
      }
      setSaveStatus('success');
      setTimeout(() => {
        setSaveStatus('idle');
      }, 2000);
    } catch (error) {
      setSaveStatus('error');
      setErrorMessage(error instanceof Error ? error.message : 'Failed to save post');
    }
  };

  const handleReset = () => {
    if (post) {
      setTitle(post.title ?? '');
      setSlug(post.slug ?? '');
      setContent(post.content ?? '');
      setExcerpt(post.excerpt ?? '');
      setCategoryId(post.category_id ?? null);
      setPublished(post.published === 1);
      setFeaturedImage(post.featured_image ?? '');
    } else {
      setTitle('');
      setSlug('');
      setContent('');
      setExcerpt('');
      setCategoryId(null);
      setPublished(false);
      setFeaturedImage('');
    }
    setSaveStatus('idle');
    setErrorMessage(null);
  };

  return (
    <div
      className={styles.inspector}
      style={
        {
          '--active-tab-color': activeColor.text,
          '--active-tab-bg': activeColor.primary,
          '--active-tab-light': activeColor.light,
          '--active-tab-border': activeColor.border
        } as React.CSSProperties
      }
    >
      <div className={styles.header}>
        <h3>{isNewPost ? 'New Blog Post' : 'Edit Blog Post'}</h3>
        <button type="button" className={styles.closeButton} onClick={onClose} aria-label="Close">
          <LuX aria-hidden="true" />
        </button>
      </div>

      <div className={styles.content}>
        {errorMessage && (
          <div className={styles.errorBanner}>
            <p>{errorMessage}</p>
          </div>
        )}

        <div className={styles.fieldset}>
          <label htmlFor="blog-title" className={styles.label}>
            Title <span className={styles.required}>*</span>
          </label>
          <input
            ref={titleInputRef}
            id="blog-title"
            type="text"
            className={styles.input}
            value={title}
            onChange={(e) => setTitle(e.target.value)}
            placeholder="Enter post title"
          />
        </div>

        <div className={styles.fieldset}>
          <label htmlFor="blog-slug" className={styles.label}>
            Slug <span className={styles.required}>*</span>
          </label>
          <input
            ref={slugInputRef}
            id="blog-slug"
            type="text"
            className={styles.input}
            value={slug}
            onChange={(e) => setSlug(e.target.value)}
            placeholder="url-friendly-slug"
            pattern="[a-z0-9-]+"
          />
          <small className={styles.helpText}>Used in the URL (e.g., my-blog-post-title)</small>
        </div>

        <div className={styles.fieldset}>
          <label htmlFor="blog-category" className={styles.label}>
            Category
          </label>
          <select
            id="blog-category"
            className={styles.select}
            value={categoryId ?? ''}
            onChange={(e) => setCategoryId(e.target.value ? Number(e.target.value) : null)}
          >
            <option value="">No Category</option>
            {categories.map((category) => (
              <option key={category.id} value={category.id}>
                {category.name}
              </option>
            ))}
          </select>
        </div>

        <div className={styles.fieldset}>
          <label htmlFor="blog-excerpt" className={styles.label}>
            Excerpt
          </label>
          <textarea
            id="blog-excerpt"
            className={styles.textarea}
            rows={3}
            value={excerpt}
            onChange={(e) => setExcerpt(e.target.value)}
            placeholder="Brief summary of the post (optional)"
          />
        </div>

        <div className={styles.fieldset}>
          <label htmlFor="blog-content" className={styles.label}>
            Content <span className={styles.required}>*</span>
          </label>
          <textarea
            ref={contentTextareaRef}
            id="blog-content"
            className={styles.textarea}
            rows={12}
            value={content}
            onChange={(e) => setContent(e.target.value)}
            placeholder="Write your blog post content here..."
          />
        </div>

        <div className={styles.fieldset}>
          <label htmlFor="blog-featured-image" className={styles.label}>
            Featured Image URL
          </label>
          <input
            id="blog-featured-image"
            type="url"
            className={styles.input}
            value={featuredImage}
            onChange={(e) => setFeaturedImage(e.target.value)}
            placeholder="https://example.com/image.jpg"
          />
        </div>

        <div className={styles.fieldset}>
          <label className={styles.checkboxLabel}>
            <input
              type="checkbox"
              className={styles.checkbox}
              checked={published}
              onChange={(e) => setPublished(e.target.checked)}
            />
            <span>Publish this post</span>
          </label>
        </div>
      </div>

      <div className={styles.footer}>
        <button
          type="button"
          className={styles.resetButton}
          onClick={handleReset}
          disabled={!hasChanges || saveStatus === 'saving'}
        >
          Reset
        </button>
        <button
          type="button"
          className={styles.saveButton}
          onClick={handleSave}
          disabled={!title.trim() || !slug.trim() || saveStatus === 'saving'}
        >
          {saveStatus === 'saving' ? (
            <>
              <span>Saving...</span>
            </>
          ) : saveStatus === 'success' ? (
            <>
              <LuSave aria-hidden="true" />
              <span>Saved!</span>
            </>
          ) : (
            <>
              <LuSave aria-hidden="true" />
              <span>{isNewPost ? 'Create Post' : 'Save Changes'}</span>
            </>
          )}
        </button>
      </div>
    </div>
  );
}





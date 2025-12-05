/**
 * Blog CMS Admin Panel
 * Manage marketing blog posts
 */

import { useState, useCallback, useEffect } from 'react';
import { 
  Plus, 
  Pencil, 
  Trash, 
  Eye, 
  EyeSlash, 
  MagnifyingGlass,
  CircleNotch,
  FolderOpen,
  Newspaper,
  Check,
  X,
  Image as ImageIcon
} from '@phosphor-icons/react';

import {
  adminGetBlogData,
  createBlogPost,
  updateBlogPost,
  deleteBlogPost,
  createBlogCategory,
  updateBlogCategory,
  deleteBlogCategory,
  type BlogPost,
  type BlogCategory
} from '../../api/cms';

import styles from './cms.module.css';

type ViewMode = 'list' | 'editor';

export function BlogCMS(): JSX.Element {
  const [posts, setPosts] = useState<BlogPost[]>([]);
  const [categories, setCategories] = useState<BlogCategory[]>([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);
  const [viewMode, setViewMode] = useState<ViewMode>('list');
  const [editingPost, setEditingPost] = useState<BlogPost | null>(null);
  const [searchQuery, setSearchQuery] = useState('');
  const [filterCategory, setFilterCategory] = useState<number | null>(null);
  
  // Category management
  const [showCategoryModal, setShowCategoryModal] = useState(false);
  const [editingCategory, setEditingCategory] = useState<BlogCategory | null>(null);
  
  // Load data
  const loadData = useCallback(async () => {
    setLoading(true);
    setError(null);
    try {
      const response = await adminGetBlogData();
      if (response.success) {
        setPosts(response.posts);
        setCategories(response.categories);
      } else {
        setError(response.error ?? 'Failed to load data');
      }
    } catch (err) {
      setError(err instanceof Error ? err.message : 'Failed to load data');
    } finally {
      setLoading(false);
    }
  }, []);
  
  useEffect(() => {
    loadData();
  }, [loadData]);
  
  // Filter posts
  const filteredPosts = posts.filter(post => {
    const matchesSearch = !searchQuery || 
      post.title.toLowerCase().includes(searchQuery.toLowerCase()) ||
      post.content.toLowerCase().includes(searchQuery.toLowerCase());
    const matchesCategory = filterCategory === null || post.category_id === filterCategory;
    return matchesSearch && matchesCategory;
  });
  
  // Create new post
  const handleNewPost = () => {
    setEditingPost({
      id: 0,
      title: '',
      slug: '',
      content: '',
      excerpt: '',
      author_id: 0,
      category_id: null,
      tags: '',
      featured_image: '',
      published: 0,
      view_count: 0,
      created_at: '',
      updated_at: ''
    });
    setViewMode('editor');
  };
  
  // Edit post
  const handleEditPost = (post: BlogPost) => {
    setEditingPost(post);
    setViewMode('editor');
  };
  
  // Save post
  const handleSavePost = async (postData: {
    title: string;
    content: string;
    excerpt?: string;
    slug?: string;
    category_id?: number | null;
    tags?: string;
    featured_image?: string;
    published?: number;
  }) => {
    try {
      if (editingPost?.id) {
        await updateBlogPost(editingPost.id, {
          title: postData.title,
          content: postData.content,
          excerpt: postData.excerpt,
          slug: postData.slug,
          category_id: postData.category_id,
          tags: postData.tags,
          featured_image: postData.featured_image,
          published: postData.published
        });
      } else {
        await createBlogPost(postData);
      }
      await loadData();
      setViewMode('list');
      setEditingPost(null);
    } catch (err) {
      setError(err instanceof Error ? err.message : 'Failed to save post');
    }
  };
  
  // Delete post
  const handleDeletePost = async (id: number) => {
    if (!confirm('Are you sure you want to delete this post?')) return;
    
    try {
      await deleteBlogPost(id);
      await loadData();
    } catch (err) {
      setError(err instanceof Error ? err.message : 'Failed to delete post');
    }
  };
  
  // Toggle publish
  const handleTogglePublish = async (post: BlogPost) => {
    try {
      await updateBlogPost(post.id, { published: post.published ? 0 : 1 });
      await loadData();
    } catch (err) {
      setError(err instanceof Error ? err.message : 'Failed to update post');
    }
  };
  
  // Category handlers
  const handleSaveCategory = async (categoryData: { name: string; description?: string }) => {
    try {
      if (editingCategory?.id) {
        await updateBlogCategory(editingCategory.id, {
          name: categoryData.name,
          description: categoryData.description
        });
      } else {
        await createBlogCategory({ name: categoryData.name, description: categoryData.description });
      }
      await loadData();
      setShowCategoryModal(false);
      setEditingCategory(null);
    } catch (err) {
      setError(err instanceof Error ? err.message : 'Failed to save category');
    }
  };
  
  const handleDeleteCategory = async (id: number) => {
    if (!confirm('Are you sure you want to delete this category? Posts will be uncategorized.')) return;
    
    try {
      await deleteBlogCategory(id);
      await loadData();
    } catch (err) {
      setError(err instanceof Error ? err.message : 'Failed to delete category');
    }
  };
  
  if (loading) {
    return (
      <div className={styles.container}>
        <div className={styles.loading}>
          <CircleNotch size={32} weight="regular" className={styles.spinner} />
          <span>Loading blog posts...</span>
        </div>
      </div>
    );
  }
  
  if (viewMode === 'editor') {
    return (
      <PostEditor
        post={editingPost}
        categories={categories}
        onSave={handleSavePost}
        onCancel={() => { setViewMode('list'); setEditingPost(null); }}
      />
    );
  }
  
  return (
    <div className={styles.container}>
      <header className={styles.header}>
        <div>
          <h1>Blog Posts</h1>
          <p>Create and manage marketing content</p>
        </div>
        <div className={styles.headerActions}>
          <button
            type="button"
            className={styles.secondaryButton}
            onClick={() => { setEditingCategory(null); setShowCategoryModal(true); }}
          >
            <FolderOpen size={16} weight="regular" />
            Manage Categories
          </button>
          <button
            type="button"
            className={styles.primaryButton}
            onClick={handleNewPost}
          >
            <Plus size={16} weight="bold" />
            New Post
          </button>
        </div>
      </header>
      
      {error && (
        <div className={styles.errorBanner}>
          {error}
          <button type="button" onClick={() => setError(null)}><X size={16} /></button>
        </div>
      )}
      
      <div className={styles.toolbar}>
        <div className={styles.searchBox}>
          <MagnifyingGlass size={18} weight="regular" />
          <input
            type="text"
            placeholder="Search posts..."
            value={searchQuery}
            onChange={(e) => setSearchQuery(e.target.value)}
          />
        </div>
        <select
          value={filterCategory ?? ''}
          onChange={(e) => setFilterCategory(e.target.value ? Number(e.target.value) : null)}
          className={styles.filterSelect}
        >
          <option value="">All Categories</option>
          {categories.map(cat => (
            <option key={cat.id} value={cat.id}>{cat.name}</option>
          ))}
        </select>
      </div>
      
      <div className={styles.stats}>
        <div className={styles.statCard}>
          <Newspaper size={24} weight="regular" />
          <div>
            <span className={styles.statValue}>{posts.length}</span>
            <span className={styles.statLabel}>Total Posts</span>
          </div>
        </div>
        <div className={styles.statCard}>
          <Eye size={24} weight="regular" />
          <div>
            <span className={styles.statValue}>
              {posts.filter(p => p.published).length}
            </span>
            <span className={styles.statLabel}>Published</span>
          </div>
        </div>
        <div className={styles.statCard}>
          <FolderOpen size={24} weight="regular" />
          <div>
            <span className={styles.statValue}>{categories.length}</span>
            <span className={styles.statLabel}>Categories</span>
          </div>
        </div>
      </div>
      
      <div className={styles.postGrid}>
        {filteredPosts.length === 0 ? (
          <div className={styles.emptyState}>
            <Newspaper size={48} weight="regular" />
            <p>No posts found</p>
            <button type="button" className={styles.primaryButton} onClick={handleNewPost}>
              Create your first post
            </button>
          </div>
        ) : (
          filteredPosts.map(post => (
            <div key={post.id} className={styles.postCard}>
              {post.featured_image ? (
                <div className={styles.postImage}>
                  <img src={post.featured_image} alt="" />
                </div>
              ) : (
                <div className={styles.postImagePlaceholder}>
                  <ImageIcon size={32} weight="regular" />
                </div>
              )}
              <div className={styles.postContent}>
                <h3>{post.title}</h3>
                {post.excerpt && <p className={styles.postExcerpt}>{post.excerpt}</p>}
                <div className={styles.postMeta}>
                  {post.category_name && (
                    <span className={styles.categoryBadge}>{post.category_name}</span>
                  )}
                  <span className={`${styles.statusBadge} ${post.published ? styles.published : styles.draft}`}>
                    {post.published ? 'Published' : 'Draft'}
                  </span>
                </div>
                <div className={styles.postFooter}>
                  <span className={styles.viewCount}>{post.view_count} views</span>
                  <span className={styles.date}>
                    {new Date(post.created_at).toLocaleDateString()}
                  </span>
                </div>
              </div>
              <div className={styles.postActions}>
                <button
                  type="button"
                  className={styles.iconButton}
                  onClick={() => handleTogglePublish(post)}
                  title={post.published ? 'Unpublish' : 'Publish'}
                >
                  {post.published ? <EyeSlash size={18} /> : <Eye size={18} />}
                </button>
                <button
                  type="button"
                  className={styles.iconButton}
                  onClick={() => handleEditPost(post)}
                  title="Edit"
                >
                  <Pencil size={18} />
                </button>
                <button
                  type="button"
                  className={`${styles.iconButton} ${styles.danger}`}
                  onClick={() => handleDeletePost(post.id)}
                  title="Delete"
                >
                  <Trash size={18} />
                </button>
              </div>
            </div>
          ))
        )}
      </div>
      
      {showCategoryModal && (
        <CategoryModal
          categories={categories}
          editingCategory={editingCategory}
          onSave={handleSaveCategory}
          onEdit={setEditingCategory}
          onDelete={handleDeleteCategory}
          onClose={() => { setShowCategoryModal(false); setEditingCategory(null); }}
        />
      )}
    </div>
  );
}

// Post Editor Component
interface PostEditorProps {
  post: BlogPost | null;
  categories: BlogCategory[];
  onSave: (data: {
    title: string;
    content: string;
    excerpt?: string;
    slug?: string;
    category_id?: number | null;
    tags?: string;
    featured_image?: string;
    published?: number;
  }) => void;
  onCancel: () => void;
}

function PostEditor({ post, categories, onSave, onCancel }: PostEditorProps): JSX.Element {
  const [title, setTitle] = useState(post?.title ?? '');
  const [slug, setSlug] = useState(post?.slug ?? '');
  const [content, setContent] = useState(post?.content ?? '');
  const [excerpt, setExcerpt] = useState(post?.excerpt ?? '');
  const [categoryId, setCategoryId] = useState<number | null>(post?.category_id ?? null);
  const [tags, setTags] = useState(post?.tags ?? '');
  const [featuredImage, setFeaturedImage] = useState(post?.featured_image ?? '');
  const [published, setPublished] = useState(post?.published ?? 0);
  const [saving, setSaving] = useState(false);
  
  const generateSlug = (text: string) => {
    return text
      .toLowerCase()
      .replace(/[^a-z0-9\s-]/g, '')
      .replace(/[\s-]+/g, '-')
      .trim();
  };
  
  const handleTitleChange = (value: string) => {
    setTitle(value);
    if (!post?.id) {
      setSlug(generateSlug(value));
    }
  };
  
  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    if (!title.trim() || !content.trim()) return;
    
    setSaving(true);
    await onSave({
      title: title.trim(),
      slug: slug.trim() || generateSlug(title),
      content: content.trim(),
      excerpt: excerpt.trim(),
      category_id: categoryId,
      tags: tags.trim(),
      featured_image: featuredImage.trim(),
      published
    });
    setSaving(false);
  };
  
  return (
    <div className={styles.container}>
      <header className={styles.header}>
        <div>
          <h1>{post?.id ? 'Edit Post' : 'New Post'}</h1>
          <p>Create engaging content for your audience</p>
        </div>
      </header>
      
      <form onSubmit={handleSubmit} className={styles.editorForm}>
        <div className={styles.formGrid}>
          <div className={styles.mainColumn}>
            <div className={styles.formGroup}>
              <label htmlFor="title">Title</label>
              <input
                id="title"
                type="text"
                value={title}
                onChange={(e) => handleTitleChange(e.target.value)}
                placeholder="Post title"
                required
              />
            </div>
            
            <div className={styles.formGroup}>
              <label htmlFor="slug">Slug</label>
              <input
                id="slug"
                type="text"
                value={slug}
                onChange={(e) => setSlug(e.target.value)}
                placeholder="post-slug"
              />
            </div>
            
            <div className={styles.formGroup}>
              <label htmlFor="excerpt">Excerpt</label>
              <textarea
                id="excerpt"
                value={excerpt}
                onChange={(e) => setExcerpt(e.target.value)}
                placeholder="Brief summary of the post..."
                rows={3}
              />
            </div>
            
            <div className={styles.formGroup}>
              <label htmlFor="content">Content (Markdown supported)</label>
              <textarea
                id="content"
                value={content}
                onChange={(e) => setContent(e.target.value)}
                placeholder="Write your post content here..."
                rows={20}
                required
              />
            </div>
          </div>
          
          <div className={styles.sideColumn}>
            <div className={styles.sideCard}>
              <h3>Settings</h3>
              
              <div className={styles.formGroup}>
                <label htmlFor="featured_image">Featured Image URL</label>
                <input
                  id="featured_image"
                  type="url"
                  value={featuredImage}
                  onChange={(e) => setFeaturedImage(e.target.value)}
                  placeholder="https://..."
                />
                {featuredImage && (
                  <div className={styles.imagePreview}>
                    <img src={featuredImage} alt="Preview" />
                  </div>
                )}
              </div>
              
              <div className={styles.formGroup}>
                <label htmlFor="category">Category</label>
                <select
                  id="category"
                  value={categoryId ?? ''}
                  onChange={(e) => setCategoryId(e.target.value ? Number(e.target.value) : null)}
                >
                  <option value="">No Category</option>
                  {categories.map(cat => (
                    <option key={cat.id} value={cat.id}>{cat.name}</option>
                  ))}
                </select>
              </div>
              
              <div className={styles.formGroup}>
                <label htmlFor="tags">Tags (comma separated)</label>
                <input
                  id="tags"
                  type="text"
                  value={tags}
                  onChange={(e) => setTags(e.target.value)}
                  placeholder="marketing, tips, podcasting"
                />
              </div>
              
              <div className={styles.formGroup}>
                <label className={styles.checkboxLabel}>
                  <input
                    type="checkbox"
                    checked={published === 1}
                    onChange={(e) => setPublished(e.target.checked ? 1 : 0)}
                  />
                  Published
                </label>
              </div>
            </div>
            
            <div className={styles.formActions}>
              <button
                type="button"
                className={styles.secondaryButton}
                onClick={onCancel}
                disabled={saving}
              >
                Cancel
              </button>
              <button
                type="submit"
                className={styles.primaryButton}
                disabled={saving || !title.trim() || !content.trim()}
              >
                {saving ? (
                  <>
                    <CircleNotch size={16} className={styles.spinner} />
                    Saving...
                  </>
                ) : (
                  <>
                    <Check size={16} weight="bold" />
                    Save Post
                  </>
                )}
              </button>
            </div>
          </div>
        </div>
      </form>
    </div>
  );
}

// Category Modal Component
interface CategoryModalProps {
  categories: BlogCategory[];
  editingCategory: BlogCategory | null;
  onSave: (data: { name: string; description?: string }) => void;
  onEdit: (category: BlogCategory | null) => void;
  onDelete: (id: number) => void;
  onClose: () => void;
}

function CategoryModal({ categories, editingCategory, onSave, onEdit, onDelete, onClose }: CategoryModalProps): JSX.Element {
  const [name, setName] = useState(editingCategory?.name ?? '');
  const [description, setDescription] = useState(editingCategory?.description ?? '');
  const [saving, setSaving] = useState(false);
  
  useEffect(() => {
    setName(editingCategory?.name ?? '');
    setDescription(editingCategory?.description ?? '');
  }, [editingCategory]);
  
  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    if (!name.trim()) return;
    
    setSaving(true);
    await onSave({ name: name.trim(), description: description.trim() });
    setSaving(false);
    setName('');
    setDescription('');
    onEdit(null);
  };
  
  return (
    <div className={styles.modalOverlay} onClick={onClose}>
      <div className={styles.modal} onClick={e => e.stopPropagation()}>
        <header className={styles.modalHeader}>
          <h2>Manage Categories</h2>
          <button type="button" className={styles.closeButton} onClick={onClose}>
            <X size={20} />
          </button>
        </header>
        
        <div className={styles.modalContent}>
          <form onSubmit={handleSubmit} className={styles.categoryForm}>
            <input
              type="text"
              value={name}
              onChange={(e) => setName(e.target.value)}
              placeholder="Category name"
              required
            />
            <input
              type="text"
              value={description}
              onChange={(e) => setDescription(e.target.value)}
              placeholder="Description (optional)"
            />
            <button
              type="submit"
              className={styles.primaryButton}
              disabled={saving || !name.trim()}
            >
              {editingCategory ? 'Update' : 'Add'} Category
            </button>
            {editingCategory && (
              <button
                type="button"
                className={styles.secondaryButton}
                onClick={() => { onEdit(null); setName(''); setDescription(''); }}
              >
                Cancel Edit
              </button>
            )}
          </form>
          
          <div className={styles.categoryList}>
            {categories.map(cat => (
              <div key={cat.id} className={styles.categoryItem}>
                <div>
                  <strong>{cat.name}</strong>
                  {cat.description && <p>{cat.description}</p>}
                  <span className={styles.articleCount}>{cat.post_count ?? 0} posts</span>
                </div>
                <div className={styles.categoryActions}>
                  <button
                    type="button"
                    className={styles.iconButton}
                    onClick={() => onEdit(cat)}
                  >
                    <Pencil size={16} />
                  </button>
                  <button
                    type="button"
                    className={`${styles.iconButton} ${styles.danger}`}
                    onClick={() => onDelete(cat.id)}
                  >
                    <Trash size={16} />
                  </button>
                </div>
              </div>
            ))}
          </div>
        </div>
      </div>
    </div>
  );
}


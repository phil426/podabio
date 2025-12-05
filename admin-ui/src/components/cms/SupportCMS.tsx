/**
 * Support CMS Admin Panel
 * Manage support/help documentation articles
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
  Article,
  Check,
  X
} from '@phosphor-icons/react';

import {
  adminGetSupportData,
  createSupportArticle,
  updateSupportArticle,
  deleteSupportArticle,
  createSupportCategory,
  updateSupportCategory,
  deleteSupportCategory,
  type SupportArticle,
  type SupportCategory
} from '../../api/cms';

import styles from './cms.module.css';

type ViewMode = 'list' | 'editor';

export function SupportCMS(): JSX.Element {
  const [articles, setArticles] = useState<SupportArticle[]>([]);
  const [categories, setCategories] = useState<SupportCategory[]>([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);
  const [viewMode, setViewMode] = useState<ViewMode>('list');
  const [editingArticle, setEditingArticle] = useState<SupportArticle | null>(null);
  const [searchQuery, setSearchQuery] = useState('');
  const [filterCategory, setFilterCategory] = useState<number | null>(null);
  
  // Category management
  const [showCategoryModal, setShowCategoryModal] = useState(false);
  const [editingCategory, setEditingCategory] = useState<SupportCategory | null>(null);
  
  // Load data
  const loadData = useCallback(async () => {
    setLoading(true);
    setError(null);
    try {
      const response = await adminGetSupportData();
      if (response.success) {
        setArticles(response.articles);
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
  
  // Filter articles
  const filteredArticles = articles.filter(article => {
    const matchesSearch = !searchQuery || 
      article.title.toLowerCase().includes(searchQuery.toLowerCase()) ||
      article.content.toLowerCase().includes(searchQuery.toLowerCase());
    const matchesCategory = filterCategory === null || article.category_id === filterCategory;
    return matchesSearch && matchesCategory;
  });
  
  // Create new article
  const handleNewArticle = () => {
    setEditingArticle({
      id: 0,
      title: '',
      slug: '',
      content: '',
      category_id: null,
      tags: '',
      published: 0,
      view_count: 0,
      created_at: '',
      updated_at: ''
    });
    setViewMode('editor');
  };
  
  // Edit article
  const handleEditArticle = (article: SupportArticle) => {
    setEditingArticle(article);
    setViewMode('editor');
  };
  
  // Save article
  const handleSaveArticle = async (articleData: {
    title: string;
    content: string;
    slug?: string;
    category_id?: number | null;
    tags?: string;
    published?: number;
  }) => {
    try {
      if (editingArticle?.id) {
        await updateSupportArticle(editingArticle.id, {
          title: articleData.title,
          content: articleData.content,
          slug: articleData.slug,
          category_id: articleData.category_id,
          tags: articleData.tags ?? undefined,
          published: articleData.published
        });
      } else {
        await createSupportArticle(articleData);
      }
      await loadData();
      setViewMode('list');
      setEditingArticle(null);
    } catch (err) {
      setError(err instanceof Error ? err.message : 'Failed to save article');
    }
  };
  
  // Delete article
  const handleDeleteArticle = async (id: number) => {
    if (!confirm('Are you sure you want to delete this article?')) return;
    
    try {
      await deleteSupportArticle(id);
      await loadData();
    } catch (err) {
      setError(err instanceof Error ? err.message : 'Failed to delete article');
    }
  };
  
  // Toggle publish
  const handleTogglePublish = async (article: SupportArticle) => {
    try {
      await updateSupportArticle(article.id, { published: article.published ? 0 : 1 });
      await loadData();
    } catch (err) {
      setError(err instanceof Error ? err.message : 'Failed to update article');
    }
  };
  
  // Category handlers
  const handleSaveCategory = async (categoryData: { name: string; description?: string }) => {
    try {
      if (editingCategory?.id) {
        await updateSupportCategory(editingCategory.id, {
          name: categoryData.name,
          description: categoryData.description
        });
      } else {
        await createSupportCategory({ name: categoryData.name, description: categoryData.description });
      }
      await loadData();
      setShowCategoryModal(false);
      setEditingCategory(null);
    } catch (err) {
      setError(err instanceof Error ? err.message : 'Failed to save category');
    }
  };
  
  const handleDeleteCategory = async (id: number) => {
    if (!confirm('Are you sure you want to delete this category? Articles will be uncategorized.')) return;
    
    try {
      await deleteSupportCategory(id);
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
          <span>Loading support articles...</span>
        </div>
      </div>
    );
  }
  
  if (viewMode === 'editor') {
    return (
      <ArticleEditor
        article={editingArticle}
        categories={categories}
        onSave={handleSaveArticle}
        onCancel={() => { setViewMode('list'); setEditingArticle(null); }}
      />
    );
  }
  
  return (
    <div className={styles.container}>
      <header className={styles.header}>
        <div>
          <h1>Support Articles</h1>
          <p>Manage help documentation for your users</p>
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
            onClick={handleNewArticle}
          >
            <Plus size={16} weight="bold" />
            New Article
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
            placeholder="Search articles..."
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
          <Article size={24} weight="regular" />
          <div>
            <span className={styles.statValue}>{articles.length}</span>
            <span className={styles.statLabel}>Total Articles</span>
          </div>
        </div>
        <div className={styles.statCard}>
          <Eye size={24} weight="regular" />
          <div>
            <span className={styles.statValue}>
              {articles.filter(a => a.published).length}
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
      
      <div className={styles.articleList}>
        {filteredArticles.length === 0 ? (
          <div className={styles.emptyState}>
            <Article size={48} weight="regular" />
            <p>No articles found</p>
            <button type="button" className={styles.primaryButton} onClick={handleNewArticle}>
              Create your first article
            </button>
          </div>
        ) : (
          filteredArticles.map(article => (
            <div key={article.id} className={styles.articleCard}>
              <div className={styles.articleInfo}>
                <h3>{article.title}</h3>
                <div className={styles.articleMeta}>
                  {article.category_name && (
                    <span className={styles.categoryBadge}>{article.category_name}</span>
                  )}
                  <span className={`${styles.statusBadge} ${article.published ? styles.published : styles.draft}`}>
                    {article.published ? 'Published' : 'Draft'}
                  </span>
                  <span className={styles.viewCount}>{article.view_count} views</span>
                </div>
              </div>
              <div className={styles.articleActions}>
                <button
                  type="button"
                  className={styles.iconButton}
                  onClick={() => handleTogglePublish(article)}
                  title={article.published ? 'Unpublish' : 'Publish'}
                >
                  {article.published ? <EyeSlash size={18} /> : <Eye size={18} />}
                </button>
                <button
                  type="button"
                  className={styles.iconButton}
                  onClick={() => handleEditArticle(article)}
                  title="Edit"
                >
                  <Pencil size={18} />
                </button>
                <button
                  type="button"
                  className={`${styles.iconButton} ${styles.danger}`}
                  onClick={() => handleDeleteArticle(article.id)}
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

// Article Editor Component
interface ArticleEditorProps {
  article: SupportArticle | null;
  categories: SupportCategory[];
  onSave: (data: {
    title: string;
    content: string;
    slug?: string;
    category_id?: number | null;
    tags?: string;
    published?: number;
  }) => void;
  onCancel: () => void;
}

function ArticleEditor({ article, categories, onSave, onCancel }: ArticleEditorProps): JSX.Element {
  const [title, setTitle] = useState(article?.title ?? '');
  const [slug, setSlug] = useState(article?.slug ?? '');
  const [content, setContent] = useState(article?.content ?? '');
  const [categoryId, setCategoryId] = useState<number | null>(article?.category_id ?? null);
  const [tags, setTags] = useState(article?.tags ?? '');
  const [published, setPublished] = useState(article?.published ?? 0);
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
    if (!article?.id) {
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
      category_id: categoryId,
      tags: tags.trim(),
      published
    });
    setSaving(false);
  };
  
  return (
    <div className={styles.container}>
      <header className={styles.header}>
        <div>
          <h1>{article?.id ? 'Edit Article' : 'New Article'}</h1>
          <p>Create helpful documentation for your users</p>
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
                placeholder="Article title"
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
                placeholder="article-slug"
              />
            </div>
            
            <div className={styles.formGroup}>
              <label htmlFor="content">Content (Markdown supported)</label>
              <textarea
                id="content"
                value={content}
                onChange={(e) => setContent(e.target.value)}
                placeholder="Write your article content here..."
                rows={20}
                required
              />
            </div>
          </div>
          
          <div className={styles.sideColumn}>
            <div className={styles.sideCard}>
              <h3>Settings</h3>
              
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
                  placeholder="getting-started, faq, how-to"
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
                    Save Article
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
  categories: SupportCategory[];
  editingCategory: SupportCategory | null;
  onSave: (data: { name: string; description?: string }) => void;
  onEdit: (category: SupportCategory | null) => void;
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
                  <span className={styles.articleCount}>{cat.article_count ?? 0} articles</span>
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


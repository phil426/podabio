import { useState } from 'react';
import { LuPlus, LuPencil, LuTrash2, LuFileText, LuCalendar, LuEye, LuEyeOff } from 'react-icons/lu';

import { useBlogPosts, useDeleteBlogPostMutation, useBlogCategories } from '../../api/blog';
import type { BlogPost } from '../../api/blog';

import styles from './blog-panel.module.css';

interface BlogPanelProps {
  onSelectPost: (post: BlogPost | null) => void;
  selectedPostId: number | null;
}

export function BlogPanel({ onSelectPost, selectedPostId }: BlogPanelProps): JSX.Element {
  const { data: postsData, isLoading } = useBlogPosts({ limit: 50 });
  const { data: categoriesData } = useBlogCategories();
  const deleteMutation = useDeleteBlogPostMutation();
  const [selectedCategory, setSelectedCategory] = useState<number | null>(null);

  const posts = postsData?.posts ?? [];
  const categories = categoriesData?.categories ?? [];

  const handleCreateNew = () => {
    onSelectPost(null);
  };

  const handleEdit = (post: BlogPost) => {
    onSelectPost(post);
  };

  const handleDelete = async (postId: number, e: React.MouseEvent) => {
    e.stopPropagation();
    if (confirm('Are you sure you want to delete this post?')) {
      deleteMutation.mutate(postId);
    }
  };

  const filteredPosts = selectedCategory
    ? posts.filter((post) => post.category_id === selectedCategory)
    : posts;

  const formatDate = (dateString: string) => {
    const date = new Date(dateString);
    return date.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
  };

  return (
    <div className={styles.panel}>
      <div className={styles.header}>
        <h3>Blog Posts</h3>
        <button type="button" className={styles.createButton} onClick={handleCreateNew}>
          <LuPlus aria-hidden="true" />
          <span>New Post</span>
        </button>
      </div>

      {categories.length > 0 && (
        <div className={styles.categoryFilter}>
          <button
            type="button"
            className={selectedCategory === null ? styles.categoryButtonActive : styles.categoryButton}
            onClick={() => setSelectedCategory(null)}
          >
            All
          </button>
          {categories.map((category) => (
            <button
              key={category.id}
              type="button"
              className={selectedCategory === category.id ? styles.categoryButtonActive : styles.categoryButton}
              onClick={() => setSelectedCategory(category.id)}
            >
              {category.name}
            </button>
          ))}
        </div>
      )}

      {isLoading ? (
        <div className={styles.loading}>Loading posts...</div>
      ) : filteredPosts.length === 0 ? (
        <div className={styles.empty}>
          <LuFileText aria-hidden="true" />
          <p>No blog posts yet.</p>
          <button type="button" className={styles.emptyButton} onClick={handleCreateNew}>
            Create your first post
          </button>
        </div>
      ) : (
        <div className={styles.postsList}>
          {filteredPosts.map((post) => (
            <div
              key={post.id}
              className={`${styles.postItem} ${selectedPostId === post.id ? styles.postItemSelected : ''}`}
              onClick={() => handleEdit(post)}
            >
              <div className={styles.postHeader}>
                <h4 className={styles.postTitle}>{post.title}</h4>
                <div className={styles.postActions}>
                  <button
                    type="button"
                    className={styles.actionButton}
                    onClick={(e) => {
                      e.stopPropagation();
                      handleEdit(post);
                    }}
                    aria-label="Edit post"
                  >
                    <LuPencil aria-hidden="true" />
                  </button>
                  <button
                    type="button"
                    className={styles.actionButton}
                    onClick={(e) => handleDelete(post.id, e)}
                    aria-label="Delete post"
                  >
                    <LuTrash2 aria-hidden="true" />
                  </button>
                </div>
              </div>
              <div className={styles.postMeta}>
                <span className={styles.postDate}>
                  <LuCalendar aria-hidden="true" />
                  {formatDate(post.created_at)}
                </span>
                {post.category_name && (
                  <span className={styles.postCategory}>{post.category_name}</span>
                )}
                <span className={styles.postStatus}>
                  {post.published === 1 ? (
                    <>
                      <LuEye aria-hidden="true" />
                      Published
                    </>
                  ) : (
                    <>
                      <LuEyeOff aria-hidden="true" />
                      Draft
                    </>
                  )}
                </span>
              </div>
              {post.excerpt && (
                <p className={styles.postExcerpt}>{post.excerpt}</p>
              )}
            </div>
          ))}
        </div>
      )}
    </div>
  );
}


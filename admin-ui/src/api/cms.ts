/**
 * CMS API Functions
 * API client for Support Articles and Blog Posts
 */

import { requestJson } from './http';
import type { ApiResponse } from './types';
import { formPostInit } from './utils';

// ==================== TYPES ====================

export interface SupportCategory {
  id: number;
  name: string;
  slug: string;
  description: string | null;
  display_order: number;
  article_count?: number;
  created_at: string;
  updated_at: string;
}

export interface SupportArticle {
  id: number;
  title: string;
  slug: string;
  content: string;
  category_id: number | null;
  category_name?: string;
  category_slug?: string;
  tags: string | null;
  published: number;
  view_count: number;
  created_at: string;
  updated_at: string;
}

export interface BlogCategory {
  id: number;
  name: string;
  slug: string;
  description: string | null;
  display_order: number;
  post_count?: number;
  created_at: string;
  updated_at: string;
}

export interface BlogPost {
  id: number;
  title: string;
  slug: string;
  content: string;
  excerpt: string | null;
  author_id: number;
  author_email?: string;
  category_id: number | null;
  category_name?: string;
  category_slug?: string;
  tags: string | null;
  featured_image: string | null;
  published: number;
  view_count: number;
  created_at: string;
  updated_at: string;
}

// Response types
interface SupportCategoriesResponse extends ApiResponse {
  categories: SupportCategory[];
}

interface SupportArticlesResponse extends ApiResponse {
  articles: SupportArticle[];
  total: number;
  limit: number;
  offset: number;
}

interface SupportArticleResponse extends ApiResponse {
  article: SupportArticle;
}

interface BlogCategoriesResponse extends ApiResponse {
  categories: BlogCategory[];
}

interface BlogPostsResponse extends ApiResponse {
  posts: BlogPost[];
  total: number;
  limit: number;
  offset: number;
}

interface BlogPostResponse extends ApiResponse {
  post: BlogPost;
  related_posts?: BlogPost[];
}

// Admin response types
interface AdminSupportResponse extends ApiResponse {
  articles: SupportArticle[];
  categories: SupportCategory[];
  total: number;
}

interface AdminBlogResponse extends ApiResponse {
  posts: BlogPost[];
  categories: BlogCategory[];
  total: number;
}

// ==================== SUPPORT API ====================

const SUPPORT_ENDPOINT = '/api/support.php';

// Public endpoints
export async function getSupportCategories(): Promise<SupportCategoriesResponse> {
  return requestJson<SupportCategoriesResponse>(`${SUPPORT_ENDPOINT}?action=get_categories`);
}

export async function getSupportArticles(
  categoryId?: number,
  limit = 50,
  offset = 0
): Promise<SupportArticlesResponse> {
  const params = new URLSearchParams({
    action: 'get_articles',
    limit: String(limit),
    offset: String(offset)
  });
  if (categoryId) params.append('category_id', String(categoryId));
  return requestJson<SupportArticlesResponse>(`${SUPPORT_ENDPOINT}?${params}`);
}

export async function getSupportArticle(slug: string): Promise<SupportArticleResponse> {
  return requestJson<SupportArticleResponse>(`${SUPPORT_ENDPOINT}?action=get_article&slug=${encodeURIComponent(slug)}`);
}

export async function searchSupportArticles(query: string): Promise<SupportArticlesResponse> {
  return requestJson<SupportArticlesResponse>(`${SUPPORT_ENDPOINT}?action=search&q=${encodeURIComponent(query)}`);
}

export async function getPopularSupportArticles(limit = 10): Promise<SupportArticlesResponse> {
  return requestJson<SupportArticlesResponse>(`${SUPPORT_ENDPOINT}?action=get_popular&limit=${limit}`);
}

// Admin endpoints
export async function adminGetSupportData(): Promise<AdminSupportResponse> {
  return requestJson<AdminSupportResponse>(`${SUPPORT_ENDPOINT}?action=admin_get_all`);
}

export async function adminGetSupportArticle(id: number): Promise<SupportArticleResponse> {
  return requestJson<SupportArticleResponse>(`${SUPPORT_ENDPOINT}?action=admin_get_article&id=${id}`);
}

export async function createSupportArticle(data: {
  title: string;
  content: string;
  slug?: string;
  category_id?: number | null;
  tags?: string;
  published?: number;
}): Promise<SupportArticleResponse> {
  const payload: Record<string, string | undefined> = {
    action: 'create_article',
    title: data.title,
    content: data.content,
    slug: data.slug,
    category_id: data.category_id != null ? String(data.category_id) : undefined,
    tags: data.tags,
    published: data.published != null ? String(data.published) : undefined
  };
  return requestJson<SupportArticleResponse>(SUPPORT_ENDPOINT, formPostInit(payload));
}

export async function updateSupportArticle(
  id: number,
  data: Partial<{
    title: string;
    content: string;
    slug: string;
    category_id: number | null;
    tags: string;
    published: number;
  }>
): Promise<SupportArticleResponse> {
  const payload: Record<string, string | undefined> = {
    action: 'update_article',
    id: String(id),
    title: data.title,
    content: data.content,
    slug: data.slug,
    category_id: data.category_id != null ? String(data.category_id) : (data.category_id === null ? '' : undefined),
    tags: data.tags ?? undefined,
    published: data.published != null ? String(data.published) : undefined
  };
  return requestJson<SupportArticleResponse>(SUPPORT_ENDPOINT, formPostInit(payload));
}

export async function deleteSupportArticle(id: number): Promise<ApiResponse> {
  return requestJson<ApiResponse>(
    SUPPORT_ENDPOINT,
    formPostInit({ action: 'delete_article', id: String(id) })
  );
}

export async function createSupportCategory(data: {
  name: string;
  slug?: string;
  description?: string;
  display_order?: number;
}): Promise<ApiResponse & { category: SupportCategory }> {
  const payload: Record<string, string | undefined> = {
    action: 'create_category',
    name: data.name,
    slug: data.slug,
    description: data.description,
    display_order: data.display_order != null ? String(data.display_order) : undefined
  };
  return requestJson<ApiResponse & { category: SupportCategory }>(SUPPORT_ENDPOINT, formPostInit(payload));
}

export async function updateSupportCategory(
  id: number,
  data: Partial<{
    name: string;
    slug: string;
    description: string;
    display_order: number;
  }>
): Promise<ApiResponse & { category: SupportCategory }> {
  const payload: Record<string, string | undefined> = {
    action: 'update_category',
    id: String(id),
    name: data.name,
    slug: data.slug,
    description: data.description,
    display_order: data.display_order != null ? String(data.display_order) : undefined
  };
  return requestJson<ApiResponse & { category: SupportCategory }>(SUPPORT_ENDPOINT, formPostInit(payload));
}

export async function deleteSupportCategory(id: number): Promise<ApiResponse> {
  return requestJson<ApiResponse>(
    SUPPORT_ENDPOINT,
    formPostInit({ action: 'delete_category', id: String(id) })
  );
}

// ==================== BLOG API ====================

const BLOG_ENDPOINT = '/api/blog.php';

// Public endpoints
export async function getBlogCategories(): Promise<BlogCategoriesResponse> {
  return requestJson<BlogCategoriesResponse>(`${BLOG_ENDPOINT}?action=get_categories`);
}

export async function getBlogPosts(
  categoryId?: number,
  limit = 20,
  offset = 0
): Promise<BlogPostsResponse> {
  const params = new URLSearchParams({
    action: 'get_posts',
    limit: String(limit),
    offset: String(offset)
  });
  if (categoryId) params.append('category_id', String(categoryId));
  return requestJson<BlogPostsResponse>(`${BLOG_ENDPOINT}?${params}`);
}

export async function getBlogPost(slug: string): Promise<BlogPostResponse> {
  return requestJson<BlogPostResponse>(`${BLOG_ENDPOINT}?action=get_post&slug=${encodeURIComponent(slug)}`);
}

export async function searchBlogPosts(query: string): Promise<BlogPostsResponse> {
  return requestJson<BlogPostsResponse>(`${BLOG_ENDPOINT}?action=search&q=${encodeURIComponent(query)}`);
}

export async function getRecentBlogPosts(limit = 5): Promise<BlogPostsResponse> {
  return requestJson<BlogPostsResponse>(`${BLOG_ENDPOINT}?action=get_recent&limit=${limit}`);
}

// Admin endpoints
export async function adminGetBlogData(): Promise<AdminBlogResponse> {
  return requestJson<AdminBlogResponse>(`${BLOG_ENDPOINT}?action=admin_get_all`);
}

export async function adminGetBlogPost(id: number): Promise<BlogPostResponse> {
  return requestJson<BlogPostResponse>(`${BLOG_ENDPOINT}?action=admin_get_post&id=${id}`);
}

export async function createBlogPost(data: {
  title: string;
  content: string;
  excerpt?: string;
  slug?: string;
  category_id?: number | null;
  tags?: string;
  featured_image?: string;
  published?: number;
}): Promise<BlogPostResponse> {
  const payload: Record<string, string | undefined> = {
    action: 'create_post',
    title: data.title,
    content: data.content,
    excerpt: data.excerpt,
    slug: data.slug,
    category_id: data.category_id != null ? String(data.category_id) : undefined,
    tags: data.tags,
    featured_image: data.featured_image,
    published: data.published != null ? String(data.published) : undefined
  };
  return requestJson<BlogPostResponse>(BLOG_ENDPOINT, formPostInit(payload));
}

export async function updateBlogPost(
  id: number,
  data: Partial<{
    title: string;
    content: string;
    excerpt: string;
    slug: string;
    category_id: number | null;
    tags: string;
    featured_image: string;
    published: number;
  }>
): Promise<BlogPostResponse> {
  const payload: Record<string, string | undefined> = {
    action: 'update_post',
    id: String(id),
    title: data.title,
    content: data.content,
    excerpt: data.excerpt,
    slug: data.slug,
    category_id: data.category_id != null ? String(data.category_id) : (data.category_id === null ? '' : undefined),
    tags: data.tags,
    featured_image: data.featured_image,
    published: data.published != null ? String(data.published) : undefined
  };
  return requestJson<BlogPostResponse>(BLOG_ENDPOINT, formPostInit(payload));
}

export async function deleteBlogPost(id: number): Promise<ApiResponse> {
  return requestJson<ApiResponse>(
    BLOG_ENDPOINT,
    formPostInit({ action: 'delete_post', id: String(id) })
  );
}

export async function createBlogCategory(data: {
  name: string;
  slug?: string;
  description?: string;
  display_order?: number;
}): Promise<ApiResponse & { category: BlogCategory }> {
  const payload: Record<string, string | undefined> = {
    action: 'create_category',
    name: data.name,
    slug: data.slug,
    description: data.description,
    display_order: data.display_order != null ? String(data.display_order) : undefined
  };
  return requestJson<ApiResponse & { category: BlogCategory }>(BLOG_ENDPOINT, formPostInit(payload));
}

export async function updateBlogCategory(
  id: number,
  data: Partial<{
    name: string;
    slug: string;
    description: string;
    display_order: number;
  }>
): Promise<ApiResponse & { category: BlogCategory }> {
  const payload: Record<string, string | undefined> = {
    action: 'update_category',
    id: String(id),
    name: data.name,
    slug: data.slug,
    description: data.description,
    display_order: data.display_order != null ? String(data.display_order) : undefined
  };
  return requestJson<ApiResponse & { category: BlogCategory }>(BLOG_ENDPOINT, formPostInit(payload));
}

export async function deleteBlogCategory(id: number): Promise<ApiResponse> {
  return requestJson<ApiResponse>(
    BLOG_ENDPOINT,
    formPostInit({ action: 'delete_category', id: String(id) })
  );
}


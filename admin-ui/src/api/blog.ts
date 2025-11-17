import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';

import { requestJson } from './http';
import type { ApiResponse } from './types';
import { formPostInit, queryKeys } from './utils';

const BLOG_ENDPOINT = '/api/blog.php';
const CATEGORIES_ENDPOINT = '/api/blog_categories.php';

export interface BlogPost {
  id: number;
  title: string;
  slug: string;
  content: string;
  excerpt?: string | null;
  category_id?: number | null;
  category_name?: string | null;
  category_slug?: string | null;
  author_id: number;
  published: number;
  featured_image?: string | null;
  view_count?: number;
  created_at: string;
  updated_at: string;
}

export interface BlogCategory {
  id: number;
  name: string;
  slug: string;
  display_order?: number;
}

export interface BlogPostsResponse extends ApiResponse {
  posts: BlogPost[];
  total?: number;
  page?: number;
  limit?: number;
}

export interface BlogPostResponse extends ApiResponse {
  post: BlogPost;
  post_id?: number;
}

export interface BlogCategoriesResponse extends ApiResponse {
  categories: BlogCategory[];
}

export async function fetchBlogPosts(params?: { page?: number; limit?: number; category_id?: number }): Promise<BlogPostsResponse> {
  const queryParams = new URLSearchParams();
  queryParams.set('action', 'list');
  if (params?.page) queryParams.set('page', String(params.page));
  if (params?.limit) queryParams.set('limit', String(params.limit));
  if (params?.category_id) queryParams.set('category_id', String(params.category_id));
  
  return requestJson<BlogPostsResponse>(`${BLOG_ENDPOINT}?${queryParams.toString()}`, { method: 'GET' });
}

export function useBlogPosts(params?: { page?: number; limit?: number; category_id?: number }) {
  return useQuery({
    queryKey: queryKeys.blogPosts(params),
    queryFn: () => fetchBlogPosts(params)
  });
}

export async function fetchBlogPost(postId: number): Promise<BlogPostResponse> {
  return requestJson<BlogPostResponse>(`${BLOG_ENDPOINT}?action=get&id=${postId}`, { method: 'GET' });
}

export function useBlogPost(postId: number | null) {
  return useQuery({
    queryKey: queryKeys.blogPost(postId),
    queryFn: () => fetchBlogPost(postId!),
    enabled: postId !== null
  });
}

export async function fetchBlogCategories(): Promise<BlogCategoriesResponse> {
  return requestJson<BlogCategoriesResponse>(CATEGORIES_ENDPOINT, { method: 'GET' });
}

export function useBlogCategories() {
  return useQuery({
    queryKey: queryKeys.blogCategories(),
    queryFn: fetchBlogCategories
  });
}

export async function createBlogPost(payload: {
  title: string;
  slug: string;
  content: string;
  excerpt?: string;
  category_id?: number | null;
  published?: boolean;
  featured_image?: string;
}): Promise<BlogPostResponse> {
  return requestJson<BlogPostResponse>(
    BLOG_ENDPOINT,
    formPostInit({
      action: 'create',
      title: payload.title,
      slug: payload.slug,
      content: payload.content,
      excerpt: payload.excerpt ?? '',
      category_id: payload.category_id ? String(payload.category_id) : '',
      published: payload.published ? '1' : '0',
      featured_image: payload.featured_image ?? ''
    })
  );
}

export function useCreateBlogPostMutation() {
  const queryClient = useQueryClient();
  
  return useMutation({
    mutationFn: createBlogPost,
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: queryKeys.blogPosts() });
    }
  });
}

export async function updateBlogPost(payload: {
  post_id: number;
  title: string;
  slug: string;
  content: string;
  excerpt?: string;
  category_id?: number | null;
  published?: boolean;
  featured_image?: string;
}): Promise<BlogPostResponse> {
  return requestJson<BlogPostResponse>(
    BLOG_ENDPOINT,
    formPostInit({
      action: 'update',
      post_id: String(payload.post_id),
      title: payload.title,
      slug: payload.slug,
      content: payload.content,
      excerpt: payload.excerpt ?? '',
      category_id: payload.category_id ? String(payload.category_id) : '',
      published: payload.published ? '1' : '0',
      featured_image: payload.featured_image ?? ''
    })
  );
}

export function useUpdateBlogPostMutation() {
  const queryClient = useQueryClient();
  
  return useMutation({
    mutationFn: updateBlogPost,
    onSuccess: (data) => {
      queryClient.invalidateQueries({ queryKey: queryKeys.blogPosts() });
      if (data.post?.id) {
        queryClient.invalidateQueries({ queryKey: queryKeys.blogPost(data.post.id) });
      }
    }
  });
}

export async function deleteBlogPost(postId: number): Promise<ApiResponse> {
  return requestJson<ApiResponse>(
    BLOG_ENDPOINT,
    formPostInit({
      action: 'delete',
      post_id: String(postId)
    })
  );
}

export function useDeleteBlogPostMutation() {
  const queryClient = useQueryClient();
  
  return useMutation({
    mutationFn: deleteBlogPost,
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: queryKeys.blogPosts() });
    }
  });
}


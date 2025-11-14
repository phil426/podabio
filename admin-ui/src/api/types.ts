import type { TokenBundle } from '../design-system/tokens';

export interface ApiResponse<T = unknown> {
  success: boolean;
  error?: string | null;
  data?: T;
  [key: string]: unknown;
}

export interface SocialIconRecord {
  id: number | string;
  page_id: number;
  platform_name: string;
  url: string;
  icon: string | null;
  display_order: number;
  is_active?: number;
}

export interface PageSnapshot {
  id: number;
  user_id?: number;
  username: string;
  custom_domain?: string | null;
  rss_feed_url: string | null;
  podcast_name: string | null;
  podcast_description: string | null;
  footer_text?: string | null;
  cover_image_url: string | null;
  profile_image?: string | null;
  theme_id: number | null;
  colors: Record<string, unknown> | null;
  fonts: Record<string, unknown> | null;
  layout_option?: string | null;
  page_background: string | null;
  widget_styles?: Record<string, unknown> | null;
  widget_background: string | null;
  widget_border_color: string | null;
  widget_primary_font?: string | null;
  widget_secondary_font?: string | null;
  page_primary_font: string | null;
  page_secondary_font: string | null;
  publish_status?: 'draft' | 'published' | 'scheduled';
  published_at?: string | null;
  scheduled_publish_at?: string | null;
  name_alignment?: 'left' | 'center' | 'right' | null;
  name_text_size?: 'large' | 'xlarge' | 'xxlarge' | null;
  profile_image_shape?: 'circle' | 'rounded' | 'square' | null;
  profile_image_shadow?: 'none' | 'subtle' | 'strong' | null;
  profile_image_size?: 'small' | 'medium' | 'large' | null;
  profile_image_border?: 'none' | 'thin' | 'thick' | null;
  bio_alignment?: 'left' | 'center' | 'right' | null;
  bio_text_size?: 'small' | 'medium' | 'large' | null;
  profile_visible?: boolean | null;
  footer_visible?: boolean | null;
  podcast_player_enabled?: boolean | null;
}

export interface WidgetConfig {
  [key: string]: string | number | boolean | null | WidgetConfig | WidgetConfig[];
}

export interface WidgetRecord {
  id: number;
  page_id: number;
  widget_type: string;
  title: string;
  config_data: WidgetConfig | string | null;
  display_order: number;
  is_active: 0 | 1;
  is_featured?: 0 | 1;
  featured_effect?: string | null;
  created_at: string;
  updated_at: string;
}

export interface WidgetListResponse extends ApiResponse {
  widgets: WidgetRecord[];
}

export interface PageSnapshotResponse extends ApiResponse {
  page: PageSnapshot;
  widgets: WidgetRecord[];
  social_icons: SocialIconRecord[];
  tokens: TokenBundle;
  token_overrides?: Record<string, unknown>;
}

export interface AvailableWidget {
  widget_id?: string;
  type?: string;
  name?: string;
  label?: string;
  description?: string;
  category?: string;
  config_fields?: Record<string, Record<string, unknown>>;
  [key: string]: unknown;
}

export interface AccountProfile {
  email: string;
  name?: string;
  plan?: string;
  avatar_url?: string | null;
}

export interface AuthMethodRecord {
  has_password: boolean;
  has_google: boolean;
  google_link_url?: string;
}

export interface BillingInfo {
  plan_type: string;
  expires_at?: string | null;
  payment_method?: string | null;
  status: 'active' | 'expired' | 'canceled';
  invoices?: BillingInvoice[];
}

export interface BillingInvoice {
  id: string;
  amount: number;
  currency: string;
  status: 'paid' | 'pending' | 'failed';
  issued_at: string;
  hosted_invoice_url?: string;
}

export interface AvailableWidgetsResponse extends ApiResponse {
  available_widgets?: AvailableWidget[];
  widgets?: AvailableWidget[];
}

export interface TokensResponse extends ApiResponse {
  tokens: TokenBundle;
  overrides?: Record<string, unknown>;
}

export interface AnalyticsWidgetEntry {
  widget_id: number;
  title: string;
  click_count: number;
  view_count: number;
  ctr: number;
}

export interface WidgetAnalyticsResponse extends ApiResponse {
  widgets: AnalyticsWidgetEntry[];
  page_views: number;
  total_clicks: number;
}

export interface PublishStateResponse extends ApiResponse {
  publish_status?: 'draft' | 'published' | 'scheduled';
  published_at?: string | null;
  scheduled_publish_at?: string | null;
}

export interface ThemeRecord {
  id: number;
  name: string;
  user_id?: number | null;
  colors?: Record<string, unknown> | null;
  fonts?: Record<string, unknown> | null;
  page_background?: string | null;
  widget_background?: string | null;
  widget_border_color?: string | null;
  widget_primary_font?: string | null;
  widget_secondary_font?: string | null;
  page_primary_font?: string | null;
  page_secondary_font?: string | null;
  preview_image?: string | null;
  layout_density?: string | null;
  color_tokens?: Record<string, unknown> | string | null;
  typography_tokens?: Record<string, unknown> | string | null;
  spacing_tokens?: Record<string, unknown> | string | null;
  shape_tokens?: Record<string, unknown> | string | null;
  motion_tokens?: Record<string, unknown> | string | null;
  spatial_effect?: string | null;
  categories?: string[] | null;
  tags?: string[] | null;
}

export interface ThemeLibraryResponse extends ApiResponse {
  system?: ThemeRecord[];
  user?: ThemeRecord[];
  themes?: ThemeRecord[];
}

export interface TokenHistoryEntry {
  id: number;
  overrides: Partial<TokenBundle>;
  created_at: string;
  created_by?: number | null;
  created_by_email?: string | null;
}


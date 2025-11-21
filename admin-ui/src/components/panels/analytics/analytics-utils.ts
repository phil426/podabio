export function formatNumber(value: number): string {
  if (value === 0) return '0';
  if (value < 1000) return value.toString();
  if (value < 1000000) return `${(value / 1000).toFixed(1)}k`;
  return `${(value / 1000000).toFixed(1)}M`;
}

export function formatPercentage(value: number): string {
  return `${value.toFixed(1)}%`;
}

export function formatDate(dateString: string): string {
  const date = new Date(dateString);
  return date.toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
}

export function truncateUrl(url: string, maxLength: number = 40): string {
  if (url.length <= maxLength) return url;
  try {
    const urlObj = new URL(url);
    const host = urlObj.hostname.replace('www.', '');
    const path = urlObj.pathname;
    const full = `${host}${path}`;
    if (full.length <= maxLength) return full;
    return `${full.substring(0, maxLength - 3)}...`;
  } catch {
    return url.length > maxLength ? `${url.substring(0, maxLength - 3)}...` : url;
  }
}

export interface ChartDataPoint {
  x: number;
  y: number;
  value: number;
  date: string;
}

export interface ChartData {
  maxClicks: number;
  points: ChartDataPoint[];
}

export function processChartData(timeSeries: Array<{ date: string; clicks: number }>): ChartData {
  if (!timeSeries || timeSeries.length === 0) {
    return { maxClicks: 0, points: [] };
  }
  
  const maxClicks = Math.max(...timeSeries.map(d => d.clicks), 1);
  const points = timeSeries.map((point, index) => ({
    x: index,
    y: maxClicks > 0 ? (point.clicks / maxClicks) * 100 : 0,
    value: point.clicks,
    date: point.date
  }));
  
  return { maxClicks, points };
}

export interface TrendData {
  change: number;
  percentChange: number;
  isPositive: boolean;
}

export function calculateTrend(timeSeries: Array<{ date: string; clicks: number }>): TrendData | null {
  if (!timeSeries || timeSeries.length < 2) return null;
  const mid = Math.floor(timeSeries.length / 2);
  const firstHalf = timeSeries.slice(0, mid).reduce((sum, d) => sum + d.clicks, 0);
  const secondHalf = timeSeries.slice(mid).reduce((sum, d) => sum + d.clicks, 0);
  const change = secondHalf - firstHalf;
  const percentChange = firstHalf > 0 ? ((change / firstHalf) * 100) : 0;
  return { change, percentChange, isPositive: change >= 0 };
}


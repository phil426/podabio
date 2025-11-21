import { type TabColorTheme } from '../../layout/tab-colors';
import { formatDate, type ChartData } from './analytics-utils';
import styles from '../analytics-dashboard.module.css';

interface ClickChartProps {
  chartData: ChartData;
  activeColor: TabColorTheme;
}

export function ClickChart({ chartData, activeColor }: ClickChartProps): JSX.Element {
  if (chartData.points.length === 0) {
    return null;
  }

  const averageClicks = chartData.points.length > 0 
    ? Math.round(chartData.points.reduce((sum, p) => sum + p.value, 0) / chartData.points.length)
    : 0;

  return (
    <div className={styles.chartSection}>
      <div className={styles.chartHeader}>
        <div>
          <h2 className={styles.chartTitle}>Clicks Over Time</h2>
          <p className={styles.chartSubtitle}>Daily click activity for the selected period</p>
        </div>
      </div>
      <div className={styles.chartContainer}>
        <svg className={styles.chart} viewBox="0 0 1000 300" preserveAspectRatio="none">
          <defs>
            <linearGradient id="chartGradient" x1="0%" y1="0%" x2="0%" y2="100%">
              <stop offset="0%" stopColor="var(--active-tab-color, #2563eb)" stopOpacity="0.4" />
              <stop offset="50%" stopColor="var(--active-tab-color, #2563eb)" stopOpacity="0.15" />
              <stop offset="100%" stopColor="var(--active-tab-color, #2563eb)" stopOpacity="0.05" />
            </linearGradient>
            <filter id="glow">
              <feGaussianBlur stdDeviation="3" result="coloredBlur"/>
              <feMerge>
                <feMergeNode in="coloredBlur"/>
                <feMergeNode in="SourceGraphic"/>
              </feMerge>
            </filter>
          </defs>
          {/* Grid lines */}
          {[0, 25, 50, 75, 100].map((y) => (
            <line
              key={y}
              x1="0"
              y1={300 - (y * 2.4)}
              x2="1000"
              y2={300 - (y * 2.4)}
              stroke="rgba(15, 23, 42, 0.08)"
              strokeWidth="1"
              strokeDasharray="4,4"
            />
          ))}
          {/* Area fill */}
          <path
            d={`M 0 300 ${chartData.points.map((p, i) => `L ${(i / (chartData.points.length - 1 || 1)) * 1000} ${300 - p.y * 2.4}`).join(' ')} L 1000 300 Z`}
            fill="url(#chartGradient)"
            className={styles.chartArea}
          />
          {/* Line */}
          <polyline
            points={chartData.points.map((p, i) => `${(i / (chartData.points.length - 1 || 1)) * 1000},${300 - p.y * 2.4}`).join(' ')}
            fill="none"
            stroke="var(--active-tab-color, #2563eb)"
            strokeWidth="3"
            className={styles.chartLine}
            filter="url(#glow)"
          />
          {/* Data points */}
          {chartData.points.map((point, index) => (
            <g key={index}>
              <circle
                cx={(index / (chartData.points.length - 1 || 1)) * 1000}
                cy={300 - point.y * 2.4}
                r="5"
                fill="var(--active-tab-color, #2563eb)"
                className={styles.chartPoint}
                stroke="#ffffff"
                strokeWidth="2"
              />
              <title>{`${point.date}: ${point.value} clicks`}</title>
            </g>
          ))}
        </svg>
        <div className={styles.chartLabels}>
          <span className={styles.chartLabelStart}>
            {formatDate(chartData.points[0].date)}
          </span>
          <span className={styles.chartLabelEnd}>
            {formatDate(chartData.points[chartData.points.length - 1].date)}
          </span>
        </div>
        <div className={styles.chartStats}>
          <div className={styles.chartStat}>
            <span className={styles.chartStatLabel}>Peak</span>
            <span className={styles.chartStatValue}>{chartData.maxClicks} clicks</span>
          </div>
          <div className={styles.chartStat}>
            <span className={styles.chartStatLabel}>Average</span>
            <span className={styles.chartStatValue}>{averageClicks} clicks/day</span>
          </div>
        </div>
      </div>
    </div>
  );
}


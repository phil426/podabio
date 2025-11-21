import { ArrowSquareOut } from '@phosphor-icons/react';
import { type TabColorTheme } from '../../layout/tab-colors';
import { formatNumber, formatPercentage, truncateUrl } from './analytics-utils';
import styles from '../analytics-dashboard.module.css';

interface TopLink {
  id: number;
  title: string;
  url?: string | null;
  type?: string;
  clicks: number;
  ctr: number;
}

interface TopLinksListProps {
  links: TopLink[];
  activeColor: TabColorTheme;
}

export function TopLinksList({ links, activeColor }: TopLinksListProps): JSX.Element {
  if (!links || links.length === 0) {
    return null;
  }

  const maxClicks = links[0]?.clicks ?? 1;

  return (
    <div className={styles.topLinksSection}>
      <div className={styles.sectionHeader}>
        <div>
          <h2 className={styles.sectionTitle}>Top Performing Links</h2>
          <p className={styles.sectionSubtitle}>Your most clicked links ranked by performance</p>
        </div>
      </div>
      <div className={styles.topLinksGrid}>
        {links.slice(0, 12).map((link, index) => (
          <div key={link.id} className={styles.topLinkCard}>
            <div className={styles.topLinkRank}>{index + 1}</div>
            <div className={styles.topLinkContent}>
              <div className={styles.topLinkHeader}>
                <span className={styles.topLinkTitle}>{link.title}</span>
                <span className={styles.topLinkCtr}>{formatPercentage(link.ctr)} CTR</span>
              </div>
              <div className={styles.topLinkMeta}>
                <span className={styles.topLinkUrl}>
                  {link.url ? (
                    <a 
                      href={link.url} 
                      target="_blank" 
                      rel="noopener noreferrer"
                      className={styles.topLinkUrlLink}
                      onClick={(e) => e.stopPropagation()}
                    >
                      {truncateUrl(link.url, 35)}
                      <ArrowSquareOut className={styles.externalIcon} aria-hidden="true" size={14} weight="regular" />
                    </a>
                  ) : (
                    <span className={styles.topLinkType}>{link.type}</span>
                  )}
                </span>
              </div>
              <div className={styles.topLinkStats}>
                <span className={styles.topLinkClicks}>{formatNumber(link.clicks)} clicks</span>
              </div>
              <div className={styles.topLinkBar}>
                <div 
                  className={styles.topLinkBarFill}
                  style={{ 
                    width: `${(link.clicks / maxClicks) * 100}%` 
                  }}
                />
              </div>
            </div>
          </div>
        ))}
      </div>
    </div>
  );
}


import { useMemo } from 'react';
import { CircleNotch, MagnifyingGlass, Images } from '@phosphor-icons/react';
import type { PodcastSearchResult } from '../../../../api/page';
import { EmptyState } from './EmptyState';
import styles from '../podcast-theme-generator.module.css';

export interface RSSTabProps {
  searchQuery: string;
  searchResults: PodcastSearchResult[];
  selectedPodcast: PodcastSearchResult | null;
  isSearching: boolean;
  onSearchQueryChange: (value: string) => void;
  onSearch: () => void;
  onSelectPodcast: (podcast: PodcastSearchResult) => void;
  searchInputRef?: React.RefObject<HTMLInputElement>;
}

export function RSSTab({
  searchQuery,
  searchResults,
  selectedPodcast,
  isSearching,
  onSearchQueryChange,
  onSearch,
  onSelectPodcast,
  searchInputRef
}: RSSTabProps): JSX.Element {
  const isSearchDisabled = useMemo(
    () => isSearching || searchQuery.trim().length === 0,
    [isSearching, searchQuery]
  );

  return (
    <section className={styles.card}>
      <h3 className={styles.cardTitle}>Search Podcast</h3>
      <div className={styles.cardContent}>
        <div className={styles.searchContainer}>
          <input
            ref={searchInputRef}
            type="text"
            className={styles.searchInput}
            placeholder="Search for a podcast..."
            value={searchQuery}
            onChange={(e) => onSearchQueryChange(e.target.value)}
            onKeyDown={(e) => {
              if (e.key === 'Enter') {
                onSearch();
              }
            }}
            aria-label="Search for podcast"
            aria-describedby="search-help-text"
          />
          <button
            type="button"
            className={styles.searchButton}
            onClick={onSearch}
            disabled={isSearchDisabled}
            aria-label="Search podcasts"
            aria-busy={isSearching}
          >
            {isSearching ? (
              <CircleNotch className={styles.spinner} aria-hidden="true" size={16} weight="regular" />
            ) : (
              <MagnifyingGlass aria-hidden="true" size={16} weight="regular" />
            )}
          </button>
        </div>
        <p id="search-help-text" className={styles.helpText} style={{ fontSize: '12px', color: 'var(--admin-text-secondary, #64748b)', marginTop: '8px' }}>
          Type at least 2 characters and press Enter or click search
        </p>

        {searchResults.length > 0 ? (
          <div className={styles.searchResults}>
            {searchResults.map((podcast) => (
              <div
                key={podcast.id}
                className={`${styles.podcastResult} ${
                  selectedPodcast?.id === podcast.id ? styles.podcastResultSelected : ''
                }`}
                onClick={() => onSelectPodcast(podcast)}
                title={podcast.name}
              >
                {podcast.artwork_url ? (
                  <img
                    src={podcast.artwork_url}
                    alt={podcast.name}
                    className={styles.podcastResultImage}
                  />
                ) : (
                  <div className={styles.podcastResultImagePlaceholder}>
                    <Images aria-hidden="true" size={24} weight="regular" />
                  </div>
                )}
              </div>
            ))}
          </div>
        ) : (
          !isSearching && searchQuery.trim().length >= 2 && (
            <EmptyState type="no-search-results" />
          )
        )}
      </div>
    </section>
  );
}


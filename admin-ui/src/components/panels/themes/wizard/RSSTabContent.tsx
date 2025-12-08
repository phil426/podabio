import { MagnifyingGlass, CircleNotch, Check } from '@phosphor-icons/react';
import styles from '../podcast-theme-generator.module.css';
import type { PodcastSearchResult } from '../../../../api/page';

interface RSSTabContentProps {
    searchQuery: string;
    isSearching: boolean;
    searchResults: PodcastSearchResult[];
    selectedPodcast: PodcastSearchResult | null;
    uploadedImageUrl: string | null;
    onSearchQueryChange: (query: string) => void;
    onSearch: () => void;
    onSelectPodcast: (podcast: PodcastSearchResult) => void;
}

export function RSSTabContent({
    searchQuery,
    isSearching,
    searchResults,
    selectedPodcast,
    uploadedImageUrl,
    onSearchQueryChange,
    onSearch,
    onSelectPodcast
}: RSSTabContentProps) {
    return (
        <div className={styles.tabContent}>
            <div className={styles.searchSection}>
                <div className={styles.inputWrapper}>
                    <MagnifyingGlass size={20} className={styles.searchIcon} aria-hidden="true" />
                    <input
                        type="text"
                        placeholder="Search for a podcast..."
                        className={styles.searchInput}
                        value={searchQuery}
                        onChange={(e) => onSearchQueryChange(e.target.value)}
                        onKeyDown={(e) => e.key === 'Enter' && onSearch()}
                        aria-label="Search for a podcast"
                    />
                    {isSearching && (
                        <div className={styles.searchSpinner}>
                            <CircleNotch size={20} className="fa-spin" />
                        </div>
                    )}
                </div>

                {searchResults.length > 0 && (
                    <div className={styles.searchResults}>
                        {searchResults.map((podcast) => (
                            <button
                                key={podcast.id}
                                className={`${styles.podcastResult} ${selectedPodcast?.id === podcast.id ? styles.podcastResultSelected : ''}`}
                                onClick={() => onSelectPodcast(podcast)}
                                title={`${podcast.name} by ${podcast.artist}`}
                            >
                                <img
                                    src={podcast.artwork_url || '/placeholder-podcast.png'}
                                    alt={podcast.name}
                                    className={styles.podcastResultImage}
                                />
                                {selectedPodcast?.id === podcast.id && (
                                    <div className={styles.selectedIndicator}>
                                        <Check size={16} weight="bold" />
                                    </div>
                                )}
                            </button>
                        ))}
                    </div>
                )}
            </div>

            {selectedPodcast && (
                <div className={styles.selectionPreview}>
                    <div className={styles.previewImageContainer}>
                        <img
                            src={uploadedImageUrl || selectedPodcast.artwork_url || '/placeholder-podcast.png'}
                            alt={selectedPodcast.name}
                            className={styles.previewImage}
                        />
                        <div className={styles.previewOverlay}>
                            <Check size={24} weight="bold" />
                            <span>Selected</span>
                        </div>
                    </div>
                    <div className={styles.previewDetails}>
                        <h3>{selectedPodcast.name}</h3>
                        <p>{selectedPodcast.artist}</p>
                    </div>
                </div>
            )}
        </div>
    );
}

import { Rss, Images } from '@phosphor-icons/react';
import styles from '../podcast-theme-generator.module.css';
import type { TabType } from '../hooks/useThemeWizardState';

interface WizardTabsProps {
    activeTab: TabType;
    onTabChange: (tab: TabType) => void;
}

export function WizardTabs({ activeTab, onTabChange }: WizardTabsProps) {
    return (
        <div className={styles.tabsContainer}>
            <div className={styles.tabs} role="tablist" aria-label="Wizard Mode Selection">
                <button
                    className={`${styles.tab} ${activeTab === 'rss' ? styles.tabActive : ''}`}
                    onClick={() => onTabChange('rss')}
                    role="tab"
                    aria-selected={activeTab === 'rss'}
                    tabIndex={activeTab === 'rss' ? 0 : -1}
                >
                    <Rss size={16} weight={activeTab === 'rss' ? 'fill' : 'regular'} aria-hidden="true" />
                    <span>From Podcast RSS</span>
                </button>
                <button
                    className={`${styles.tab} ${activeTab === 'photo' ? styles.tabActive : ''}`}
                    onClick={() => onTabChange('photo')}
                    role="tab"
                    aria-selected={activeTab === 'photo'}
                    tabIndex={activeTab === 'photo' ? 0 : -1}
                >
                    <Images size={16} weight={activeTab === 'photo' ? 'fill' : 'regular'} aria-hidden="true" />
                    <span>From Photo</span>
                </button>
            </div>
        </div>
    );
}

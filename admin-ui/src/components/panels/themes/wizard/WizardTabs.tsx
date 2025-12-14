import { Rss, Images } from '@phosphor-icons/react';
import type { TabType } from '../hooks/useThemeWizardState';
import { SegmentedControl } from '../../../common/SegmentedControl';
import styles from '../podcast-theme-generator.module.css';

interface WizardTabsProps {
    activeTab: TabType;
    onTabChange: (tab: TabType) => void;
}

export function WizardTabs({ activeTab, onTabChange }: WizardTabsProps) {
    return (
        <div className={styles.tabsContainer}>
            <SegmentedControl
                options={[
                    {
                        value: 'rss',
                        label: 'From Podcast RSS',
                        icon: <Rss size={16} weight={activeTab === 'rss' ? 'fill' : 'regular'} />
                    },
                    {
                        value: 'photo',
                        label: 'From Photo',
                        icon: <Images size={16} weight={activeTab === 'photo' ? 'fill' : 'regular'} />
                    }
                ]}
                value={activeTab}
                onChange={(value) => onTabChange(value as TabType)}
                aria-label="Wizard Mode Selection"
            />
        </div>
    );
}

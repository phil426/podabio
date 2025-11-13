import * as Tabs from '@radix-ui/react-tabs';
import {
  LuLayers,
  LuPalette,
  LuTrendingUp,
  LuBookOpen,
  LuPlug,
  LuSettings
} from 'react-icons/lu';

import { usePageSnapshot, usePublishStateMutation } from '../../api/page';
import { trackTelemetry } from '../../services/telemetry';
import { tabColors, type TabValue } from './tab-colors';

import styles from './tab-bar.module.css';

interface TabBarProps {
  activeTab: TabValue;
  onTabChange: (tab: TabValue) => void;
}

export function TabBar({ activeTab, onTabChange }: TabBarProps): JSX.Element {
  const { data: snapshot } = usePageSnapshot();
  const page = snapshot?.page;
  const publishMutation = usePublishStateMutation();

  const publishStatus = (page?.publish_status ?? 'draft') as 'draft' | 'published' | 'scheduled';
  const activeColor = tabColors[activeTab];

  const handlePublish = () => {
    trackTelemetry({ event: 'tabbar.publish' });
    publishMutation.mutate({ publish_status: 'published' });
  };

  return (
    <div className={styles.tabbar} style={{ '--active-tab-color': activeColor.text, '--active-tab-bg': activeColor.primary, '--active-tab-border': activeColor.border } as React.CSSProperties}>
      <Tabs.Root
        className={styles.tabsRoot}
        value={activeTab}
        onValueChange={(value) => {
          const newTab = (value as TabValue) ?? 'structure';
          onTabChange(newTab);
        }}
      >
        <Tabs.List className={styles.tabList} aria-label="Editor sections">
          <Tabs.Trigger 
            value="structure" 
            className={styles.tabTrigger} 
            data-tab-color={tabColors.structure.text}
            style={{ '--tab-color': tabColors.structure.text } as React.CSSProperties}
          >
            <LuLayers className={styles.tabIcon} aria-hidden="true" />
            <span className={styles.tabLabel}>Layout</span>
          </Tabs.Trigger>
          <Tabs.Trigger 
            value="design" 
            className={styles.tabTrigger} 
            data-tab-color={tabColors.design.text}
            style={{ '--tab-color': tabColors.design.text } as React.CSSProperties}
          >
            <LuPalette className={styles.tabIcon} aria-hidden="true" />
            <span className={styles.tabLabel}>Look</span>
          </Tabs.Trigger>
          <Tabs.Trigger 
            value="analytics" 
            className={styles.tabTrigger} 
            data-tab-color={tabColors.analytics.text}
            style={{ '--tab-color': tabColors.analytics.text } as React.CSSProperties}
          >
            <LuTrendingUp className={styles.tabIcon} aria-hidden="true" />
            <span className={styles.tabLabel}>Analytics</span>
          </Tabs.Trigger>
          <Tabs.Trigger 
            value="blog" 
            className={styles.tabTrigger} 
            data-tab-color={tabColors.blog.text}
            style={{ '--tab-color': tabColors.blog.text } as React.CSSProperties}
          >
            <LuBookOpen className={styles.tabIcon} aria-hidden="true" />
            <span className={styles.tabLabel}>Blog</span>
          </Tabs.Trigger>
          <Tabs.Trigger 
            value="integrations" 
            className={styles.tabTrigger} 
            data-tab-color={tabColors.integrations.text}
            style={{ '--tab-color': tabColors.integrations.text } as React.CSSProperties}
          >
            <LuPlug className={styles.tabIcon} aria-hidden="true" />
            <span className={styles.tabLabel}>Integrations</span>
          </Tabs.Trigger>
          <Tabs.Trigger 
            value="settings" 
            className={styles.tabTrigger} 
            data-tab-color={tabColors.settings.text}
            style={{ '--tab-color': tabColors.settings.text } as React.CSSProperties}
          >
            <LuSettings className={styles.tabIcon} aria-hidden="true" />
            <span className={styles.tabLabel}>Settings</span>
          </Tabs.Trigger>
        </Tabs.List>
      </Tabs.Root>

      <div className={styles.publishSection}>
        <button
          type="button"
          className={styles.publishButton}
          onClick={handlePublish}
          disabled={publishMutation.isPending || publishStatus === 'published'}
        >
          {publishMutation.isPending ? 'Publishing...' : publishStatus === 'published' ? 'Published' : 'Publish'}
        </button>
      </div>
    </div>
  );
}


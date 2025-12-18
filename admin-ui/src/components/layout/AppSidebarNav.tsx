import { useEffect, useMemo } from 'react';
import { motion, AnimatePresence } from 'framer-motion';
import * as ScrollArea from '@radix-ui/react-scroll-area';
import {
  Plus,

  NotePencil,
  ApplePodcastsLogo,
  Plug,
  TrendUp,
  Question,
  User,
  Cube
} from '@phosphor-icons/react';
import * as Tooltip from '@radix-ui/react-tooltip';
import type { Icon } from '@phosphor-icons/react';
import { type AppSidebarTabValue, tabColors, type TabColorTheme } from './tab-colors';
import { AppSidebarTab } from './AppSidebarTab';
import { AppSidebarExpandButton } from './AppSidebarExpandButton';
import { AppSidebarLogo } from './AppSidebarLogo';
import { AppSidebarProfile } from './AppSidebarProfile';
import { ThemeToggle } from '../system/ThemeToggle';
import { useAppSidebarExpanded } from '../../state/appSidebarExpanded';
import styles from './app-sidebar-nav.module.css';

interface AppSidebarNavProps {
  activeTab: AppSidebarTabValue;
  onTabChange: (tab: AppSidebarTabValue) => void;
}

interface TabDefinition {
  value: AppSidebarTabValue;
  label: string;
  Icon: Icon;
}

const TABS: TabDefinition[] = [

  { value: 'page-editor', label: 'Page Editor', Icon: NotePencil },
  { value: 'podcast', label: 'Podcast / rss', Icon: ApplePodcastsLogo },
  { value: 'integration', label: 'Integrations', Icon: Plug },
  { value: 'analytics', label: 'Analytics', Icon: TrendUp },
  { value: 'account', label: 'Account', Icon: User },
];

const COLLAPSED_WIDTH = 64;
const EXPANDED_WIDTH = 280;

export function AppSidebarNav({ activeTab, onTabChange }: AppSidebarNavProps): JSX.Element {
  const { isExpanded } = useAppSidebarExpanded();
  const activeColor = tabColors[activeTab];

  // Keyboard navigation
  useEffect(() => {
    const handleKeyDown = (e: KeyboardEvent) => {
      // Only handle if focus is within the left rail
      const leftRail = document.querySelector(`.${styles.appSidebar}`);
      if (!leftRail || !leftRail.contains(document.activeElement)) return;

      if (e.key === 'ArrowDown' || e.key === 'ArrowUp') {
        e.preventDefault();
        const currentIndex = TABS.findIndex((tab) => tab.value === activeTab);
        let newIndex: number;

        if (e.key === 'ArrowDown') {
          newIndex = (currentIndex + 1) % TABS.length;
        } else {
          newIndex = (currentIndex - 1 + TABS.length) % TABS.length;
        }

        onTabChange(TABS[newIndex].value);

        // Focus the new tab button
        const newTabButton = document.querySelector(
          `button[data-tab="${TABS[newIndex].value}"]`
        ) as HTMLElement;
        newTabButton?.focus();
      }

      // Enter or Space activates tab
      if ((e.key === 'Enter' || e.key === ' ') && document.activeElement?.getAttribute('data-tab')) {
        const tabValue = document.activeElement.getAttribute('data-tab') as AppSidebarTabValue;
        onTabChange(tabValue);
      }
    };

    window.addEventListener('keydown', handleKeyDown);
    return () => window.removeEventListener('keydown', handleKeyDown);
  }, [activeTab, onTabChange]);

  return (
    <Tooltip.Provider delayDuration={0} skipDelayDuration={300}>
      <motion.div
        className={styles.appSidebar}
        initial={false}
        animate={{
          width: isExpanded ? EXPANDED_WIDTH : COLLAPSED_WIDTH,
        }}
        transition={{
          duration: 0.3,
          ease: 'easeOut',
        }}
        style={{ '--left-rail-bg': '#1E293B' } as React.CSSProperties}
        role="navigation"
        aria-label="Main navigation"
      >
        <AppSidebarLogo />
        <ScrollArea.Root className={styles.scrollArea} type="auto">
          <ScrollArea.Viewport className={styles.viewport}>
            <div className={styles.tabsContainer}>
              <div className={styles.expandButtonWrapper}>
                <AppSidebarExpandButton />
              </div>
              <nav className={styles.tabsNav} role="tablist" aria-label="Editor sections">
                {TABS.map((tab) => (
                  <AppSidebarTab
                    key={tab.value}
                    tab={tab.value}
                    label={tab.label}
                    Icon={tab.Icon}
                    isActive={activeTab === tab.value}
                    isExpanded={isExpanded}
                    onClick={() => onTabChange(tab.value)}
                    activeColor={activeColor}
                  />
                ))}
              </nav>
            </div>
          </ScrollArea.Viewport>
          <ScrollArea.Scrollbar orientation="vertical" className={styles.scrollbar}>
            <ScrollArea.Thumb className={styles.thumb} />
          </ScrollArea.Scrollbar>
        </ScrollArea.Root>

        <div className={styles.docsSection}>
          <DocumentationButton />
        </div>

        <AppSidebarProfile />
      </motion.div>
    </Tooltip.Provider>
  );
}

function DocumentationButton(): JSX.Element {
  const { isExpanded } = useAppSidebarExpanded();

  const handleClick = () => {
    // Open user documentation/support in a new tab
    window.open('/support/', '_blank');
  };

  return (
    <Tooltip.Root disableHoverableContent={isExpanded}>
      <Tooltip.Trigger asChild>
        <motion.button
          type="button"
          className={styles.docsButton}
          data-expanded={isExpanded ? 'true' : undefined}
          onClick={handleClick}
          whileHover={{ scale: 1.02 }}
          whileTap={{ scale: 0.98 }}
          aria-label="Open documentation"
        >
          <Question aria-hidden="true" size={isExpanded ? 20 : 24} weight="regular" />
          {isExpanded && (
            <motion.span
              className={styles.docsLabel}
              initial={{ opacity: 0, x: -10 }}
              animate={{ opacity: 1, x: 0 }}
              exit={{ opacity: 0, x: -10 }}
              transition={{ duration: 0.2 }}
            >
              Documentation
            </motion.span>
          )}
        </motion.button>
      </Tooltip.Trigger>
      <Tooltip.Portal>
        {!isExpanded && (
          <Tooltip.Content
            side="right"
            sideOffset={5}
            className="glassTooltip"
            align="center"
          >
            Documentation
            <Tooltip.Arrow className="glassTooltipArrow" />
          </Tooltip.Content>
        )}
      </Tooltip.Portal>
    </Tooltip.Root>
  );
}


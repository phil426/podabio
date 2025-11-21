import { useEffect, useMemo } from 'react';
import { motion, AnimatePresence } from 'framer-motion';
import * as ScrollArea from '@radix-ui/react-scroll-area';
import {
  Plus,
  Stack,
  Palette,
  ApplePodcastsLogo,
  Plug,
  TrendUp,
  Question
} from '@phosphor-icons/react';
import type { Icon } from '@phosphor-icons/react';
import { type LeftyTabValue, tabColors, type TabColorTheme } from './tab-colors';
import { LeftyTabTrigger } from './LeftyTabTrigger';
import { LeftyExpandButton } from './LeftyExpandButton';
import { LeftyAppLogoSection } from './LeftyAppLogoSection';
import { LeftyProfileSection } from './LeftyProfileSection';
import { useLeftRailExpanded } from '../../state/leftRailExpanded';
import styles from './lefty-rail-nav.module.css';

interface LeftRailNavProps {
  activeTab: LeftyTabValue;
  onTabChange: (tab: LeftyTabValue) => void;
}

interface TabDefinition {
  value: LeftyTabValue;
  label: string;
  Icon: Icon;
}

const TABS: TabDefinition[] = [
  { value: 'layers', label: 'Layers', Icon: Stack },
  { value: 'themes', label: 'Themes', Icon: Palette },
  { value: 'podcast', label: 'Podcast', Icon: ApplePodcastsLogo },
  { value: 'integration', label: 'Integration', Icon: Plug },
  { value: 'analytics', label: 'Analytics', Icon: TrendUp },
];

const COLLAPSED_WIDTH = 64;
const EXPANDED_WIDTH = 280;

export function LeftRailNav({ activeTab, onTabChange }: LeftRailNavProps): JSX.Element {
  const { isExpanded } = useLeftRailExpanded();
  const activeColor = tabColors[activeTab];

  // Keyboard navigation
  useEffect(() => {
    const handleKeyDown = (e: KeyboardEvent) => {
      // Only handle if focus is within the left rail
      const leftRail = document.querySelector(`.${styles.leftRail}`);
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
        const tabValue = document.activeElement.getAttribute('data-tab') as LeftyTabValue;
        onTabChange(tabValue);
      }
    };

    window.addEventListener('keydown', handleKeyDown);
    return () => window.removeEventListener('keydown', handleKeyDown);
  }, [activeTab, onTabChange]);

  return (
    <motion.div
      className={styles.leftRail}
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
      <LeftyAppLogoSection />
      <ScrollArea.Root className={styles.scrollArea} type="auto">
        <ScrollArea.Viewport className={styles.viewport}>
          <div className={styles.tabsContainer}>
            <div className={styles.expandButtonWrapper}>
              <LeftyExpandButton />
            </div>
            <nav className={styles.tabsNav} role="tablist" aria-label="Editor sections">
              {TABS.map((tab) => (
                <LeftyTabTrigger
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

      <DocumentationButton />
      <LeftyProfileSection />
    </motion.div>
  );
}

function DocumentationButton(): JSX.Element {
  const { isExpanded } = useLeftRailExpanded();

  const handleClick = () => {
    // Open user documentation/support in a new tab
    window.open('/support/', '_blank');
  };

  return (
    <motion.button
      type="button"
      className={styles.docsButton}
      data-expanded={isExpanded ? 'true' : undefined}
      onClick={handleClick}
      whileHover={{ scale: 1.02 }}
      whileTap={{ scale: 0.98 }}
      aria-label="Open documentation"
      title="Documentation"
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
  );
}


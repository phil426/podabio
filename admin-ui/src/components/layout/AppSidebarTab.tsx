import { motion } from 'framer-motion';
import type { Icon } from '@phosphor-icons/react';
import * as Tooltip from '@radix-ui/react-tooltip';
import { type AppSidebarTabValue, type TabColorTheme } from './tab-colors';
import styles from './app-sidebar-tab.module.css';

interface AppSidebarTabProps {
  tab: AppSidebarTabValue;
  label: string;
  Icon: Icon;
  isActive: boolean;
  isExpanded: boolean;
  onClick: () => void;
  activeColor: TabColorTheme;
}

export function AppSidebarTab({
  tab,
  label,
  Icon,
  isActive,
  isExpanded,
  onClick,
  activeColor
}: AppSidebarTabProps): JSX.Element {
  return (
    <Tooltip.Root disableHoverableContent={isExpanded}>
      <Tooltip.Trigger asChild>
        <motion.button
          type="button"
          className={styles.tabTrigger}
          data-tab={tab}
          data-active={isActive ? 'true' : undefined}
          data-expanded={isExpanded ? 'true' : undefined}
          onClick={onClick}
          whileHover={{ scale: 1 }}
          whileTap={{ scale: 0.95 }}
          style={
            {
              '--active-color': activeColor.text,
              '--active-bg': isActive ? activeColor.primary : undefined,
              '--active-border': activeColor.border
            } as React.CSSProperties
          }
          aria-label={label}
          aria-current={isActive ? 'page' : undefined}
          role="tab"
          aria-selected={isActive}
        >
          <motion.span
            className={styles.iconWrapper}
            whileHover={{ scale: 1.1 }}
            transition={{ duration: 0.2, ease: 'easeOut' }}
          >
            <Icon aria-hidden="true" className={styles.icon} size={20} weight="regular" />
          </motion.span>
          {isExpanded && (
            <motion.span
              className={styles.label}
              initial={{ opacity: 0, x: -10 }}
              animate={{ opacity: 1, x: 0 }}
              exit={{ opacity: 0, x: -10 }}
              transition={{ duration: 0.2 }}
            >
              {label}
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
            {label}
            <Tooltip.Arrow className="glassTooltipArrow" />
          </Tooltip.Content>
        )}
      </Tooltip.Portal>
    </Tooltip.Root>
  );
}


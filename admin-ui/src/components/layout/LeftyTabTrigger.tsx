import { motion } from 'framer-motion';
import type { Icon } from '@phosphor-icons/react';
import { type LeftyTabValue, type TabColorTheme } from './tab-colors';
import styles from './lefty-tab-trigger.module.css';

interface LeftyTabTriggerProps {
  tab: LeftyTabValue;
  label: string;
  Icon: Icon;
  isActive: boolean;
  isExpanded: boolean;
  onClick: () => void;
  activeColor: TabColorTheme;
}

export function LeftyTabTrigger({
  tab,
  label,
  Icon,
  isActive,
  isExpanded,
  onClick,
  activeColor
}: LeftyTabTriggerProps): JSX.Element {
  return (
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
          '--active-bg': isActive ? activeColor.primary : undefined
        } as React.CSSProperties
      }
      aria-label={label}
      aria-current={isActive ? 'page' : undefined}
      title={label}
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
  );
}


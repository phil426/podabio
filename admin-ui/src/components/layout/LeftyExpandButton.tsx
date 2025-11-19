import { motion } from 'framer-motion';
import { SidebarSimple, Sidebar } from '@phosphor-icons/react';
import { useLeftRailExpanded } from '../../state/leftRailExpanded';
import styles from './lefty-expand-button.module.css';

export function LeftyExpandButton(): JSX.Element {
  const { isExpanded, toggleExpanded } = useLeftRailExpanded();

  return (
    <motion.button
      type="button"
      className={styles.expandButton}
      onClick={toggleExpanded}
      aria-label={isExpanded ? 'Collapse navigation' : 'Expand navigation'}
      aria-expanded={isExpanded}
      whileHover={{ scale: 1.05 }}
      whileTap={{ scale: 0.95 }}
      transition={{ duration: 0.2 }}
    >
      <motion.div
        initial={false}
        animate={{ opacity: 1 }}
        transition={{ duration: 0.2 }}
      >
        {isExpanded ? (
          <Sidebar aria-hidden="true" className={styles.icon} size={18} weight="regular" />
        ) : (
          <SidebarSimple aria-hidden="true" className={styles.icon} size={18} weight="regular" />
        )}
      </motion.div>
    </motion.button>
  );
}


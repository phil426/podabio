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
      data-expanded={isExpanded ? 'true' : undefined}
      onClick={toggleExpanded}
      whileHover={{ scale: 1.02 }}
      whileTap={{ scale: 0.98 }}
      aria-label={isExpanded ? 'Collapse navigation' : 'Expand navigation'}
      aria-expanded={isExpanded}
      title={isExpanded ? 'Collapse navigation' : 'Expand navigation'}
    >
      {isExpanded ? (
        <Sidebar 
          aria-hidden="true" 
          size={isExpanded ? 20 : 24} 
          weight="regular" 
        />
      ) : (
        <SidebarSimple 
          aria-hidden="true" 
          size={isExpanded ? 20 : 24} 
          weight="regular" 
        />
      )}
      {isExpanded && (
        <motion.span
          className={styles.label}
          initial={{ opacity: 0, x: -10 }}
          animate={{ opacity: 1, x: 0 }}
          exit={{ opacity: 0, x: -10 }}
          transition={{ duration: 0.2 }}
        >
          Collapse
        </motion.span>
      )}
    </motion.button>
  );
}


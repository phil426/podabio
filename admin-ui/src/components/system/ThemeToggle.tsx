import { Moon, Sun } from '@phosphor-icons/react';
import { motion } from 'framer-motion';
import { useAdminTheme } from '../../design-system/admin-theme/AdminThemeProvider';
import { useLeftRailExpanded } from '../../state/leftRailExpanded';
import styles from './theme-toggle.module.css';

/**
 * ThemeToggle Component
 * 
 * Allows users to toggle between light and dark admin UI themes.
 * Uses --admin-* CSS variables (separate from user page --pod-* variables).
 */
export function ThemeToggle(): JSX.Element {
  const { mode, toggleMode } = useAdminTheme();
  const { isExpanded } = useLeftRailExpanded();
  const isDark = mode === 'dark';

  return (
    <motion.button
      type="button"
      className={styles.toggle}
      data-expanded={isExpanded ? 'true' : undefined}
      onClick={toggleMode}
      whileHover={{ scale: 1.02 }}
      whileTap={{ scale: 0.98 }}
      aria-label={`Switch to ${isDark ? 'light' : 'dark'} mode`}
      title={`Switch to ${isDark ? 'light' : 'dark'} mode`}
    >
      {isDark ? (
        <Sun size={isExpanded ? 18 : 20} weight="regular" />
      ) : (
        <Moon size={isExpanded ? 18 : 20} weight="regular" />
      )}
      {isExpanded && (
        <motion.span
          className={styles.label}
          initial={{ opacity: 0, x: -10 }}
          animate={{ opacity: 1, x: 0 }}
          exit={{ opacity: 0, x: -10 }}
          transition={{ duration: 0.2 }}
        >
          {isDark ? 'Light' : 'Dark'}
        </motion.span>
      )}
    </motion.button>
  );
}


import { motion, AnimatePresence } from 'framer-motion';
import { type LeftyTabValue, type TabColorTheme } from './tab-colors';
import { PodcastPanel } from '../panels/PodcastPanel';
import { IntegrationsPanel } from '../panels/IntegrationsPanel';
import { AnalyticsDashboard } from '../panels/AnalyticsDashboard';
import { SettingsPanel } from '../panels/SettingsPanel';
import { ThemesPanel } from '../panels/ThemesPanel';
import { AccountPanel } from '../panels/AccountPanel';
import { LeftyInspectorDrawer } from '../panels/lefty/LeftyInspectorDrawer';
import styles from './lefty-content-panel.module.css';

interface LeftyContentPanelProps {
  activeTab: LeftyTabValue;
  activeColor: TabColorTheme;
  onTabChange?: (tab: LeftyTabValue) => void;
}

export function LeftyContentPanel({ activeTab, activeColor, onTabChange }: LeftyContentPanelProps): JSX.Element {
  return (
    <div className={styles.container}>
      <AnimatePresence mode="wait">
        {activeTab === 'podcast' && (
          <motion.div
            key="podcast"
            initial={{ opacity: 0, x: 10 }}
            animate={{ opacity: 1, x: 0 }}
            exit={{ opacity: 0, x: -10 }}
            transition={{ duration: 0.25 }}
            className={styles.panel}
          >
            <PodcastPanel activeColor={activeColor} />
          </motion.div>
        )}

        {activeTab === 'integration' && (
          <motion.div
            key="integration"
            initial={{ opacity: 0, x: 10 }}
            animate={{ opacity: 1, x: 0 }}
            exit={{ opacity: 0, x: -10 }}
            transition={{ duration: 0.25 }}
            className={styles.panel}
          >
            <IntegrationsPanel />
          </motion.div>
        )}

        {activeTab === 'analytics' && (
          <motion.div
            key="analytics"
            initial={{ opacity: 0, x: 10 }}
            animate={{ opacity: 1, x: 0 }}
            exit={{ opacity: 0, x: -10 }}
            transition={{ duration: 0.25 }}
            className={styles.panel}
          >
            <AnalyticsDashboard activeColor={activeColor} />
          </motion.div>
        )}



        {activeTab === 'shadow-preview' && (
          <motion.div
            key="shadow-preview"
            initial={{ opacity: 0, x: 10 }}
            animate={{ opacity: 1, x: 0 }}
            exit={{ opacity: 0, x: -10 }}
            transition={{ duration: 0.25 }}
            className={styles.panel}
          >
            <ThemesPanel activeColor={activeColor} />
          </motion.div>
        )}

        {activeTab === 'account' && (
          <motion.div
            key="account"
            initial={{ opacity: 0, x: 10 }}
            animate={{ opacity: 1, x: 0 }}
            exit={{ opacity: 0, x: -10 }}
            transition={{ duration: 0.25 }}
            className={styles.panel}
          >
            <AccountPanel activeColor={activeColor} />
          </motion.div>
        )}
      </AnimatePresence>
      <LeftyInspectorDrawer activeColor={activeColor} activeTab={activeTab} />
    </div>
  );
}


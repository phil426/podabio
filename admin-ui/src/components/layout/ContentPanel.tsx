import { motion, AnimatePresence } from 'framer-motion';
import { type AppSidebarTabValue, type TabColorTheme } from './tab-colors';
import { PodcastPanel } from '../panels/PodcastPanel';
import { IntegrationsPanel } from '../panels/IntegrationsPanel';
import { AnalyticsDashboard } from '../panels/AnalyticsDashboard';
import { SettingsPanel } from '../panels/SettingsPanel';
import { ThemesPanel } from '../panels/ThemesPanel';
import { AccountPanel } from '../panels/AccountPanel';
import { InspectorDrawer } from '../panels/InspectorDrawer';
import styles from './content-panel.module.css';

interface ContentPanelProps {
  activeTab: AppSidebarTabValue;
  activeColor: TabColorTheme;
  onTabChange?: (tab: AppSidebarTabValue) => void;
}

export function ContentPanel({ activeTab, activeColor, onTabChange }: ContentPanelProps): JSX.Element {
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



        {activeTab === 'page-editor' && (
          <motion.div
            key="page-editor"
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
      <InspectorDrawer activeColor={activeColor} activeTab={activeTab} />
    </div>
  );
}


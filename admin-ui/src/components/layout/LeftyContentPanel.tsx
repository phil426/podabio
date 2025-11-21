import { motion, AnimatePresence } from 'framer-motion';
import { type LeftyTabValue, type TabColorTheme } from './tab-colors';
import { LayersPanel } from '../panels/LayersPanel';
import { PodcastPanel } from '../panels/PodcastPanel';
import { IntegrationsPanel } from '../panels/IntegrationsPanel';
import { AnalyticsDashboard } from '../panels/AnalyticsDashboard';
import { SettingsPanel } from '../panels/SettingsPanel';
import { ThemesPanel } from '../panels/ThemesPanel';
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
        {activeTab === 'layers' && (
          <motion.div
            key="layers"
            initial={{ opacity: 0, x: 10 }}
            animate={{ opacity: 1, x: 0 }}
            exit={{ opacity: 0, x: -10 }}
            transition={{ duration: 0.25 }}
            className={styles.panel}
          >
            <LayersPanel activeColor={activeColor} onTabChange={onTabChange} />
          </motion.div>
        )}

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

        {activeTab === 'themes' && (
          <motion.div
            key="themes"
            initial={{ opacity: 0, x: 10 }}
            animate={{ opacity: 1, x: 0 }}
            exit={{ opacity: 0, x: -10 }}
            transition={{ duration: 0.25 }}
            className={styles.panel}
          >
            <ThemesPanel activeColor={activeColor} />
          </motion.div>
        )}
      </AnimatePresence>
      <LeftyInspectorDrawer activeColor={activeColor} activeTab={activeTab} />
    </div>
  );
}


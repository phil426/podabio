import { motion, AnimatePresence } from 'framer-motion';
import { type LeftyTabValue, type TabColorTheme } from './tab-colors';
import { LayersPanel } from '../panels/LayersPanel';
import { ColorsPanel } from '../panels/ColorsPanel';
import { SpecialEffectsPanel } from '../panels/SpecialEffectsPanel';
import { PodcastPanel } from '../panels/PodcastPanel';
import { IntegrationsPanel } from '../panels/IntegrationsPanel';
import { AnalyticsDashboard } from '../panels/AnalyticsDashboard';
import { SettingsPanel } from '../panels/SettingsPanel';
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

        {activeTab === 'colors' && (
          <motion.div
            key="colors"
            initial={{ opacity: 0, x: 10 }}
            animate={{ opacity: 1, x: 0 }}
            exit={{ opacity: 0, x: -10 }}
            transition={{ duration: 0.25 }}
            className={styles.panel}
          >
            <ColorsPanel activeColor={activeColor} />
          </motion.div>
        )}

        {activeTab === 'special-effects' && (
          <motion.div
            key="special-effects"
            initial={{ opacity: 0, x: 10 }}
            animate={{ opacity: 1, x: 0 }}
            exit={{ opacity: 0, x: -10 }}
            transition={{ duration: 0.25 }}
            className={styles.panel}
          >
            <SpecialEffectsPanel activeColor={activeColor} />
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

        {activeTab === 'preview' && (
          <motion.div
            key="preview"
            initial={{ opacity: 0 }}
            animate={{ opacity: 1 }}
            exit={{ opacity: 0 }}
            transition={{ duration: 0.25 }}
            className={styles.panel}
          >
            <div className={styles.previewPlaceholder}>
              <p>Preview mode will open in an overlay</p>
            </div>
          </motion.div>
        )}
      </AnimatePresence>
      <LeftyInspectorDrawer activeColor={activeColor} activeTab={activeTab} />
    </div>
  );
}


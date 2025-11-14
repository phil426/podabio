import { useState, useEffect, useMemo } from 'react';
import * as Tabs from '@radix-ui/react-tabs';
import {
  LuLayers,
  LuPalette,
  LuTrendingUp,
  LuBookOpen,
  LuPlug,
  LuSettings,
  LuGripVertical
} from 'react-icons/lu';
import {
  DndContext,
  PointerSensor,
  useSensor,
  useSensors,
  closestCenter,
  DragEndEvent
} from '@dnd-kit/core';
import {
  SortableContext,
  horizontalListSortingStrategy,
  arrayMove,
  useSortable
} from '@dnd-kit/sortable';
import { CSS } from '@dnd-kit/utilities';

import { tabColors, type TabValue } from './tab-colors';

import styles from './tab-bar.module.css';

interface TabBarProps {
  activeTab: TabValue;
  onTabChange: (tab: TabValue) => void;
}

interface TabDefinition {
  value: TabValue;
  label: string;
  icon: JSX.Element;
}

const DEFAULT_TAB_ORDER: TabValue[] = ['structure', 'design', 'analytics', 'blog', 'integrations', 'settings'];

const TAB_DEFINITIONS: Record<TabValue, TabDefinition> = {
  structure: {
    value: 'structure',
    label: 'Layout',
    icon: <LuLayers className={styles.tabIcon} aria-hidden="true" />
  },
  design: {
    value: 'design',
    label: 'Look',
    icon: <LuPalette className={styles.tabIcon} aria-hidden="true" />
  },
  analytics: {
    value: 'analytics',
    label: 'Analytics',
    icon: <LuTrendingUp className={styles.tabIcon} aria-hidden="true" />
  },
  blog: {
    value: 'blog',
    label: 'Blog',
    icon: <LuBookOpen className={styles.tabIcon} aria-hidden="true" />
  },
  integrations: {
    value: 'integrations',
    label: 'Integrations',
    icon: <LuPlug className={styles.tabIcon} aria-hidden="true" />
  },
  settings: {
    value: 'settings',
    label: 'Settings',
    icon: <LuSettings className={styles.tabIcon} aria-hidden="true" />
  }
};

const STORAGE_KEY = 'tabBarOrder';

function loadTabOrder(): TabValue[] {
  if (typeof window === 'undefined') return DEFAULT_TAB_ORDER;
  try {
    const stored = localStorage.getItem(STORAGE_KEY);
    if (stored) {
      const parsed = JSON.parse(stored) as TabValue[];
      // Validate that all tabs are present
      const allTabs = new Set(parsed);
      const defaultTabs = new Set(DEFAULT_TAB_ORDER);
      if (allTabs.size === defaultTabs.size && [...allTabs].every(tab => defaultTabs.has(tab))) {
        return parsed;
      }
    }
  } catch {
    // Ignore errors
  }
  return DEFAULT_TAB_ORDER;
}

function saveTabOrder(order: TabValue[]): void {
  if (typeof window === 'undefined') return;
  try {
    localStorage.setItem(STORAGE_KEY, JSON.stringify(order));
  } catch {
    // Ignore storage errors
  }
}

interface SortableTabTriggerProps {
  tab: TabDefinition;
  activeTab: TabValue;
  onTabChange: (tab: TabValue) => void;
}

function SortableTabTrigger({ tab, activeTab, onTabChange }: SortableTabTriggerProps): JSX.Element {
  const { attributes, listeners, setNodeRef, transform, transition, isDragging } = useSortable({
    id: tab.value
  });

  const style = {
    transform: CSS.Transform.toString(transform),
    transition,
    opacity: isDragging ? 0.5 : 1
  };

  return (
    <Tabs.Trigger
      ref={setNodeRef}
      value={tab.value}
      className={styles.tabTrigger}
      data-tab-color={tabColors[tab.value].text}
      data-dragging={isDragging ? 'true' : undefined}
      style={{ '--tab-color': tabColors[tab.value].text, ...style } as React.CSSProperties}
      onClick={() => onTabChange(tab.value)}
    >
      <span 
        className={styles.gripIcon}
        {...attributes}
        {...listeners}
        aria-hidden="true"
        onClick={(e) => e.stopPropagation()}
      >
        <LuGripVertical />
      </span>
      {tab.icon}
      <span className={styles.tabLabel}>{tab.label}</span>
    </Tabs.Trigger>
  );
}

export function TabBar({ activeTab, onTabChange }: TabBarProps): JSX.Element {
  const activeColor = tabColors[activeTab];
  const [tabOrder, setTabOrder] = useState<TabValue[]>(loadTabOrder);

  useEffect(() => {
    setTabOrder(loadTabOrder());
  }, []);

  const orderedTabs = useMemo(() => {
    return tabOrder.map(value => TAB_DEFINITIONS[value]).filter(Boolean) as TabDefinition[];
  }, [tabOrder]);

  const sensors = useSensors(
    useSensor(PointerSensor, {
      activationConstraint: { distance: 6 }
    })
  );

  const handleDragEnd = ({ active, over }: DragEndEvent) => {
    if (!over || active.id === over.id) return;
    
    const oldIndex = tabOrder.findIndex(tab => tab === active.id);
    const newIndex = tabOrder.findIndex(tab => tab === over.id);
    
    if (oldIndex === -1 || newIndex === -1) return;
    
    const newOrder = arrayMove(tabOrder, oldIndex, newIndex);
    setTabOrder(newOrder);
    saveTabOrder(newOrder);
  };

  return (
    <div className={styles.tabbar} style={{ '--active-tab-color': activeColor.text, '--active-tab-bg': activeColor.primary, '--active-tab-border': activeColor.border } as React.CSSProperties}>
      <Tabs.Root
        className={styles.tabsRoot}
        value={activeTab}
        onValueChange={(value) => {
          const newTab = (value as TabValue) ?? 'structure';
          onTabChange(newTab);
        }}
      >
        <DndContext sensors={sensors} collisionDetection={closestCenter} onDragEnd={handleDragEnd}>
          <SortableContext items={tabOrder} strategy={horizontalListSortingStrategy}>
            <Tabs.List className={styles.tabList} aria-label="Editor sections">
              {orderedTabs.map((tab) => (
                <SortableTabTrigger
                  key={tab.value}
                  tab={tab}
                  activeTab={activeTab}
                  onTabChange={onTabChange}
                />
              ))}
            </Tabs.List>
          </SortableContext>
        </DndContext>
      </Tabs.Root>
    </div>
  );
}


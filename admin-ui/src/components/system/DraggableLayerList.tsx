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
  verticalListSortingStrategy,
  arrayMove,
  useSortable
} from '@dnd-kit/sortable';
import { CSS } from '@dnd-kit/utilities';
import { useEffect, useState, type ReactNode } from 'react';
import { LuGripHorizontal } from 'react-icons/lu';

import styles from './draggable-layer-list.module.css';

export interface LayerItem {
  id: string;
  label: string;
  description: string;
  icon?: ReactNode;
  thumbnail?: string;
  displayOrder?: number;
  isActive?: boolean;
  isLocked?: boolean;
  isFeatured?: boolean;
}

interface DraggableLayerListProps {
  items: LayerItem[];
  onReorder?: (items: LayerItem[]) => void;
  startIndex?: number;
  renderActions?: (item: LayerItem) => JSX.Element | null;
  onSelect?: (item: LayerItem) => void;
  selectedId?: string | null;
}

export function DraggableLayerList({
  items,
  onReorder,
  startIndex = 0,
  renderActions,
  onSelect,
  selectedId
}: DraggableLayerListProps): JSX.Element {
  const [internalItems, setInternalItems] = useState(items);

  useEffect(() => {
    setInternalItems(items);
  }, [items]);

  const sensors = useSensors(
    useSensor(PointerSensor, {
      activationConstraint: { distance: 6 }
    })
  );

  const handleDragEnd = ({ active, over }: DragEndEvent) => {
    if (!over || active.id === over.id) return;
    
    // Don't allow dragging locked items
    const activeItem = internalItems.find((item) => item.id === active.id);
    const overItem = internalItems.find((item) => item.id === over.id);
    if (activeItem?.isLocked || overItem?.isLocked) return;
    
    setInternalItems((previous) => {
      const oldIndex = previous.findIndex((item) => item.id === active.id);
      const newIndex = previous.findIndex((item) => item.id === over.id);
      const next = arrayMove(previous, oldIndex, newIndex);
      
      // Only reorder unlocked items
      const unlockedItems = next.filter((item) => !item.isLocked);
      onReorder?.(
        unlockedItems.map((layer, idx) => ({
          ...layer,
          displayOrder: startIndex + idx
        }))
      );
      return next;
    });
  };

  return (
    <DndContext sensors={sensors} collisionDetection={closestCenter} onDragEnd={handleDragEnd}>
      <SortableContext items={internalItems} strategy={verticalListSortingStrategy}>
        <ul className={styles.list}>
          {internalItems.map((item) => (
            <SortableLayerItem
              key={item.id}
              item={item}
              renderActions={renderActions}
              onSelect={onSelect}
              isSelected={selectedId === item.id}
            />
          ))}
        </ul>
      </SortableContext>
    </DndContext>
  );
}

interface SortableLayerItemProps {
  item: LayerItem;
  renderActions?: (item: LayerItem) => JSX.Element | null;
  onSelect?: (item: LayerItem) => void;
  isSelected?: boolean;
}

function SortableLayerItem({ item, renderActions, onSelect, isSelected }: SortableLayerItemProps): JSX.Element {
  const isPodcastPlayer = item.id === 'page:podcast-player';
  const isDraggable = !item.isLocked && !isPodcastPlayer;
  
  const { attributes, listeners, setNodeRef, transform, transition, isDragging } = useSortable({
    id: item.id,
    disabled: !isDraggable
  });

  const style = {
    transform: CSS.Transform.toString(transform),
    transition,
    zIndex: isDragging ? 2 : undefined
  };

  const handleSelect = () => {
    onSelect?.(item);
  };

  return (
    <li
      ref={setNodeRef}
      style={style}
      className={`${styles.item} ${isSelected ? styles.itemSelected : ''} ${item.isLocked ? styles.itemLocked : ''} ${isPodcastPlayer ? styles.itemNoGrip : ''}`}
      {...(isDraggable ? { ...attributes, ...listeners } : {})}
      data-dnd-kit-dragging={isDragging ? 'true' : undefined}
      data-locked={item.isLocked ? 'true' : undefined}
      data-no-grip={isPodcastPlayer ? 'true' : undefined}
    >
      {!isPodcastPlayer && (
      <span className={styles.gripIcon} aria-hidden="true">
        <LuGripHorizontal />
      </span>
      )}
      {item.thumbnail ? (
        <span className={styles.thumbnail} aria-hidden="true">
          <img src={item.thumbnail} alt="" />
        </span>
      ) : item.icon ? (
        <span 
          className={styles.icon} 
          aria-hidden="true"
          data-active={item.isActive ? 'true' : 'false'}
        >
          {item.icon}
        </span>
      ) : null}
      <div className={styles.text} onClick={handleSelect} role="button" tabIndex={0} onKeyDown={(event) => {
        if (event.key === 'Enter' || event.key === ' ') {
          event.preventDefault();
          handleSelect();
        }
      }}>
        <p className={styles.label}>{item.label}</p>
        <p className={styles.description}>{item.description}</p>
      </div>
      {renderActions ? renderActions(item) : (
        <button type="button" className={styles.quickAction} onClick={handleSelect}>
          Focus
        </button>
      )}
    </li>
  );
}


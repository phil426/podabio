/**
 * Context Menu Component
 * Displays a context menu for widget actions with accessibility and mobile support
 */

import { useEffect, useRef, useState } from 'react';
import { Pencil, Palette, Eye, EyeSlash, Trash, Lock, LockOpen } from '@phosphor-icons/react';
import styles from './context-menu.module.css';

export interface ContextMenuOption {
  label: string;
  icon?: JSX.Element;
  action: () => void;
  ariaLabel?: string;
  disabled?: boolean;
}

interface ContextMenuProps {
  x: number;
  y: number;
  options: ContextMenuOption[];
  onClose: () => void;
  widgetTitle?: string;
  previewContainerRef?: React.RefObject<HTMLElement>;
}

export function ContextMenu({ x, y, options, onClose, widgetTitle, previewContainerRef }: ContextMenuProps): JSX.Element {
  const menuRef = useRef<HTMLDivElement>(null);
  const [position, setPosition] = useState({ x, y });
  const [focusedIndex, setFocusedIndex] = useState<number | null>(null);

  // Position menu relative to preview container
  useEffect(() => {
    if (!menuRef.current) return;

    const menuWidth = 220; // Match min-width in CSS
    const menuHeight = options.length * 48 + 12; // 48px per item + padding (6px top + 6px bottom)
    
    // If preview container ref is provided, position relative to it
    if (previewContainerRef?.current) {
      const containerRect = previewContainerRef.current.getBoundingClientRect();
      
      // Horizontally centered above the preview
      const adjustedX = containerRect.left + (containerRect.width / 2) - (menuWidth / 2);
      
      // Vertically positioned a little above center (40% from top of container)
      const adjustedY = containerRect.top + (containerRect.height * 0.4) - (menuHeight / 2);
      
      setPosition({ x: adjustedX, y: adjustedY });
    } else {
      // Fallback to click-based positioning if no container ref
      const viewportWidth = window.innerWidth;
      const viewportHeight = window.innerHeight;
      const safeAreaBottom = 34; // Home indicator height on mobile
      const padding = 16; // Minimum distance from edges

      let adjustedX = x;
      let adjustedY = y;

      // Position menu to the right and below click point by default
      // But adjust if it would go off-screen
      if (x + menuWidth + padding > viewportWidth) {
        // Position to the left of click point
        adjustedX = x - menuWidth - 8;
      } else {
        // Position to the right of click point
        adjustedX = x + 8;
      }

      // Ensure minimum distance from left edge
      if (adjustedX < padding) {
        adjustedX = padding;
      }

      // Ensure minimum distance from right edge
      if (adjustedX + menuWidth > viewportWidth - padding) {
        adjustedX = viewportWidth - menuWidth - padding;
      }

      // Position below click point, but adjust if near bottom
      if (y + menuHeight + padding > viewportHeight - safeAreaBottom) {
        // Position above click point
        adjustedY = y - menuHeight - 8;
      } else {
        // Position below click point
        adjustedY = y + 8;
      }

      // Ensure minimum distance from top edge
      if (adjustedY < padding) {
        adjustedY = padding;
      }

      // Ensure minimum distance from bottom edge
      if (adjustedY + menuHeight > viewportHeight - safeAreaBottom - padding) {
        adjustedY = viewportHeight - menuHeight - safeAreaBottom - padding;
      }

      setPosition({ x: adjustedX, y: adjustedY });
    }
  }, [x, y, options.length, previewContainerRef]);


  // Focus first item when menu opens
  useEffect(() => {
    if (menuRef.current && focusedIndex === null) {
      const firstItem = menuRef.current.querySelector('[role="menuitem"]:not([disabled])') as HTMLElement;
      firstItem?.focus();
      const firstIndex = Array.from(menuRef.current.querySelectorAll('[role="menuitem"]')).indexOf(firstItem);
      setFocusedIndex(firstIndex >= 0 ? firstIndex : 0);
    }
  }, [focusedIndex]);

  // Handle keyboard navigation
  const handleKeyDown = (e: React.KeyboardEvent, index: number) => {
    const items = Array.from(menuRef.current?.querySelectorAll('[role="menuitem"]:not([disabled])') || []);
    const currentIndex = items.indexOf(e.currentTarget as HTMLElement);

    switch (e.key) {
      case 'ArrowDown':
        e.preventDefault();
        const nextIndex = (currentIndex + 1) % items.length;
        (items[nextIndex] as HTMLElement)?.focus();
        setFocusedIndex(nextIndex);
        break;
      case 'ArrowUp':
        e.preventDefault();
        const prevIndex = (currentIndex - 1 + items.length) % items.length;
        (items[prevIndex] as HTMLElement)?.focus();
        setFocusedIndex(prevIndex);
        break;
      case 'Escape':
        e.preventDefault();
        onClose();
        break;
      case 'Enter':
      case ' ':
        e.preventDefault();
        (e.currentTarget as HTMLElement).click();
        break;
    }
  };

  // Close on outside click
  useEffect(() => {
    const handleClickOutside = (e: MouseEvent) => {
      if (menuRef.current && !menuRef.current.contains(e.target as Node)) {
        onClose();
      }
    };

    const handleEscape = (e: KeyboardEvent) => {
      if (e.key === 'Escape') {
        onClose();
      }
    };

    // Use setTimeout to avoid immediate close on the click that opened the menu
    const timeout = setTimeout(() => {
      document.addEventListener('mousedown', handleClickOutside as EventListener);
      document.addEventListener('touchstart', handleClickOutside as EventListener);
    }, 0);

    document.addEventListener('keydown', handleEscape as EventListener);

    return () => {
      clearTimeout(timeout);
      document.removeEventListener('mousedown', handleClickOutside as EventListener);
      document.removeEventListener('touchstart', handleClickOutside as EventListener);
      document.removeEventListener('keydown', handleEscape as EventListener);
    };
  }, [onClose]);

  const handleOptionClick = (option: ContextMenuOption) => {
    if (!option.disabled) {
      option.action();
      onClose();
    }
  };

  return (
    <div
        ref={menuRef}
        className={styles.contextMenu}
        role="menu"
        aria-label={widgetTitle ? `Actions for ${widgetTitle}` : 'Widget actions'}
        aria-orientation="vertical"
        style={{
          left: `${position.x}px`,
          top: `${position.y}px`
        }}
      >
      {options.map((option, index) => (
        <button
          key={index}
          type="button"
          role="menuitem"
          className={styles.menuItem}
          aria-label={option.ariaLabel || option.label}
          disabled={option.disabled}
          onClick={() => handleOptionClick(option)}
          onKeyDown={(e) => handleKeyDown(e, index)}
          tabIndex={focusedIndex === index ? 0 : -1}
        >
          {option.icon && <span className={styles.menuIcon} aria-hidden="true">{option.icon}</span>}
          <span className={styles.menuLabel}>{option.label}</span>
        </button>
      ))}
    </div>
  );
}


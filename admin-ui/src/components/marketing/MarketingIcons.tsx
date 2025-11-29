import { useEffect } from 'react';
import { createRoot } from 'react-dom/client';
import {
  Play,
  Headphones,
  Sparkle,
  Broadcast,
  Rss,
  MusicNote,
  Palette,
  ChartBar,
  Envelope,
  Check,
  Plus,
  Minus,
  X,
  Folder,
} from '@phosphor-icons/react';

interface IconReplacement {
  selector: string;
  Icon: React.ComponentType<{ size?: number; weight?: string; className?: string }>;
  size?: number;
  weight?: 'thin' | 'light' | 'regular' | 'bold' | 'fill' | 'duotone';
}

const iconReplacements: IconReplacement[] = [
  { selector: '.icon-play', Icon: Play, size: 20, weight: 'fill' },
  { selector: '.icon-headphones', Icon: Headphones, size: 32, weight: 'regular' },
  { selector: '.icon-sparkle', Icon: Sparkle, size: 32, weight: 'regular' },
  { selector: '.icon-broadcast', Icon: Broadcast, size: 32, weight: 'regular' },
  { selector: '.icon-rss', Icon: Rss, size: 24, weight: 'regular' },
  { selector: '.icon-music', Icon: MusicNote, size: 24, weight: 'regular' },
  { selector: '.icon-palette', Icon: Palette, size: 24, weight: 'regular' },
  { selector: '.icon-chart', Icon: ChartBar, size: 24, weight: 'regular' },
  { selector: '.icon-envelope', Icon: Envelope, size: 24, weight: 'regular' },
  { selector: '.icon-check', Icon: Check, size: 16, weight: 'bold' },
  { selector: '.icon-plus', Icon: Plus, size: 20, weight: 'regular' },
  { selector: '.icon-minus', Icon: Minus, size: 20, weight: 'regular' },
  { selector: '.icon-close', Icon: X, size: 24, weight: 'regular' },
  { selector: '.icon-folder', Icon: Folder, size: 16, weight: 'regular' },
];

function renderIcon(
  element: Element,
  Icon: React.ComponentType<{ size?: number; weight?: string; className?: string }>,
  size: number,
  weight: string
) {
  // Skip if already has an icon
  if (element.querySelector('svg[data-phosphor-icon]')) {
    return;
  }

  // Clear any text content
  element.textContent = '';
  
  // Create a container div for React
  const iconContainer = document.createElement('div');
  iconContainer.style.display = 'inline-flex';
  iconContainer.style.alignItems = 'center';
  iconContainer.style.verticalAlign = 'middle';
  
  try {
    const root = createRoot(iconContainer);
    root.render(<Icon size={size} weight={weight as any} data-phosphor-icon="true" />);
    
    // Append the icon container to the element
    element.appendChild(iconContainer);
  } catch (error) {
    console.error(`Failed to render icon:`, error);
  }
}

export function MarketingIcons(): null {
  useEffect(() => {
    const renderAllIcons = () => {
      iconReplacements.forEach(({ selector, Icon, size = 24, weight = 'regular' }) => {
        const elements = document.querySelectorAll(selector);
        elements.forEach((element) => {
          // Skip if already has an icon
          if (element.querySelector('svg[data-phosphor-icon]')) {
            return;
          }
          renderIcon(element, Icon, size, weight);
        });
      });
    };

    // Initial render with delay to ensure DOM is ready
    const timeoutId = setTimeout(renderAllIcons, 200);

    // Listen for icon update events (from accordion toggles)
    const handleIconUpdate = (event: CustomEvent) => {
      const element = event.detail?.element;
      if (element) {
        // Remove existing icon container
        const existingContainer = element.querySelector('div');
        if (existingContainer) {
          existingContainer.remove();
        }
        // Re-render with correct icon
        const isMinus = element.classList.contains('icon-minus');
        const Icon = isMinus ? Minus : Plus;
        renderIcon(element, Icon, 20, 'regular');
      }
    };

    document.addEventListener('icon-update', handleIconUpdate as EventListener);

    // Also watch for class changes on accordion icons
    const observer = new MutationObserver((mutations) => {
      mutations.forEach((mutation) => {
        if (mutation.type === 'attributes' && mutation.attributeName === 'class') {
          const target = mutation.target as Element;
          if (target.classList.contains('icon-plus') || target.classList.contains('icon-minus')) {
            // Remove existing icon container
            const existingContainer = target.querySelector('div');
            if (existingContainer) {
              existingContainer.remove();
            }
            // Re-render
            const isMinus = target.classList.contains('icon-minus');
            const Icon = isMinus ? Minus : Plus;
            renderIcon(target, Icon, 20, 'regular');
          }
        }
      });
    });

    observer.observe(document.body, {
      childList: true,
      subtree: true,
      attributes: true,
      attributeFilter: ['class'],
    });

    return () => {
      clearTimeout(timeoutId);
      observer.disconnect();
      document.removeEventListener('icon-update', handleIconUpdate as EventListener);
    };
  }, []);

  return null;
}


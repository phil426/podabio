import { useState, useEffect, useRef } from 'react';
import * as ScrollArea from '@radix-ui/react-scroll-area';
import { CaretRight, FileText, Folder, FolderOpen } from '@phosphor-icons/react';
import clsx from 'clsx';
import styles from './documentation-sidebar.module.css';

interface DocItem {
  name: string;
  type: 'file' | 'folder';
  path: string;
  children?: DocItem[];
}

interface DocumentationSidebarProps {
  selectedDoc: string | null;
  onDocSelect: (path: string) => void;
}

// Helper to find first document in structure
function findFirstDoc(items: DocItem[]): string | null {
  for (const item of items) {
    if (item.type === 'file') {
      return item.path;
    }
    if (item.type === 'folder' && item.children) {
      const found = findFirstDoc(item.children);
      if (found) return found;
    }
  }
  return null;
}

export function DocumentationSidebar({ selectedDoc, onDocSelect }: DocumentationSidebarProps): JSX.Element {
  const [structure, setStructure] = useState<DocItem[]>([]);
  const [expandedFolders, setExpandedFolders] = useState<Set<string>>(new Set());
  const [loading, setLoading] = useState(true);
  const hasAutoSelectedRef = useRef(false);

  useEffect(() => {
    fetch('/api/docs.php?action=list')
      .then((res) => res.json())
      .then((data) => {
        if (data.success) {
          setStructure(data.structure);
          // Auto-expand folder containing selected doc
          if (selectedDoc) {
            const folderPath = selectedDoc.split('/').slice(0, -1).join('/');
            if (folderPath) {
              setExpandedFolders(new Set([folderPath]));
            }
          } else if (!hasAutoSelectedRef.current) {
            // Auto-select first document if none selected (only once)
            const firstDoc = findFirstDoc(data.structure);
            if (firstDoc) {
              hasAutoSelectedRef.current = true;
              onDocSelect(firstDoc);
            }
          }
        }
        setLoading(false);
      })
      .catch((err) => {
        console.error('Failed to load documentation structure:', err);
        setLoading(false);
      });
  }, [selectedDoc, onDocSelect]);

  const toggleFolder = (path: string) => {
    const newExpanded = new Set(expandedFolders);
    if (newExpanded.has(path)) {
      newExpanded.delete(path);
    } else {
      newExpanded.add(path);
    }
    setExpandedFolders(newExpanded);
  };

  const renderItem = (item: DocItem, depth = 0): JSX.Element => {
    if (item.type === 'folder') {
      const isExpanded = expandedFolders.has(item.path);
      const hasChildren = item.children && item.children.length > 0;

      return (
        <div key={item.path} className={styles.folderItem}>
          <button
            type="button"
            className={styles.folderButton}
            onClick={() => toggleFolder(item.path)}
            style={{ paddingLeft: `${depth * 1.25 + 0.75}rem` }}
          >
            <span className={styles.folderIcon}>
              {isExpanded ? (
                <FolderOpen size={16} weight="regular" />
              ) : (
                <Folder size={16} weight="regular" />
              )}
            </span>
            <CaretRight
              size={12}
              weight="regular"
              className={clsx(styles.caret, isExpanded && styles.caretExpanded)}
            />
            <span className={styles.folderName}>{item.name}</span>
          </button>
          {isExpanded && hasChildren && (
            <div className={styles.folderChildren}>
              {item.children!.map((child) => renderItem(child, depth + 1))}
            </div>
          )}
        </div>
      );
    }

    // File item
    const isSelected = selectedDoc === item.path;
    return (
      <button
        key={item.path}
        type="button"
        className={clsx(styles.fileItem, isSelected && styles.fileItemSelected)}
        onClick={() => onDocSelect(item.path)}
        style={{ paddingLeft: `${depth * 1.25 + 0.75}rem` }}
      >
        <FileText size={16} weight="regular" className={styles.fileIcon} />
        <span className={styles.fileName}>{item.name.replace('.md', '')}</span>
      </button>
    );
  };

  if (loading) {
    return (
      <div className={styles.sidebar}>
        <div className={styles.loading}>Loading documentation...</div>
      </div>
    );
  }

  return (
    <div className={styles.sidebar}>
      <div className={styles.sidebarHeader}>
        <h2 className={styles.sidebarTitle}>Documentation</h2>
      </div>
      <ScrollArea.Root className={styles.scrollArea}>
        <ScrollArea.Viewport className={styles.viewport}>
          <nav className={styles.nav}>
            {structure.map((item) => renderItem(item))}
          </nav>
        </ScrollArea.Viewport>
        <ScrollArea.Scrollbar orientation="vertical" className={styles.scrollbar}>
          <ScrollArea.Thumb className={styles.thumb} />
        </ScrollArea.Scrollbar>
      </ScrollArea.Root>
    </div>
  );
}


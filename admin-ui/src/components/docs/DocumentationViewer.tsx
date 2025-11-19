import { useState, useEffect } from 'react';
import { useSearchParams } from 'react-router-dom';
import { Panel, PanelGroup, PanelResizeHandle } from 'react-resizable-panels';
import { DocumentationSidebar } from './DocumentationSidebar';
import { DocumentationContent } from './DocumentationContent';
import { DocumentationTOC } from './DocumentationTOC';
import styles from './documentation-viewer.module.css';

export function DocumentationViewer(): JSX.Element {
  const [searchParams, setSearchParams] = useSearchParams();
  const [selectedDoc, setSelectedDoc] = useState<string | null>(null);
  const [headings, setHeadings] = useState<Array<{ id: string; text: string; level: number }>>([]);

  // Get document from URL params or default to first available
  useEffect(() => {
    const docParam = searchParams.get('doc');
    if (docParam) {
      setSelectedDoc(docParam);
    }
    // Note: Default document selection handled in DocumentationSidebar
  }, [searchParams]);

  const handleDocSelect = (docPath: string) => {
    setSelectedDoc(docPath);
    setSearchParams({ doc: docPath });
  };

  return (
    <div className={styles.viewer}>
      <PanelGroup direction="horizontal" className={styles.panelGroup}>
        {/* Left Sidebar */}
        <Panel defaultSize={20} minSize={15} maxSize={30} className={styles.sidebarPanel}>
          <DocumentationSidebar
            selectedDoc={selectedDoc}
            onDocSelect={handleDocSelect}
          />
        </Panel>

        <PanelResizeHandle className={styles.resizeHandle} />

        {/* Center Content */}
        <Panel defaultSize={60} minSize={40} className={styles.contentPanel}>
          <DocumentationContent
            docPath={selectedDoc}
            onHeadingsChange={setHeadings}
          />
        </Panel>

        <PanelResizeHandle className={styles.resizeHandle} />

        {/* Right TOC */}
        <Panel defaultSize={20} minSize={15} maxSize={25} className={styles.tocPanel}>
          <DocumentationTOC headings={headings} />
        </Panel>
      </PanelGroup>
    </div>
  );
}


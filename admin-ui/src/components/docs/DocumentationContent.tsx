import { useState, useEffect, useRef } from 'react';
import ReactMarkdown from 'react-markdown';
import remarkGfm from 'remark-gfm';
import rehypeSlug from 'rehype-slug';
import rehypeAutolinkHeadings from 'rehype-autolink-headings';
import * as ScrollArea from '@radix-ui/react-scroll-area';
import styles from './documentation-content.module.css';

interface DocumentationContentProps {
  docPath: string | null;
  onHeadingsChange: (headings: Array<{ id: string; text: string; level: number }>) => void;
}

export function DocumentationContent({ docPath, onHeadingsChange }: DocumentationContentProps): JSX.Element {
  const [content, setContent] = useState<string>('');
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const contentRef = useRef<HTMLDivElement>(null);

  useEffect(() => {
    if (!docPath) {
      setContent('');
      onHeadingsChange([]);
      return;
    }

    setLoading(true);
    setError(null);

    fetch(`/api/docs.php?action=get&file=${encodeURIComponent(docPath)}`)
      .then((res) => res.json())
      .then((data) => {
        if (data.success) {
          setContent(data.content);
        } else {
          setError(data.error || 'Failed to load document');
        }
        setLoading(false);
      })
      .catch((err) => {
        console.error('Failed to load document:', err);
        setError('Failed to load document');
        setLoading(false);
      });
  }, [docPath, onHeadingsChange]);

  // Extract headings from rendered content
  useEffect(() => {
    if (!contentRef.current) return;

    const headings: Array<{ id: string; text: string; level: number }> = [];
    const headingElements = contentRef.current.querySelectorAll('h2, h3, h4');

    headingElements.forEach((heading) => {
      const id = heading.id || heading.textContent?.toLowerCase().replace(/\s+/g, '-') || '';
      const text = heading.textContent || '';
      const level = parseInt(heading.tagName.charAt(1), 10);

      headings.push({ id, text, level });
    });

    onHeadingsChange(headings);
  }, [content, onHeadingsChange]);

  if (!docPath) {
    return (
      <div className={styles.content}>
        <div className={styles.emptyState}>
          <h2>Select a document</h2>
          <p>Choose a document from the sidebar to view its content.</p>
        </div>
      </div>
    );
  }

  if (loading) {
    return (
      <div className={styles.content}>
        <div className={styles.loading}>Loading document...</div>
      </div>
    );
  }

  if (error) {
    return (
      <div className={styles.content}>
        <div className={styles.error}>
          <h2>Error</h2>
          <p>{error}</p>
        </div>
      </div>
    );
  }

  return (
    <div className={styles.content}>
      <ScrollArea.Root className={styles.scrollArea}>
        <ScrollArea.Viewport className={styles.viewport}>
          <div ref={contentRef} className={styles.markdownContent}>
            <ReactMarkdown
              remarkPlugins={[remarkGfm]}
              rehypePlugins={[
                rehypeSlug,
                [
                  rehypeAutolinkHeadings,
                  {
                    behavior: 'wrap',
                    properties: {
                      className: [styles.headingLink],
                    },
                  },
                ],
              ]}
              components={{
                h1: ({ node, ...props }) => <h1 className={styles.h1} {...props} />,
                h2: ({ node, ...props }) => <h2 className={styles.h2} {...props} />,
                h3: ({ node, ...props }) => <h3 className={styles.h3} {...props} />,
                h4: ({ node, ...props }) => <h4 className={styles.h4} {...props} />,
                p: ({ node, ...props }) => <p className={styles.p} {...props} />,
                ul: ({ node, ...props }) => <ul className={styles.ul} {...props} />,
                ol: ({ node, ...props }) => <ol className={styles.ol} {...props} />,
                li: ({ node, ...props }) => <li className={styles.li} {...props} />,
                code: ({ node, inline, ...props }: any) => {
                  if (inline) {
                    return <code className={styles.inlineCode} {...props} />;
                  }
                  return <code className={styles.codeBlock} {...props} />;
                },
                pre: ({ node, ...props }) => <pre className={styles.pre} {...props} />,
                blockquote: ({ node, ...props }) => <blockquote className={styles.blockquote} {...props} />,
                table: ({ node, ...props }) => <table className={styles.table} {...props} />,
                thead: ({ node, ...props }) => <thead className={styles.thead} {...props} />,
                tbody: ({ node, ...props }) => <tbody className={styles.tbody} {...props} />,
                tr: ({ node, ...props }) => <tr className={styles.tr} {...props} />,
                th: ({ node, ...props }) => <th className={styles.th} {...props} />,
                td: ({ node, ...props }) => <td className={styles.td} {...props} />,
                a: ({ node, ...props }) => <a className={styles.a} {...props} />,
                strong: ({ node, ...props }) => <strong className={styles.strong} {...props} />,
                em: ({ node, ...props }) => <em className={styles.em} {...props} />,
              }}
            >
              {content}
            </ReactMarkdown>
          </div>
        </ScrollArea.Viewport>
        <ScrollArea.Scrollbar orientation="vertical" className={styles.scrollbar}>
          <ScrollArea.Thumb className={styles.thumb} />
        </ScrollArea.Scrollbar>
      </ScrollArea.Root>
    </div>
  );
}


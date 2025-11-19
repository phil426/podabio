import { useEffect, useState } from 'react';
import * as ScrollArea from '@radix-ui/react-scroll-area';
import styles from './documentation-toc.module.css';

interface Heading {
  id: string;
  text: string;
  level: number;
}

interface DocumentationTOCProps {
  headings: Heading[];
}

export function DocumentationTOC({ headings }: DocumentationTOCProps): JSX.Element {
  const [activeId, setActiveId] = useState<string>('');

  useEffect(() => {
    if (headings.length === 0) return;

    const observer = new IntersectionObserver(
      (entries) => {
        entries.forEach((entry) => {
          if (entry.isIntersecting) {
            setActiveId(entry.target.id);
          }
        });
      },
      {
        rootMargin: '-20% 0% -35% 0%',
        threshold: 0,
      }
    );

    headings.forEach((heading) => {
      const element = document.getElementById(heading.id);
      if (element) {
        observer.observe(element);
      }
    });

    return () => {
      headings.forEach((heading) => {
        const element = document.getElementById(heading.id);
        if (element) {
          observer.unobserve(element);
        }
      });
    };
  }, [headings]);

  const handleClick = (id: string) => {
    const element = document.getElementById(id);
    if (element) {
      element.scrollIntoView({ behavior: 'smooth', block: 'start' });
      setActiveId(id);
    }
  };

  if (headings.length === 0) {
    return (
      <div className={styles.toc}>
        <div className={styles.emptyState}>
          <p>No headings in this document</p>
        </div>
      </div>
    );
  }

  return (
    <div className={styles.toc}>
      <div className={styles.tocHeader}>
        <h3 className={styles.tocTitle}>On this page</h3>
      </div>
      <ScrollArea.Root className={styles.scrollArea}>
        <ScrollArea.Viewport className={styles.viewport}>
          <nav className={styles.tocNav}>
            <ul className={styles.tocList}>
              {headings.map((heading) => (
                <li
                  key={heading.id}
                  className={styles.tocItem}
                  data-level={heading.level}
                >
                  <a
                    href={`#${heading.id}`}
                    className={`${styles.tocLink} ${activeId === heading.id ? styles.tocLinkActive : ''}`}
                    onClick={(e) => {
                      e.preventDefault();
                      handleClick(heading.id);
                    }}
                  >
                    {heading.text}
                  </a>
                </li>
              ))}
            </ul>
          </nav>
        </ScrollArea.Viewport>
        <ScrollArea.Scrollbar orientation="vertical" className={styles.scrollbar}>
          <ScrollArea.Thumb className={styles.thumb} />
        </ScrollArea.Scrollbar>
      </ScrollArea.Root>
    </div>
  );
}


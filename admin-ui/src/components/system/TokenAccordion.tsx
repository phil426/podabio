import * as Accordion from '@radix-ui/react-accordion';
import { ReactNode } from 'react';
import { ChevronDownIcon } from './icons/ChevronDownIcon';

import styles from './token-accordion.module.css';

export interface TokenAccordionItem {
  id: string;
  trigger: ReactNode;
  description?: string;
  content: ReactNode;
}

interface TokenAccordionProps {
  items: TokenAccordionItem[];
  type?: 'single' | 'multiple';
  defaultValue?: string;
}

export function TokenAccordion({
  items,
  type = 'single',
  defaultValue
}: TokenAccordionProps): JSX.Element {
  const accordionProps =
    type === 'multiple'
      ? ({
          type: 'multiple' as const,
          defaultValue: defaultValue ? [defaultValue] : undefined
        } satisfies Accordion.AccordionMultipleProps)
      : ({
          type: 'single' as const,
          defaultValue
        } satisfies Accordion.AccordionSingleProps);

  return (
    <Accordion.Root {...accordionProps} className={styles.root}>
      {items.map((item) => (
        <Accordion.Item key={item.id} value={item.id} className={styles.item}>
          <Accordion.Header className={styles.header}>
            <Accordion.Trigger className={styles.trigger}>
              <div>
                <div className={styles.triggerLabel}>{item.trigger}</div>
                {item.description && <p className={styles.triggerDescription}>{item.description}</p>}
              </div>
              <ChevronDownIcon className={styles.chevron} />
            </Accordion.Trigger>
          </Accordion.Header>
          <Accordion.Content className={styles.content}>
            <div className={styles.contentInner}>{item.content}</div>
          </Accordion.Content>
        </Accordion.Item>
      ))}
    </Accordion.Root>
  );
}


import { ReactNode } from 'react';
import { CaretDown, CaretRight } from '@phosphor-icons/react';
import * as Tooltip from '@radix-ui/react-tooltip';
import styles from './section-header.module.css';

interface SectionHeaderProps {
  icon?: ReactNode;
  title: string;
  isExpanded: boolean;
  onToggle: () => void;
  count?: number;
}

export function SectionHeader({
  icon,
  title,
  isExpanded,
  onToggle,
  count
}: SectionHeaderProps): JSX.Element {
  return (
    <Tooltip.Root>
      <Tooltip.Trigger asChild>
    <button
      className={styles.header}
      onClick={onToggle}
      aria-expanded={isExpanded}
      aria-label={`${isExpanded ? 'Collapse' : 'Expand'} ${title} section`}
          title={isExpanded ? `Collapse ${title}` : `Expand ${title}`}
    >
      <div className={styles.left}>
        {icon && <span className={styles.icon}>{icon}</span>}
        <span className={styles.title}>{title}</span>
        {count !== undefined && count > 0 && (
          <span className={styles.count}>{count}</span>
        )}
      </div>
      <div className={styles.right}>
        {isExpanded ? (
          <CaretDown className={styles.chevron} weight="regular" />
        ) : (
          <CaretRight className={styles.chevron} weight="regular" />
        )}
      </div>
    </button>
      </Tooltip.Trigger>
      <Tooltip.Portal>
        <Tooltip.Content side="right" align="center" className={styles.tooltip}>
          {isExpanded ? `Hide ${title} token controls` : `Show ${title} token controls`}
          <Tooltip.Arrow className={styles.tooltipArrow} />
        </Tooltip.Content>
      </Tooltip.Portal>
    </Tooltip.Root>
  );
}


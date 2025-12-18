import { CaretRight, CaretLeft } from '@phosphor-icons/react';
import { useAppSidebarExpanded } from '../../state/appSidebarExpanded';
import styles from './app-sidebar-collapse-handle.module.css';

export function AppSidebarCollapseHandle(): JSX.Element {
    const { isExpanded, toggleExpanded } = useAppSidebarExpanded();

    return (
        <button
            type="button"
            className={styles.handle}
            onClick={toggleExpanded}
            aria-label={isExpanded ? 'Collapse sidebar' : 'Expand sidebar'}
            aria-expanded={isExpanded}
            data-expanded={isExpanded}
        >
            {isExpanded ? (
                <CaretLeft size={16} weight="bold" />
            ) : (
                <CaretRight size={16} weight="bold" />
            )}
        </button>
    );
}

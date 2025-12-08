import { X } from '@phosphor-icons/react';
import styles from '../podcast-theme-generator.module.css';

interface WizardHeaderProps {
    onClose: () => void;
}

export function WizardHeader({ onClose }: WizardHeaderProps) {
    return (
        <div className={styles.header}>
            <div className={styles.headerContent}>
                <h2>Theme Wizard</h2>
                <p>Extract colors from images to create custom themes</p>
            </div>
            <button
                onClick={onClose}
                className={styles.closeButton}
                aria-label="Close wizard"
            >
                <X size={20} weight="bold" />
            </button>
        </div>
    );
}

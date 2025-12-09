import { Star, CircleNotch, MagicWand, ArrowCounterClockwise } from '@phosphor-icons/react';
import styles from '../podcast-theme-generator.module.css';

interface PreviewActionsProps {
    isGenerating: boolean;
    colorsCount: number;
    onGenerate: () => void;
    onCancel: () => void;
    onApply: () => void;
    onRevert: () => void;
}

export function PreviewActions({
    isGenerating,
    colorsCount,
    onGenerate,
    onCancel,
    onApply,
    onRevert,
}: PreviewActionsProps) {
    return (
        <div className={styles.actions}>
            <div className={styles.leftActions}>
                <button
                    className={styles.cancelButton}
                    onClick={onCancel}
                    disabled={isGenerating}
                >
                    Cancel
                </button>
            </div>

            <div className={styles.rightActions}>
                <button
                    className={styles.iconActionButton}
                    onClick={onRevert}
                    disabled={isGenerating}
                    title="Revert to original look"
                    aria-label="Revert changes"
                >
                    <ArrowCounterClockwise size={20} />
                </button>
                <button
                    className={styles.iconActionButton}
                    onClick={onApply}
                    disabled={isGenerating || colorsCount < 2}
                    title="Apply changes to preview"
                    aria-label="Apply changes"
                >
                    <MagicWand size={20} weight="fill" />
                </button>
                <button
                    className={styles.primaryIconActionButton}
                    onClick={onGenerate}
                    disabled={isGenerating || colorsCount !== 5}
                    title="Save Theme"
                    aria-label="Save Theme"
                >
                    {isGenerating ? (
                        <CircleNotch size={20} className="fa-spin" />
                    ) : (
                        <Star size={20} weight="fill" />
                    )}
                </button>
            </div>
        </div>
    );
}

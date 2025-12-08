import { Star, CircleNotch } from '@phosphor-icons/react';
import styles from '../podcast-theme-generator.module.css';

interface PreviewActionsProps {
    isGenerating: boolean;
    colorsCount: number;
    onGenerate: () => void;
    onCancel: () => void;
}

export function PreviewActions({
    isGenerating,
    colorsCount,
    onGenerate,
    onCancel,
}: PreviewActionsProps) {
    return (
        <div className={styles.actions}>
            <button
                className={styles.cancelButton}
                onClick={onCancel}
                disabled={isGenerating}
            >
                Cancel
            </button>
            <button
                className={styles.generateButton}
                onClick={onGenerate}
                disabled={isGenerating || colorsCount !== 5}
            >
                {isGenerating ? (
                    <>
                        <CircleNotch size={20} className="fa-spin" />
                        <span>Creating Theme...</span>
                    </>
                ) : (
                    <>
                        <Star size={20} weight="fill" />
                        <span>Save Theme</span>
                    </>
                )}
            </button>
        </div>
    );
}

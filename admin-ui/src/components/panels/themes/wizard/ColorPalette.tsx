import { Shuffle, CircleNotch, Lock, LockOpen } from '@phosphor-icons/react';
import { HexColorPicker, HexColorInput } from 'react-colorful';
import * as Popover from '@radix-ui/react-popover';
import styles from '../podcast-theme-generator.module.css';

interface ColorPaletteProps {
    colors: string[];
    isExtracting: boolean;
    isShuffling: boolean;
    error: string | null;
    draggedIndex: number | null;
    dragOverIndex: number | null;
    onDragStart: (index: number) => void;
    onDragOver: (e: React.DragEvent, index: number) => void;
    onDragLeave: () => void;
    onDrop: (e: React.DragEvent, index: number) => void;
    onDragEnd: () => void;
    onShuffle: () => void;
    onColorsChange?: (colors: string[]) => void;
}

export function ColorPalette({
    colors,
    isExtracting,
    isShuffling,
    error,
    draggedIndex,
    dragOverIndex,
    onDragStart,
    onDragOver,
    onDragLeave,
    onDrop,
    onDragEnd,
    onShuffle,
    onColorsChange
}: ColorPaletteProps) {

    const getColorRole = (index: number): string => {
        const roles = [
            'Background Gradient Start',
            'Background Gradient End',
            'Page Title & Widget Background',
            'Body Text & Widget Text',
            'Accents & Borders'
        ];
        return roles[index] || `Color ${index + 1}`;
    };

    const handleColorUpdate = (index: number, newColor: string) => {
        if (!onColorsChange) return;
        const newColors = [...colors];
        newColors[index] = newColor;
        onColorsChange(newColors);
    };

    if (error) {
        return (
            <div className={styles.errorContainer}>
                <p className={styles.errorMessage}>{error}</p>
            </div>
        );
    }

    if (isExtracting) {
        return (
            <div className={styles.loadingContainer}>
                <CircleNotch size={32} className="fa-spin" />
                <p>Extracting colors...</p>
            </div>
        );
    }

    if (colors.length === 0) {
        return (
            <div className={styles.emptyState}>
                <p>Select a podcast or upload an image to extract colors</p>
            </div>
        );
    }

    return (
        <div className={styles.paletteSection}>
            <div className={styles.paletteHeader}>
                <h3>Extracted Palette</h3>
                <button
                    className={styles.shuffleButton}
                    onClick={onShuffle}
                    disabled={isShuffling}
                >
                    {isShuffling ? (
                        <CircleNotch size={16} className="fa-spin" />
                    ) : (
                        <Shuffle size={16} />
                    )}
                    <span>Shuffle Colors</span>
                </button>
            </div>

            <div className={styles.colorGrid}>
                {colors.map((color, index) => (
                    <div
                        key={`${index}`}
                        className={`${styles.colorItem} ${draggedIndex === index ? styles.dragging : ''} ${dragOverIndex === index ? styles.dragOver : ''}`}
                        onDragOver={(e) => onDragOver(e, index)}
                        onDragLeave={onDragLeave}
                        onDrop={(e) => onDrop(e, index)}
                        style={{ '--color-bg': color } as React.CSSProperties}
                        aria-label={`Color ${index + 1}: ${getColorRole(index)}.`}
                    >
                        <Popover.Root>
                            <Popover.Trigger asChild>
                                <button
                                    className={styles.colorSwatch}
                                    aria-label={`Edit color ${color}. Drag to reorder.`}
                                    draggable
                                    onDragStart={(e) => {
                                        // Create a custom ghost element
                                        const ghost = document.createElement('div');
                                        ghost.style.width = '40px';
                                        ghost.style.height = '40px';
                                        ghost.style.borderRadius = '6px';
                                        ghost.style.backgroundColor = color;
                                        ghost.style.position = 'absolute';
                                        ghost.style.top = '-1000px';
                                        ghost.style.left = '-1000px';
                                        ghost.style.zIndex = '10000';
                                        ghost.style.boxShadow = '0 2px 4px rgba(0,0,0,0.2)';
                                        document.body.appendChild(ghost);

                                        e.dataTransfer.setDragImage(ghost, 20, 20); // Center the ghost
                                        e.dataTransfer.effectAllowed = 'move';

                                        // Remove ghost element after browser has taken the snapshot
                                        setTimeout(() => {
                                            if (document.body.contains(ghost)) {
                                                document.body.removeChild(ghost);
                                            }
                                        }, 0);

                                        onDragStart(index);
                                    }}
                                    onDragEnd={onDragEnd}
                                />
                            </Popover.Trigger>
                            <Popover.Portal>
                                <Popover.Content className={styles.colorPickerPopover} sideOffset={5} align="start">
                                    <HexColorPicker color={color} onChange={(c) => handleColorUpdate(index, c)} className={styles.colorPicker} />
                                    <div className={styles.hexInputWrapper}>
                                        <span className={styles.hexPrefix}>#</span>
                                        <HexColorInput color={color} onChange={(c) => handleColorUpdate(index, c)} className={styles.hexInput} />
                                    </div>
                                </Popover.Content>
                            </Popover.Portal>
                        </Popover.Root>

                        <div className={styles.colorInfo}>
                            <span className={styles.colorRole}>{getColorRole(index)}</span>
                            <span className={styles.colorHex}>{color}</span>
                        </div>
                    </div>
                ))}
            </div>
            <p className={styles.dragHint}>Drag colors to reorder • Click swatch to edit</p>
        </div>
    );
}

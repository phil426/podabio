
import React, { useRef, useState, useEffect } from 'react';
import styles from './focal-point-picker.module.css';

interface FocalPointPickerProps {
    imageUrl: string;
    valueX: string; // e.g., "50%"
    valueY: string; // e.g., "50%"
    onChange: (x: string, y: string) => void;
}

export function FocalPointPicker({ imageUrl, valueX, valueY, onChange }: FocalPointPickerProps) {
    const containerRef = useRef<HTMLDivElement>(null);
    const [isDragging, setIsDragging] = useState(false);

    // Parse percentage values to numbers
    const xPercent = parseFloat(valueX) || 50;
    const yPercent = parseFloat(valueY) || 50;

    const handleInteraction = (clientX: number, clientY: number) => {
        if (!containerRef.current) return;

        const rect = containerRef.current.getBoundingClientRect();
        const x = Math.max(0, Math.min(100, ((clientX - rect.left) / rect.width) * 100));
        const y = Math.max(0, Math.min(100, ((clientY - rect.top) / rect.height) * 100));

        onChange(`${Math.round(x)}%`, `${Math.round(y)}%`);
    };

    const handleMouseDown = (e: React.MouseEvent) => {
        setIsDragging(true);
        handleInteraction(e.clientX, e.clientY);
    };

    const handleTouchStart = (e: React.TouchEvent) => {
        setIsDragging(true);
        handleInteraction(e.touches[0].clientX, e.touches[0].clientY);
    };

    useEffect(() => {
        const handleMove = (e: MouseEvent) => {
            if (isDragging) {
                handleInteraction(e.clientX, e.clientY);
            }
        };

        const handleUp = () => {
            setIsDragging(false);
        };

        const handleTouchMove = (e: TouchEvent) => {
            if (isDragging) {
                e.preventDefault(); // Prevent scrolling
                handleInteraction(e.touches[0].clientX, e.touches[0].clientY);
            }
        };

        if (isDragging) {
            window.addEventListener('mousemove', handleMove);
            window.addEventListener('mouseup', handleUp);
            window.addEventListener('touchmove', handleTouchMove, { passive: false });
            window.addEventListener('touchend', handleUp);
        }

        return () => {
            window.removeEventListener('mousemove', handleMove);
            window.removeEventListener('mouseup', handleUp);
            window.removeEventListener('touchmove', handleTouchMove);
            window.removeEventListener('touchend', handleUp);
        };
    }, [isDragging]);

    return (
        <div className={styles.container}>
            <div
                ref={containerRef}
                className={styles.pickerArea}
                style={{ backgroundImage: `url(${imageUrl})` }}
                onMouseDown={handleMouseDown}
                onTouchStart={handleTouchStart}
            >
                <div className={styles.gridOverlay} />

                {/* 9:20 Viewport Guide Overlay */}
                <div
                    className={styles.viewportOverlay}
                    style={{ left: `${xPercent}%`, top: `${yPercent}%` }}
                />

                <div
                    className={styles.handle}
                    style={{ left: `${xPercent}%`, top: `${yPercent}%` }}
                />
            </div>
            <div className={styles.labels}>
                <span>X: {Math.round(xPercent)}%</span>
                <span>Y: {Math.round(yPercent)}%</span>
            </div>
        </div>
    );
}

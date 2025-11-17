import { useState } from 'react';
import styles from './color-swatch.module.css';

interface ColorSwatchProps {
  value: string;
  onClick?: () => void;
  size?: number;
}

export function ColorSwatch({ value, onClick, size = 40 }: ColorSwatchProps): JSX.Element {
  const [isHovered, setIsHovered] = useState(false);
  
  // Determine if it's a gradient or image
  const isGradient = value.includes('gradient');
  const isImage = value.startsWith('http://') || value.startsWith('https://') || value.startsWith('/') || value.startsWith('data:');
  
  const swatchStyle: React.CSSProperties = {
    width: `${size}px`,
    height: `${size}px`,
    ...(isGradient || isImage
      ? { backgroundImage: value }
      : { backgroundColor: value })
  };

  return (
    <div
      className={styles.swatch}
      style={swatchStyle}
      onClick={onClick}
      onMouseEnter={() => setIsHovered(true)}
      onMouseLeave={() => setIsHovered(false)}
      role={onClick ? 'button' : undefined}
      tabIndex={onClick ? 0 : undefined}
      aria-label={`Color: ${value}`}
    >
      {isHovered && !isGradient && !isImage && (
        <span className={styles.hexValue}>{value.toUpperCase()}</span>
      )}
      {(isGradient || isImage) && (
        <div className={styles.patternOverlay} />
      )}
    </div>
  );
}


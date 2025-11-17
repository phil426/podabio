import styles from '../theme-editor-panel.module.css';

interface SpacingSectionProps {
  density: 'compact' | 'cozy' | 'comfortable';
  onDensityChange: (density: 'compact' | 'cozy' | 'comfortable') => void;
}

export function SpacingSection({ density, onDensityChange }: SpacingSectionProps): JSX.Element {
  return (
    <div className={styles.spacingSection}>
      <div className={styles.densityGroup} role="radiogroup" aria-label="Spacing density">
        {(['compact', 'cozy', 'comfortable'] as const).map((d) => (
          <button
            key={d}
            type="button"
            role="radio"
            aria-checked={density === d}
            className={`${styles.densityChip} ${density === d ? styles.densityChipActive : ''}`}
            onClick={() => onDensityChange(d)}
          >
            <div>
              <span>{d.charAt(0).toUpperCase() + d.slice(1)}</span>
              <p>
                {d === 'compact' && 'High information density'}
                {d === 'cozy' && 'Balanced spacing'}
                {d === 'comfortable' && 'Generous spacing'}
              </p>
            </div>
          </button>
        ))}
      </div>
    </div>
  );
}


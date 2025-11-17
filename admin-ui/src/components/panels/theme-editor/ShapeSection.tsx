import styles from '../theme-editor-panel.module.css';

interface ShapeSectionProps {
  buttonRadius: 'square' | 'rounded' | 'pill';
  borderEffect: 'shadow' | 'glow';
  shadowIntensity: 'none' | 'subtle' | 'pronounced';
  glowIntensity: 'subtle' | 'pronounced';
  glowColor: string;
  onButtonRadiusChange: (radius: 'square' | 'rounded' | 'pill') => void;
  onBorderEffectChange: (effect: 'shadow' | 'glow') => void;
  onShadowIntensityChange: (intensity: 'none' | 'subtle' | 'pronounced') => void;
  onGlowIntensityChange: (intensity: 'subtle' | 'pronounced') => void;
  onGlowColorChange: (color: string) => void;
}

export function ShapeSection({
  buttonRadius,
  borderEffect,
  shadowIntensity,
  glowIntensity,
  glowColor,
  onButtonRadiusChange,
  onBorderEffectChange,
  onShadowIntensityChange,
  onGlowIntensityChange,
  onGlowColorChange
}: ShapeSectionProps): JSX.Element {
  return (
    <div className={styles.shapeSection}>
      <div className={styles.controlGroup}>
        <span className={styles.controlLabel}>Button Radius</span>
        <div className={styles.optionButtons}>
          {(['square', 'rounded', 'pill'] as const).map((radius) => (
            <button
              key={radius}
              type="button"
              className={`${styles.optionButton} ${buttonRadius === radius ? styles.optionButtonActive : ''}`}
              onClick={() => onButtonRadiusChange(radius)}
            >
              {radius.charAt(0).toUpperCase() + radius.slice(1)}
            </button>
          ))}
        </div>
      </div>
      <div className={styles.controlGroup}>
        <span className={styles.controlLabel}>Border Effect</span>
        <div className={styles.optionButtons}>
          {(['shadow', 'glow'] as const).map((effect) => (
            <button
              key={effect}
              type="button"
              className={`${styles.optionButton} ${borderEffect === effect ? styles.optionButtonActive : ''}`}
              onClick={() => onBorderEffectChange(effect)}
            >
              {effect.charAt(0).toUpperCase() + effect.slice(1)}
            </button>
          ))}
        </div>
      </div>
      {borderEffect === 'shadow' && (
        <div className={styles.controlGroup}>
          <span className={styles.controlLabel}>Shadow Intensity</span>
          <div className={styles.optionButtons}>
            {(['none', 'subtle', 'pronounced'] as const).map((intensity) => (
              <button
                key={intensity}
                type="button"
                className={`${styles.optionButton} ${shadowIntensity === intensity ? styles.optionButtonActive : ''}`}
                onClick={() => onShadowIntensityChange(intensity)}
              >
                {intensity.charAt(0).toUpperCase() + intensity.slice(1)}
              </button>
            ))}
          </div>
        </div>
      )}
      {borderEffect === 'glow' && (
        <>
          <div className={styles.controlGroup}>
            <span className={styles.controlLabel}>Glow Intensity</span>
            <div className={styles.optionButtons}>
              {(['subtle', 'pronounced'] as const).map((intensity) => (
                <button
                  key={intensity}
                  type="button"
                  className={`${styles.optionButton} ${glowIntensity === intensity ? styles.optionButtonActive : ''}`}
                  onClick={() => onGlowIntensityChange(intensity)}
                >
                  {intensity.charAt(0).toUpperCase() + intensity.slice(1)}
                </button>
              ))}
            </div>
          </div>
          <div className={styles.controlGroup}>
            <span className={styles.controlLabel}>Glow Color</span>
            <div style={{ display: 'flex', gap: '0.5rem', alignItems: 'center' }}>
              <input
                type="color"
                value={glowColor}
                onChange={(e) => onGlowColorChange(e.target.value)}
                style={{
                  width: '48px',
                  height: '48px',
                  border: '2px solid var(--pod-core-border-default, #e2e8f0)',
                  borderRadius: 'var(--pod-core-shape-radius-md, 12px)',
                  cursor: 'pointer'
                }}
              />
              <input
                type="text"
                value={glowColor}
                onChange={(e) => onGlowColorChange(e.target.value)}
                style={{
                  flex: 1,
                  padding: '0.75rem',
                  border: '2px solid var(--pod-core-border-default, #e2e8f0)',
                  borderRadius: 'var(--pod-core-shape-radius-md, 12px)',
                  fontSize: '0.875rem'
                }}
              />
            </div>
          </div>
        </>
      )}
    </div>
  );
}


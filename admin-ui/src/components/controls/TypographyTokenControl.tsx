import { useState } from 'react';

import styles from './typography-token-control.module.css';

interface TypographyPreset {
  name: string;
  heading: string;
  body: string;
}

const presets: TypographyPreset[] = [
  { name: 'Inter', heading: 'Inter', body: 'Inter' },
  { name: 'Work Sans', heading: '"Work Sans"', body: '"Work Sans"' },
  { name: 'Poppins', heading: '"Poppins"', body: '"Poppins"' },
  { name: 'Literata', heading: '"Literata"', body: '"Inter"' }
];

interface TypographyTokenControlProps {
  headingToken: string;
  bodyToken: string;
  onChange?: (preset: TypographyPreset) => void;
}

export function TypographyTokenControl({
  headingToken,
  bodyToken,
  onChange
}: TypographyTokenControlProps): JSX.Element {
  const [active, setActive] = useState<TypographyPreset>(presets[0]);

  const handleSelect = (preset: TypographyPreset) => {
    setActive(preset);
    onChange?.(preset);
  };

  return (
    <div className={styles.wrapper}>
      <header className={styles.header}>
        <div>
          <p className={styles.title}>Typography tokens</p>
          <p className={styles.subtitle}>
            Map tokens <code>{headingToken}</code> & <code>{bodyToken}</code> to curated font stacks.
          </p>
        </div>
        <div className={styles.preview}>
          <p style={{ fontFamily: active.heading }}>Heading Preview</p>
          <p style={{ fontFamily: active.body }}>Body copy preview for PodaBio</p>
        </div>
      </header>

      <div className={styles.grid} role="list">
        {presets.map((preset) => (
          <button
            key={preset.name}
            type="button"
            role="listitem"
            className={styles.card}
            aria-pressed={preset.name === active.name}
            onClick={() => handleSelect(preset)}
          >
            <div className={styles.cardHeader}>
              <p className={styles.cardTitle}>{preset.name}</p>
              <span className={styles.badge}>WCAG AA</span>
            </div>
            <p className={styles.sampleHeading} style={{ fontFamily: preset.heading }}>
              Heading Token
            </p>
            <p className={styles.sampleBody} style={{ fontFamily: preset.body }}>
              Body token preview for descriptive text and metadata.
            </p>
          </button>
        ))}
      </div>
    </div>
  );
}


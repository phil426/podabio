import { useState } from 'react';
import { ArrowCounterClockwise } from '@phosphor-icons/react';
import styles from './token-control.module.css';

interface TokenControlProps {
  label: string;
  tokenPath: string;
  value: unknown;
  defaultValue?: unknown;
  onReset?: () => void;
  onValueChange?: (value: unknown) => void;
  children: React.ReactNode;
  error?: string;
  loading?: boolean;
  hideTokenPath?: boolean;
}

export function TokenControl({
  label,
  tokenPath,
  value,
  defaultValue,
  onReset,
  onValueChange,
  children,
  error,
  loading,
  hideTokenPath = false
}: TokenControlProps): JSX.Element {
  const [isHovered, setIsHovered] = useState(false);
  const isModified = defaultValue !== undefined && value !== defaultValue;
  const showReset = isModified && onReset && isHovered;

  return (
    <div
      className={styles.control}
      onMouseEnter={() => setIsHovered(true)}
      onMouseLeave={() => setIsHovered(false)}
    >
      <div className={styles.header}>
        <div className={styles.labelRow}>
          <label className={styles.label}>{label}</label>
          {isModified && <span className={styles.modifiedDot} aria-label="Modified" />}
        </div>
        {showReset && (
          <button
            className={styles.resetButton}
            onClick={onReset}
            aria-label={`Reset ${label} to default`}
            title="Reset to default"
          >
            <ArrowCounterClockwise weight="regular" />
          </button>
        )}
      </div>
      {!hideTokenPath && <div className={styles.tokenPath}>{tokenPath}</div>}
      {loading ? (
        <div className={styles.loading}>Loading...</div>
      ) : error ? (
        <div className={styles.error}>{error}</div>
      ) : (
        <div className={styles.controlArea}>{children}</div>
      )}
    </div>
  );
}


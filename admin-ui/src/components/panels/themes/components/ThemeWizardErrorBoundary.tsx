/**
 * Error Boundary for Theme Wizard
 * Catches React errors and displays a graceful fallback UI
 */

import { Component, type ReactNode } from 'react';
import { X, ArrowCounterClockwise } from '@phosphor-icons/react';
import styles from '../podcast-theme-generator.module.css';

interface ThemeWizardErrorBoundaryProps {
  children: ReactNode;
  onReset?: () => void;
}

interface ThemeWizardErrorBoundaryState {
  hasError: boolean;
  error: Error | null;
  errorInfo: { componentStack: string } | null;
}

export class ThemeWizardErrorBoundary extends Component<
  ThemeWizardErrorBoundaryProps,
  ThemeWizardErrorBoundaryState
> {
  constructor(props: ThemeWizardErrorBoundaryProps) {
    super(props);
    this.state = {
      hasError: false,
      error: null,
      errorInfo: null,
    };
  }

  static getDerivedStateFromError(error: Error): Partial<ThemeWizardErrorBoundaryState> {
    return {
      hasError: true,
      error,
    };
  }

  componentDidCatch(error: Error, errorInfo: { componentStack: string }): void {
    // Log error to console in development
    if (process.env.NODE_ENV === 'development') {
      console.error('Theme Wizard Error:', error, errorInfo);
    }

    // Log to telemetry/error reporting service in production
    // trackTelemetry({ event: 'theme_wizard.error', metadata: { error: error.message, stack: error.stack } });

    this.setState({
      error,
      errorInfo,
    });
  }

  handleReset = (): void => {
    this.setState({
      hasError: false,
      error: null,
      errorInfo: null,
    });

    if (this.props.onReset) {
      this.props.onReset();
    }
  };

  render(): ReactNode {
    if (this.state.hasError) {
      return (
        <div className={styles.errorBoundary}>
          <div className={styles.errorBoundaryContent}>
            <div className={styles.errorBoundaryIcon}>
              <X size={48} weight="regular" />
            </div>
            <h3 className={styles.errorBoundaryTitle}>Something went wrong</h3>
            <p className={styles.errorBoundaryMessage}>
              The Theme Wizard encountered an error. You can try resetting or closing the wizard.
            </p>
            {process.env.NODE_ENV === 'development' && this.state.error && (
              <details className={styles.errorBoundaryDetails}>
                <summary>Error Details (Development Only)</summary>
                <pre className={styles.errorBoundaryStack}>
                  {this.state.error.toString()}
                  {this.state.errorInfo?.componentStack}
                </pre>
              </details>
            )}
            <div className={styles.errorBoundaryActions}>
              <button
                type="button"
                className={styles.errorBoundaryButton}
                onClick={this.handleReset}
              >
                <ArrowCounterClockwise aria-hidden="true" size={16} weight="regular" />
                Reset Wizard
              </button>
            </div>
          </div>
        </div>
      );
    }

    return this.props.children;
  }
}



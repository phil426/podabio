import { useState, useEffect, useCallback } from 'react';
import * as Dialog from '@radix-ui/react-dialog';
import * as VisuallyHidden from '@radix-ui/react-visually-hidden';
import { AnimatePresence, motion } from 'framer-motion';
import { useAccountProfile } from '../../api/account';
import { trackTelemetry } from '../../services/telemetry';
import { ONBOARDING_STORAGE_KEY, ONBOARDING_VERSION } from '../../hooks/useOnboardingStatus';

import styles from './welcome-onboarding-modal.module.css';

// Asset Imports
const ASSET_PATH = '/admin-ui/public/assets/onboarding';

export interface WelcomeOnboardingModalProps {
  /** Force show the modal (for testing/demo) */
  forceOpen?: boolean;
  /** Callback when onboarding is completed or dismissed */
  onComplete?: () => void;
}

export function WelcomeOnboardingModal({
  forceOpen = false,
  onComplete
}: WelcomeOnboardingModalProps): JSX.Element {
  const [isOpen, setIsOpen] = useState(false);
  const [currentSlide, setCurrentSlide] = useState(0);
  const [dontShowAgain, setDontShowAgain] = useState(false);
  const [hasInteracted, setHasInteracted] = useState(false);

  const { data: profile, isLoading: profileLoading } = useAccountProfile();

  const userName = profile?.name?.split(' ')[0] || 'there';

  // Slides Configuration
  const slides = [
    {
      id: 'welcome',
      title: `Welcome to PodaBio, ${userName}!`,
      description: "You're about to create the ultimate landing page for your podcast. Let's get you set up in just a few minutes.",
      image: '/assets/onboarding/welcome.png' // Use absolute path from public root
    },
    {
      id: 'features',
      title: "All Your Links in One Place",
      description: "Display your latest episodes, social profiles, and important links with a beautiful, unified page.",
      image: '/assets/onboarding/features.png'
    },
    {
      id: 'launch',
      title: "Ready for Liftoff",
      description: "Customize your theme, claim your username, and share your PodaBio link with your listeners.",
      image: '/assets/onboarding/launch.png',
      buttonText: "Let's Go!"
    }
  ];

  // Check visibility logic
  useEffect(() => {
    if (forceOpen) {
      setIsOpen(true);
      return;
    }

    if (profileLoading) return;

    const completedVersion = localStorage.getItem(ONBOARDING_STORAGE_KEY);
    if (completedVersion === ONBOARDING_VERSION) {
      return;
    }

    setIsOpen(true);
    trackTelemetry({ event: 'onboarding.modal_shown', metadata: { version: ONBOARDING_VERSION } });
  }, [forceOpen, profileLoading]);

  const handleComplete = useCallback(() => {
    if (dontShowAgain) {
      localStorage.setItem(ONBOARDING_STORAGE_KEY, ONBOARDING_VERSION);
    }
    setIsOpen(false);
    trackTelemetry({ event: 'onboarding.completed', metadata: { dismissed: !hasInteracted, dontShowAgain } });
    onComplete?.();
  }, [hasInteracted, dontShowAgain, onComplete]);

  const handleNext = () => {
    setHasInteracted(true);
    if (currentSlide < slides.length - 1) {
      setCurrentSlide(prev => prev + 1);
      trackTelemetry({ event: 'onboarding.slide_changed', metadata: { slideIndex: currentSlide + 1 } });
    } else {
      handleComplete();
    }
  };

  const goToSlide = (index: number) => {
    setHasInteracted(true);
    setCurrentSlide(index);
  };

  return (
    <Dialog.Root open={isOpen} onOpenChange={(open) => {
      // Prevent closing by clicking outside, compel user to click Skip or Next
      // But if they press ESC, we allow it.
      if (!open) handleComplete();
    }}>
      <Dialog.Portal>
        <Dialog.Overlay className={styles.overlay} />
        <Dialog.Content className={styles.modal} onInteractOutside={(e) => e.preventDefault()}>
          <VisuallyHidden.Root asChild>
            <Dialog.Title>Welcome to PodaBio Onboarding</Dialog.Title>
          </VisuallyHidden.Root>
          <VisuallyHidden.Root asChild>
            <Dialog.Description>A quick tour of PodaBio features.</Dialog.Description>
          </VisuallyHidden.Root>

          {/* Top Actions */}
          <div className={styles.topActions}>
            <button
              className={styles.skipButton}
              onClick={handleComplete}
              aria-label="Skip onboarding"
            >
              Skip
            </button>
          </div>

          {/* Slide Content */}
          <div className={styles.content}>
            <AnimatePresence mode='wait'>
              <motion.div
                key={currentSlide}
                initial={{ opacity: 0, x: 20 }}
                animate={{ opacity: 1, x: 0 }}
                exit={{ opacity: 0, x: -20 }}
                transition={{ duration: 0.2 }}
                className={styles.slideContainer} // Wrapper for slide content
              >
                <div className={styles.illustrationContainer}>
                  <img
                    src={slides[currentSlide].image}
                    alt=""
                    className={styles.illustration}
                  />
                </div>

                <div className={styles.textContainer}>
                  <h2 className={styles.title}>{slides[currentSlide].title}</h2>
                  <p className={styles.description}>{slides[currentSlide].description}</p>
                </div>
              </motion.div>
            </AnimatePresence>

            {/* Pagination Dots */}
            <div className={styles.dotsContainer}>
              {slides.map((_, index) => (
                <button
                  key={index}
                  className={`${styles.dot} ${index === currentSlide ? styles.active : ''}`}
                  onClick={() => goToSlide(index)}
                  aria-label={`Go to slide ${index + 1}`}
                />
              ))}
            </div>

            {/* Main Action Button */}
            <div className={styles.footer}>
              <button className={styles.primaryButton} onClick={handleNext}>
                {slides[currentSlide].buttonText || "Next"}
              </button>

              <div className={styles.footerOptions}>
                <label className={styles.checkboxLabel}>
                  <input
                    type="checkbox"
                    className={styles.checkbox}
                    checked={dontShowAgain}
                    onChange={(e) => setDontShowAgain(e.target.checked)}
                  />
                  Don't show this again
                </label>
              </div>
            </div>
          </div>

        </Dialog.Content>
      </Dialog.Portal>
    </Dialog.Root>
  );
}

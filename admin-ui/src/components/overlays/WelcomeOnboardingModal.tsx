import { useState, useEffect, useCallback } from 'react';
import * as Dialog from '@radix-ui/react-dialog';
import * as ScrollArea from '@radix-ui/react-scroll-area';
import { 
  X, 
  Check, 
  Sparkle, 
  PaintBrush, 
  Rss, 
  Link as LinkIcon, 
  ShareNetwork, 
  Rocket,
  ArrowRight,
  Lightning,
  Users,
  Palette
} from '@phosphor-icons/react';

import { usePageSnapshot } from '../../api/page';
import { useAccountProfile } from '../../api/account';
import { useWidgetsQuery } from '../../api/widgets';
import { trackTelemetry } from '../../services/telemetry';

import styles from './welcome-onboarding-modal.module.css';

const ONBOARDING_STORAGE_KEY = 'podabio_onboarding_completed';
const ONBOARDING_VERSION = '1'; // Increment to show again after major updates

export interface WelcomeOnboardingModalProps {
  /** Force show the modal (for testing/demo) */
  forceOpen?: boolean;
  /** Callback when onboarding is completed or dismissed */
  onComplete?: () => void;
}

type OnboardingStep = 'welcome' | 'features' | 'checklist';

interface ChecklistItem {
  id: string;
  label: string;
  description: string;
  completed: boolean;
}

export function WelcomeOnboardingModal({ 
  forceOpen = false,
  onComplete 
}: WelcomeOnboardingModalProps): JSX.Element {
  const [isOpen, setIsOpen] = useState(false);
  const [step, setStep] = useState<OnboardingStep>('welcome');
  const [hasInteracted, setHasInteracted] = useState(false);
  
  const { data: pageResponse, isLoading: pageLoading } = usePageSnapshot();
  const { data: profile, isLoading: profileLoading } = useAccountProfile();
  const { data: widgets } = useWidgetsQuery();
  
  // Extract page data from response
  const pageData = pageResponse?.page;
  const socialIcons = pageResponse?.social_icons;
  
  // Check if onboarding should be shown
  useEffect(() => {
    if (forceOpen) {
      setIsOpen(true);
      return;
    }
    
    // Don't show while loading
    if (pageLoading || profileLoading) return;
    
    // Check if already completed this version
    const completedVersion = localStorage.getItem(ONBOARDING_STORAGE_KEY);
    if (completedVersion === ONBOARDING_VERSION) {
      return;
    }
    
    // Show onboarding for new users
    setIsOpen(true);
    trackTelemetry({ event: 'onboarding.modal_shown', metadata: { step: 'welcome' } });
  }, [forceOpen, pageLoading, profileLoading]);
  
  const handleComplete = useCallback(() => {
    localStorage.setItem(ONBOARDING_STORAGE_KEY, ONBOARDING_VERSION);
    setIsOpen(false);
    trackTelemetry({ event: 'onboarding.completed', metadata: { dismissed: !hasInteracted } });
    onComplete?.();
  }, [hasInteracted, onComplete]);
  
  const handleNext = () => {
    setHasInteracted(true);
    if (step === 'welcome') {
      setStep('features');
      trackTelemetry({ event: 'onboarding.step_changed', metadata: { step: 'features' } });
    } else if (step === 'features') {
      setStep('checklist');
      trackTelemetry({ event: 'onboarding.step_changed', metadata: { step: 'checklist' } });
    } else {
      handleComplete();
    }
  };
  
  const handleSkip = () => {
    handleComplete();
  };
  
  // Generate checklist items based on user's current state
  const hasLinks = Boolean((widgets && widgets.length > 0) || (socialIcons && socialIcons.length > 0));
  
  const checklistItems: ChecklistItem[] = [
    {
      id: 'page',
      label: 'Create your page',
      description: 'Claim your unique poda.bio/username URL',
      completed: !!pageData?.username
    },
    {
      id: 'profile',
      label: 'Add profile image',
      description: 'Upload a photo or avatar to personalize your page',
      completed: !!pageData?.profile_image
    },
    {
      id: 'podcast',
      label: 'Connect your podcast',
      description: 'Add your RSS feed to display episodes',
      completed: !!pageData?.rss_feed_url
    },
    {
      id: 'links',
      label: 'Add your links',
      description: 'Share your social profiles and important links',
      completed: hasLinks
    },
    {
      id: 'theme',
      label: 'Customize your theme',
      description: 'Make your page uniquely yours with colors and fonts',
      completed: !!pageData?.theme_id
    }
  ];
  
  const completedCount = checklistItems.filter(item => item.completed).length;
  const totalCount = checklistItems.length;
  const progressPercent = (completedCount / totalCount) * 100;
  
  const userName = profile?.name?.split(' ')[0] || 'there';
  
  return (
    <Dialog.Root open={isOpen} onOpenChange={(open) => {
      if (!open) handleComplete();
    }}>
      <Dialog.Portal>
        <Dialog.Overlay className={styles.overlay} />
        <Dialog.Content className={styles.modal} aria-label="Welcome to PodaBio">
          {/* Progress indicator */}
          <div className={styles.progressBar}>
            <div 
              className={styles.progressFill} 
              style={{ width: step === 'welcome' ? '33%' : step === 'features' ? '66%' : '100%' }}
            />
          </div>
          
          <header className={styles.header}>
            <div className={styles.stepIndicator}>
              <span className={step === 'welcome' ? styles.activeStep : styles.step}>1</span>
              <span className={styles.stepDivider} />
              <span className={step === 'features' ? styles.activeStep : styles.step}>2</span>
              <span className={styles.stepDivider} />
              <span className={step === 'checklist' ? styles.activeStep : styles.step}>3</span>
            </div>
            <Dialog.Close asChild>
              <button
                type="button"
                className={styles.closeButton}
                aria-label="Close"
                onClick={handleSkip}
              >
                <X size={20} weight="regular" aria-hidden="true" />
              </button>
            </Dialog.Close>
          </header>
          
          <ScrollArea.Root className={styles.scrollArea}>
            <ScrollArea.Viewport className={styles.viewport}>
              <div className={styles.content}>
                {/* Step 1: Welcome */}
                {step === 'welcome' && (
                  <div className={styles.step}>
                    <div className={styles.welcomeIcon}>
                      <Sparkle size={48} weight="duotone" aria-hidden="true" />
                    </div>
                    <Dialog.Title className={styles.title}>
                      Welcome to PodaBio, {userName}!
                    </Dialog.Title>
                    <Dialog.Description className={styles.description}>
                      You're about to create the ultimate landing page for your podcast. 
                      Let's get you set up in just a few minutes.
                    </Dialog.Description>
                    
                    <div className={styles.welcomeStats}>
                      <div className={styles.stat}>
                        <Lightning size={24} weight="duotone" aria-hidden="true" />
                        <span>Set up in minutes</span>
                      </div>
                      <div className={styles.stat}>
                        <Users size={24} weight="duotone" aria-hidden="true" />
                        <span>Join 1000+ podcasters</span>
                      </div>
                      <div className={styles.stat}>
                        <Palette size={24} weight="duotone" aria-hidden="true" />
                        <span>Fully customizable</span>
                      </div>
                    </div>
                  </div>
                )}
                
                {/* Step 2: Features */}
                {step === 'features' && (
                  <div className={styles.step}>
                    <Dialog.Title className={styles.title}>
                      What you can do with PodaBio
                    </Dialog.Title>
                    <Dialog.Description className={styles.description}>
                      Everything you need to grow your podcast audience in one place.
                    </Dialog.Description>
                    
                    <div className={styles.featureGrid}>
                      <div className={styles.featureCard}>
                        <div className={styles.featureIcon}>
                          <Rss size={28} weight="duotone" aria-hidden="true" />
                        </div>
                        <h3>Podcast Player</h3>
                        <p>Display your latest episodes with a beautiful, embeddable player</p>
                      </div>
                      
                      <div className={styles.featureCard}>
                        <div className={styles.featureIcon}>
                          <LinkIcon size={28} weight="duotone" aria-hidden="true" />
                        </div>
                        <h3>Smart Links</h3>
                        <p>Add links to all your platforms and let listeners choose</p>
                      </div>
                      
                      <div className={styles.featureCard}>
                        <div className={styles.featureIcon}>
                          <ShareNetwork size={28} weight="duotone" aria-hidden="true" />
                        </div>
                        <h3>Social Integration</h3>
                        <p>Connect your social profiles and grow your community</p>
                      </div>
                      
                      <div className={styles.featureCard}>
                        <div className={styles.featureIcon}>
                          <PaintBrush size={28} weight="duotone" aria-hidden="true" />
                        </div>
                        <h3>Custom Themes</h3>
                        <p>Match your page to your brand with unlimited customization</p>
                      </div>
                    </div>
                  </div>
                )}
                
                {/* Step 3: Checklist */}
                {step === 'checklist' && (
                  <div className={styles.step}>
                    <Dialog.Title className={styles.title}>
                      <Rocket size={28} weight="duotone" aria-hidden="true" style={{ marginRight: '0.5rem' }} />
                      Let's get you launched
                    </Dialog.Title>
                    <Dialog.Description className={styles.description}>
                      Complete these steps to make the most of your PodaBio page.
                    </Dialog.Description>
                    
                    <div className={styles.progressSummary}>
                      <div className={styles.progressCircle}>
                        <svg viewBox="0 0 36 36" className={styles.progressRing}>
                          <path
                            className={styles.progressRingBg}
                            d="M18 2.0845
                              a 15.9155 15.9155 0 0 1 0 31.831
                              a 15.9155 15.9155 0 0 1 0 -31.831"
                          />
                          <path
                            className={styles.progressRingFill}
                            strokeDasharray={`${progressPercent}, 100`}
                            d="M18 2.0845
                              a 15.9155 15.9155 0 0 1 0 31.831
                              a 15.9155 15.9155 0 0 1 0 -31.831"
                          />
                        </svg>
                        <span className={styles.progressText}>{completedCount}/{totalCount}</span>
                      </div>
                      <p>{completedCount === totalCount ? 'All done! 🎉' : `${totalCount - completedCount} steps remaining`}</p>
                    </div>
                    
                    <div className={styles.checklist}>
                      {checklistItems.map((item) => (
                        <div 
                          key={item.id} 
                          className={`${styles.checklistItem} ${item.completed ? styles.completed : ''}`}
                        >
                          <div className={styles.checkIcon}>
                            {item.completed ? (
                              <Check size={18} weight="bold" aria-hidden="true" />
                            ) : (
                              <div className={styles.emptyCheck} />
                            )}
                          </div>
                          <div className={styles.checkContent}>
                            <h4>{item.label}</h4>
                            <p>{item.description}</p>
                          </div>
                        </div>
                      ))}
                    </div>
                    
                    <p className={styles.helpNote}>
                      You can always access these settings from the Studio sidebar.
                    </p>
                  </div>
                )}
              </div>
            </ScrollArea.Viewport>
            <ScrollArea.Scrollbar orientation="vertical" className={styles.scrollbar}>
              <ScrollArea.Thumb className={styles.thumb} />
            </ScrollArea.Scrollbar>
          </ScrollArea.Root>
          
          <footer className={styles.footer}>
            <button
              type="button"
              className={styles.skipButton}
              onClick={handleSkip}
            >
              {step === 'checklist' ? 'Close' : 'Skip for now'}
            </button>
            <button
              type="button"
              className={styles.nextButton}
              onClick={handleNext}
            >
              {step === 'checklist' ? (
                <>
                  Start Creating
                  <Rocket size={18} weight="bold" aria-hidden="true" />
                </>
              ) : (
                <>
                  Continue
                  <ArrowRight size={18} weight="bold" aria-hidden="true" />
                </>
              )}
            </button>
          </footer>
        </Dialog.Content>
      </Dialog.Portal>
    </Dialog.Root>
  );
}

/**
 * Hook to check if onboarding has been completed
 */
export function useOnboardingStatus(): { completed: boolean; reset: () => void } {
  const [completed, setCompleted] = useState(() => {
    return localStorage.getItem(ONBOARDING_STORAGE_KEY) === ONBOARDING_VERSION;
  });
  
  const reset = useCallback(() => {
    localStorage.removeItem(ONBOARDING_STORAGE_KEY);
    setCompleted(false);
  }, []);
  
  return { completed, reset };
}


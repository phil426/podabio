import { useState, useEffect, useRef } from 'react';
import { motion, AnimatePresence, useScroll, useMotionValueEvent } from 'framer-motion';
import * as NavigationMenu from '@radix-ui/react-navigation-menu';
import { List, X } from '@phosphor-icons/react';
import styles from './marketing-nav.module.css';

interface NavLink {
  href: string;
  label: string;
  external?: boolean;
}

const navLinks: NavLink[] = [
  { href: '#features', label: 'Features' },
  { href: '#pricing', label: 'Pricing' },
  { href: '#examples', label: 'Examples' },
  { href: '#about', label: 'About' },
  { href: '/support/', label: 'Support', external: true },
];

export function MarketingNav(): JSX.Element {
  const [isScrolled, setIsScrolled] = useState(false);
  const [mobileMenuOpen, setMobileMenuOpen] = useState(false);
  const [activeLink, setActiveLink] = useState<string>('');
  const navRef = useRef<HTMLElement>(null);
  const { scrollY } = useScroll();

  // Track scroll position for header styling
  useMotionValueEvent(scrollY, 'change', (latest) => {
    setIsScrolled(latest > 50);
  });

  // Track active section based on scroll position
  useEffect(() => {
    const handleScroll = () => {
      const sections = navLinks
        .filter(link => !link.external)
        .map(link => {
          const id = link.href.replace('#', '');
          const element = document.getElementById(id);
          return { id, element, link };
        });

      const scrollPosition = window.scrollY + 150;

      for (let i = sections.length - 1; i >= 0; i--) {
        const { element, id } = sections[i];
        if (element && element.offsetTop <= scrollPosition) {
          setActiveLink(`#${id}`);
          break;
        }
      }
    };

    window.addEventListener('scroll', handleScroll, { passive: true });
    handleScroll(); // Initial check

    return () => window.removeEventListener('scroll', handleScroll);
  }, []);

  // Close mobile menu on route change
  useEffect(() => {
    const handleHashChange = () => {
      setMobileMenuOpen(false);
    };
    window.addEventListener('hashchange', handleHashChange);
    return () => window.removeEventListener('hashchange', handleHashChange);
  }, []);

  // Prevent body scroll when mobile menu is open
  useEffect(() => {
    if (mobileMenuOpen) {
      document.body.style.overflow = 'hidden';
    } else {
      document.body.style.overflow = '';
    }
    return () => {
      document.body.style.overflow = '';
    };
  }, [mobileMenuOpen]);

  const handleLinkClick = (href: string, e: React.MouseEvent<HTMLAnchorElement>) => {
    if (!href.startsWith('#')) return; // Let external links work normally
    
    e.preventDefault();
    const targetId = href.replace('#', '');
    
    // Check if this is a tab navigation link (features, pricing, examples, about)
    const tabNames = ['features', 'pricing', 'examples', 'about'];
    if (tabNames.includes(targetId)) {
      // Use the global tab switching function if available
      if (typeof (window as any).switchToTab === 'function') {
        (window as any).switchToTab(targetId, true);
      } else {
        // Fallback: scroll to main-content and let hash change handler deal with it
        const mainContent = document.getElementById('main-content');
        if (mainContent) {
          const headerOffset = 100;
          const elementPosition = mainContent.getBoundingClientRect().top;
          const offsetPosition = elementPosition + window.pageYOffset - headerOffset;

          window.scrollTo({
            top: offsetPosition,
            behavior: 'smooth',
          });
          
          // Update URL hash - the hash change handler will switch the tab
          window.history.pushState(null, '', href);
          window.dispatchEvent(new HashChangeEvent('hashchange'));
        }
      }
      
      setActiveLink(href);
      setMobileMenuOpen(false);
      return;
    }
    
    // For other anchor links, scroll to the element
    const targetElement = document.getElementById(targetId);
    if (targetElement) {
      const headerOffset = 100;
      const elementPosition = targetElement.getBoundingClientRect().top;
      const offsetPosition = elementPosition + window.pageYOffset - headerOffset;

      window.scrollTo({
        top: offsetPosition,
        behavior: 'smooth',
      });
      
      // Update URL without triggering scroll
      window.history.pushState(null, '', href);
      setActiveLink(href);
      setMobileMenuOpen(false);
    }
  };

  return (
    <motion.header
      ref={navRef}
      className={styles.header}
      initial={false}
      animate={{}}
      transition={{ duration: 0.3, ease: 'easeOut' }}
    >
      <a
        href="/"
        className={styles.logo}
      >
        <img 
          src="/assets/images/logo/marketing_logo.png" 
          alt="PodaBio" 
          className={styles.logoImage}
        />
      </a>
      <NavigationMenu.Root className={styles.desktopNav}>
        <div className={styles.navSegmented}>
          <NavigationMenu.List className={styles.navLinks}>
            {navLinks.map((link) => (
              <NavigationMenu.Item key={link.href}>
                <NavigationMenu.Link
                  asChild
                >
                  <a
                    href={link.href}
                    className={`${styles.navLink} ${activeLink === link.href ? styles.active : ''}`}
                    onClick={(e) => handleLinkClick(link.href, e)}
                    data-active={activeLink === link.href}
                  >
                    {link.label}
                    {activeLink === link.href && (
                      <motion.div
                        className={styles.activeIndicator}
                        layoutId="activeIndicator"
                        transition={{ type: 'spring', stiffness: 380, damping: 30 }}
                      />
                    )}
                  </a>
                </NavigationMenu.Link>
              </NavigationMenu.Item>
            ))}
          </NavigationMenu.List>
          <NavigationMenu.Indicator className={styles.navIndicator}>
            <div className={styles.navIndicatorArrow} />
          </NavigationMenu.Indicator>
        </div>
      </NavigationMenu.Root>
      <div className={styles.navActions}>
        <motion.a
          href="/login.php"
          className={styles.btnSecondary}
          whileHover={{ scale: 1.05 }}
          whileTap={{ scale: 0.95 }}
        >
          Login
        </motion.a>
        <motion.a
          href="/signup.php"
          className={styles.btnPrimary}
          whileHover={{ scale: 1.05, boxShadow: '0 0 24px rgba(0, 255, 127, 0.6)' }}
          whileTap={{ scale: 0.95 }}
        >
          Get Started<span className={styles.cursor}>_</span>
        </motion.a>
      </div>
      <motion.button
        className={styles.mobileMenuButton}
        onClick={() => setMobileMenuOpen(!mobileMenuOpen)}
        aria-label="Toggle menu"
        aria-expanded={mobileMenuOpen}
        whileTap={{ scale: 0.95 }}
      >
        <AnimatePresence mode="wait">
          {mobileMenuOpen ? (
            <motion.div
              key="close"
              initial={{ rotate: -90, opacity: 0 }}
              animate={{ rotate: 0, opacity: 1 }}
              exit={{ rotate: 90, opacity: 0 }}
              transition={{ duration: 0.2 }}
            >
              <X size={24} weight="bold" />
            </motion.div>
          ) : (
            <motion.div
              key="menu"
              initial={{ rotate: 90, opacity: 0 }}
              animate={{ rotate: 0, opacity: 1 }}
              exit={{ rotate: -90, opacity: 0 }}
              transition={{ duration: 0.2 }}
            >
              <List size={24} weight="bold" />
            </motion.div>
          )}
        </AnimatePresence>
      </motion.button>

      {/* Mobile Menu */}
      <AnimatePresence>
        {mobileMenuOpen && (
          <>
            <motion.div
              className={styles.mobileMenuOverlay}
              initial={{ opacity: 0 }}
              animate={{ opacity: 1 }}
              exit={{ opacity: 0 }}
              onClick={() => setMobileMenuOpen(false)}
            />
            <motion.nav
              className={styles.mobileNav}
              initial={{ x: '100%' }}
              animate={{ x: 0 }}
              exit={{ x: '100%' }}
              transition={{ type: 'spring', damping: 25, stiffness: 200 }}
            >
              <ul className={styles.mobileNavLinks} role="list">
                {navLinks.map((link, index) => (
                  <motion.li
                    key={link.href}
                    initial={{ opacity: 0, x: 20 }}
                    animate={{ opacity: 1, x: 0 }}
                    transition={{ delay: index * 0.1 }}
                  >
                    <a
                      href={link.href}
                      className={`${styles.mobileLink} ${activeLink === link.href ? styles.active : ''}`}
                      onClick={(e) => {
                        handleLinkClick(link.href, e);
                      }}
                    >
                      {link.label}
                    </a>
                  </motion.li>
                ))}
              </ul>
              <div className={styles.mobileActions}>
                <motion.a
                  href="/login.php"
                  className={styles.btnSecondary}
                  whileHover={{ scale: 1.02 }}
                  whileTap={{ scale: 0.98 }}
                >
                  Login
                </motion.a>
                <motion.a
                  href="/signup.php"
                  className={styles.btnPrimary}
                  whileHover={{ scale: 1.02 }}
                  whileTap={{ scale: 0.98 }}
                >
                  Get Started<span className={styles.cursor}>_</span>
                </motion.a>
              </div>
            </motion.nav>
          </>
        )}
      </AnimatePresence>
    </motion.header>
  );
}

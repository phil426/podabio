import { useEffect, useRef } from 'react';

export function SmoothScrollController(): null {
  // Scroll resistance has been disabled - component now does nothing
  // This allows normal smooth scrolling through the testimonials section
  
  useEffect(() => {
    // Ensure any existing scroll resistance classes are removed
    document.documentElement.classList.remove('scroll-in-testimonials');
    document.body.classList.remove('scroll-in-testimonials');
    
    const testimonialsSection = document.querySelector('.testimonials-section');
    if (testimonialsSection) {
      testimonialsSection.classList.remove('scroll-resistance-active');
    }
  }, []);
  
  return null;
}


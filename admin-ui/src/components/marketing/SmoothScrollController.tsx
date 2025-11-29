import { useEffect, useRef } from 'react';

export function SmoothScrollController(): null {
  const testimonialsSectionRef = useRef<HTMLElement | null>(null);
  
  useEffect(() => {
    testimonialsSectionRef.current = document.querySelector('.testimonials-section');
    
    if (!testimonialsSectionRef.current) return;
    
    const section = testimonialsSectionRef.current;
    
    // CSS-only approach: Add class to html/body when in testimonials section
    // This allows CSS scroll-snap to activate immediately from the top
    const observer = new IntersectionObserver(
      (entries) => {
        entries.forEach((entry) => {
          // Only activate when section is meaningfully in view - not when scrolled past
          const isInView = entry.isIntersecting && entry.intersectionRatio > 0.1;
          const isScrollingPast = entry.boundingClientRect.top < 0 && entry.intersectionRatio < 0.3;
          
          if (isInView && !isScrollingPast) {
            document.documentElement.classList.add('scroll-in-testimonials');
            document.body.classList.add('scroll-in-testimonials');
            section.classList.add('scroll-resistance-active');
          } else {
            // Definitely remove when scrolling past
            document.documentElement.classList.remove('scroll-in-testimonials');
            document.body.classList.remove('scroll-in-testimonials');
            section.classList.remove('scroll-resistance-active');
          }
        });
      },
      {
        // Activate when section is in viewport
        threshold: [0, 0.1, 0.2, 0.5, 0.8, 1],
        rootMargin: '20% 0px -20% 0px' // Activate when section is 20% away, deactivate when 20% past
      }
    );
    
    observer.observe(section);
    
    return () => {
      observer.disconnect();
      document.documentElement.classList.remove('scroll-in-testimonials');
      document.body.classList.remove('scroll-in-testimonials');
    };
  }, []);
  
  return null;
}


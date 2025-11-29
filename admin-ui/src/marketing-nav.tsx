import React from 'react';
import ReactDOM from 'react-dom/client';
import { MarketingNav } from './components/marketing/MarketingNav';

// Disable Framer Motion analytics to prevent blocked script errors
if (typeof window !== 'undefined') {
  (window as any).__FRAMER_MOTION_CONFIG__ = {
    features: {
      measureLayout: true,
      animation: true,
    },
    reduceMotion: false,
  };
}

// Find the mount point
const rootElement = document.getElementById('marketing-nav-root');

if (rootElement) {
  const root = ReactDOM.createRoot(rootElement);
  root.render(
    <React.StrictMode>
      <MarketingNav />
    </React.StrictMode>
  );
} else {
  console.warn('Marketing nav root element not found');
}




import React from 'react';
import ReactDOM from 'react-dom/client';
import { MarketingNav } from './components/marketing/MarketingNav';

console.log("🚀 marketing-nav.tsx initializing...");

function mount() {
  const rootElement = document.getElementById('marketing-nav-root');
  console.log("🔍 Checking for root element:", rootElement);

  if (rootElement) {
    if (rootElement.hasChildNodes()) {
      // Optional: check if already mounted preventing double mount in strict mode dev
      console.warn("⚠️ Root element already has children");
    }

    console.log("✅ Root element found, mounting React app...");
    const root = ReactDOM.createRoot(rootElement);
    root.render(
      <React.StrictMode>
        <MarketingNav />
      </React.StrictMode>
    );
    console.log("✨ React render called");
  } else {
    console.error('❌ Marketing nav root element not found!');
  }
}

if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', mount);
} else {
  mount();
}




import React from 'react';
import ReactDOM from 'react-dom/client';
import { MarketingNav } from './components/marketing/MarketingNav';

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




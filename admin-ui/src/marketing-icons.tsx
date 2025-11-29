import React from 'react';
import ReactDOM from 'react-dom/client';
import { MarketingIcons } from './components/marketing/MarketingIcons';

// Wait for DOM to be ready
if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', initIcons);
} else {
  initIcons();
}

function initIcons() {
  // Create a mount point for icons
  const container = document.createElement('div');
  container.id = 'marketing-icons-root';
  container.style.display = 'none'; // Hidden container
  document.body.appendChild(container);

  const root = ReactDOM.createRoot(container);
  root.render(
    <React.StrictMode>
      <MarketingIcons />
    </React.StrictMode>
  );
}

import React from 'react';
import ReactDOM from 'react-dom/client';
import { SmoothScrollController } from './components/marketing/SmoothScrollController';

// Create a hidden mount point for the smooth scroll controller
let rootElement = document.getElementById('smooth-scroll-root');

if (!rootElement) {
  rootElement = document.createElement('div');
  rootElement.id = 'smooth-scroll-root';
  rootElement.style.display = 'none';
  document.body.appendChild(rootElement);
}

const root = ReactDOM.createRoot(rootElement);
root.render(
  <React.StrictMode>
    <SmoothScrollController />
  </React.StrictMode>
);


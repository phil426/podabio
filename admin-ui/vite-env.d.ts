/// <reference types="vite/client" />

declare global {
  interface Window {
    __CSRF_TOKEN__?: string;
    __APP_URL__?: string;
  }
}

export {};


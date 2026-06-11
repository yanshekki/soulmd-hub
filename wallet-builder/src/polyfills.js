import { Buffer } from 'buffer';

// Robust early polyfills for near-api-js + wallet-selector (must run before any dep code)
(function ensurePolyfills() {
  const g = (typeof globalThis !== 'undefined' ? globalThis : (typeof window !== 'undefined' ? window : self));
  g.global = g;
  g.globalThis = g;
  g.Buffer = Buffer;
  g.process = g.process || { env: { NODE_ENV: 'production' } };
  if (!g.process.env) g.process.env = {};
  if (!g.process.env.NODE_ENV) g.process.env.NODE_ENV = 'production';
  // Some libs look for window.Buffer directly
  if (typeof window !== 'undefined') {
    window.global = g;
    window.Buffer = Buffer;
    window.process = g.process;
  }
})();
import { defineConfig } from 'vite';
import { nodePolyfills } from 'vite-plugin-node-polyfills';

export default defineConfig({
  define: {
    global: 'globalThis',
    'process.env.NODE_ENV': '"production"',
    'globalThis.process.env.NODE_ENV': '"production"',
  },
  plugins: [
    nodePolyfills({
      include: ['buffer', 'process', 'crypto', 'stream', 'events', 'util', 'path'],
      globals: { Buffer: true, global: true, process: true },
      protocolImports: true,
    }),
  ],
  build: {
    outDir: '../public_html/assets',
    emptyOutDir: false,
    cssCodeSplit: false,
    sourcemap: false,
    minify: 'esbuild',
    rollupOptions: {
      input: 'src/index.js',
      output: {
        entryFileNames: 'wallet-selector-bundle.js',
        assetFileNames: 'wallet-selector-style.[ext]',
        format: 'iife',
        name: 'NearWalletBridge',
        // ensure our top-level assignments to window/globalThis are not shadowed
        extend: true
      }
    }
  }
});
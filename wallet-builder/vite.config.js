import { defineConfig } from 'vite';
import { nodePolyfills } from 'vite-plugin-node-polyfills';

export default defineConfig({
  define: {
    global: 'window',
  },
  plugins: [
    nodePolyfills({
      include: ['buffer', 'process', 'crypto', 'stream', 'events', 'util', 'path'],
      globals: { Buffer: true, global: true, process: true },
    }),
  ],
  build: {
    outDir: '../../public_html/assets',
    emptyOutDir: false,
    cssCodeSplit: false,
    rollupOptions: {
      input: 'src/index.js',
      output: {
        entryFileNames: 'wallet-selector-bundle.js',
        assetFileNames: 'wallet-selector-style.[ext]',
        format: 'iife',
        name: 'NearWalletBridge'
      }
    }
  }
});
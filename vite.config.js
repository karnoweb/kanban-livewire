import { defineConfig } from 'vite';

export default defineConfig({
  build: {
    outDir: 'resources/dist',
    emptyOutDir: false,
    rollupOptions: {
      input: 'resources/js/kanban.js',
    },
  },
});

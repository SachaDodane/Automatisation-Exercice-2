import { defineConfig } from 'vite';

export default defineConfig({
    root: './assets',
    build: {
        outDir: '../public/build',
        emptyOutDir: true,
        manifest: true,
        rollupOptions: {
            input: {
                app: './assets/js/app.js',
                style: './assets/css/style.css'
            }
        }
    },
    server: {
        strictPort: true,
        port: 5173
    }
});

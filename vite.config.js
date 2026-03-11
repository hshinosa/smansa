import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import react from '@vitejs/plugin-react';
import { compression } from 'vite-plugin-compression2';

export default defineConfig(({ mode }) => {
    // CDN URL from environment or empty for relative paths
    const cdnUrl = mode === 'production'
        ? (process.env.CDN_URL || '')
        : '';

    return {
        plugins: [
            laravel({
                input: ['resources/js/app.jsx'],
                refresh: true,
            }),
            react(),
            // Gzip compression
            compression({
                algorithm: 'gzip',
                exclude: [/\.(br)$/, /\.(gz)$/],
            }),
            // Brotli compression
            compression({
                algorithm: 'brotliCompress',
                exclude: [/\.(br)$/, /\.(gz)$/],
            }),
        ],
        base: cdnUrl || undefined,
        resolve: {
            alias: {
                '@': '/resources/js',
            },
        },
        build: {
            // CDN base URL for production
            base: cdnUrl || '/',
            // Optimize build
            rollupOptions: {
                output: {
                    manualChunks: {
                        'react-vendor': ['react', 'react-dom'],
                        'inertia-vendor': ['@inertiajs/react'],
                        'markdown-vendor': ['react-markdown', 'remark-gfm', 'rehype-raw'],
                        'ui-vendor': ['lucide-react'],
                    },
                    // Configure asset URLs for CDN
                    assetFileNames: (assetInfo) => {
                        // Add content hash to filenames for cache busting
                        if (/\.css$/.test(assetInfo.name)) {
                            return 'assets/[name]-[hash][extname]';
                        }
                        if (/\.js$/.test(assetInfo.name)) {
                            return 'assets/[name]-[hash][extname]';
                        }
                        return 'assets/[name]-[hash][extname]';
                    },
                    chunkFileNames: 'assets/[name]-[hash].js',
                    entryFileNames: 'assets/[name]-[hash].js',
                },
            },
            // Increase chunk size warning limit
            chunkSizeWarningLimit: 1000,
            // Minify with esbuild (faster, built-in)
            minify: 'esbuild',
            target: 'esnext',
        },
        optimizeDeps: {
            include: ['react', 'react-dom', '@inertiajs/react'],
        },
    };
});

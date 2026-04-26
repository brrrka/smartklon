import { defineConfig, loadEnv } from 'vite';
import laravel from 'laravel-vite-plugin';
import tailwindcss from '@tailwindcss/vite';

export default defineConfig(({ mode }) => {
    const env     = loadEnv(mode, process.cwd(), '');
    const appHost = new URL(env.APP_URL || 'http://127.0.0.1:8000').hostname;

    return {
        plugins: [
            laravel({
                input: ['resources/css/app.css', 'resources/js/app.js'],
                refresh: true,
            }),
            tailwindcss(),
        ],
        server: {
            // Dengarkan semua interface supaya bisa diakses via LAN IP
            host: '0.0.0.0',
            // HMR connection URL diarahkan ke hostname dari APP_URL
            // sehingga @vite() menghasilkan URL yang tepat di browser
            hmr: {
                host: appHost,
            },
            watch: {
                ignored: ['**/storage/framework/views/**'],
            },
        },
    };
});

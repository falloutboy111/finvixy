import {
    defineConfig
} from 'vite';
import laravel from 'laravel-vite-plugin';
import tailwindcss from "@tailwindcss/vite";

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js'],
            refresh: true,
        }),
        tailwindcss(),
    ],
    server: {
        cors: true,
        watch: {
            // Add 'vendor' and 'node_modules' to the ignore list
            ignored: [
                '**/storage/framework/views/**',
                '**/vendor/**',
                '**/node_modules/**',
            ],
        },
    },
});
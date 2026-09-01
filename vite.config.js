import tailwindcss from "@tailwindcss/vite";
import laravel from "laravel-vite-plugin";
import { defineConfig } from "vite";

export default defineConfig({
    plugins: [
        laravel({
            input: ["resources/css/app.css", "resources/js/app.js"],
            refresh: true,
            // fonts: [
            //     bunny("Instrument Sans", {
            //         weights: [400, 500, 600],
            //     }),
            // ],
        }),
        tailwindcss({
            optimize: true,
            minify: true,
        }),
    ],
    server: {
        host: "127.0.0.1", // Força o IPv4 para resolver o conflito do WebSocket
        watch: {
            ignored: ["**/storage/framework/views/**"],
        },
    },
});

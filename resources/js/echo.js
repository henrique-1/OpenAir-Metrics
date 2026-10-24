import Echo from "laravel-echo";
import Pusher from "pusher-js";

window.Pusher = Pusher;

const envHost = import.meta.env.VITE_REVERB_HOST;
const wsHost =
    !envHost || envHost === "127.0.0.1" || envHost === "localhost"
        ? window.location.hostname
        : envHost;

window.Echo = new Echo({
    broadcaster: "reverb",
    key: import.meta.env.VITE_REVERB_APP_KEY,
    wsHost: wsHost,
    wsPort: import.meta.env.VITE_REVERB_PORT ?? 80,
    wssPort: import.meta.env.VITE_REVERB_PORT ?? 443,
    forceTLS: (import.meta.env.VITE_REVERB_SCHEME ?? "https") === "https",
    enabledTransports: ["ws", "wss"],
});

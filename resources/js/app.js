import L from "leaflet";
import "leaflet/dist/leaflet.css";

// 1. Expõe o Leaflet globalmente PRIMEIRO, pois o plugin precisa achá-lo
window.L = L;

// 2. Importa os scripts originais baixados na sua pasta vendor
import "./vendor/leaflet-webgl-heatmap.js";
import "./vendor/webgl-heatmap.js";

// 3. Inicializa o cliente WebSocket Laravel Echo com Reverb
import "./echo.js";

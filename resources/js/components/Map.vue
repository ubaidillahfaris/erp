<script setup lang="ts">
import { onMounted, onUnmounted, ref } from 'vue';
import L from 'leaflet';
import 'leaflet/dist/leaflet.css';

const props = defineProps<{
    center?: [number, number];
    zoom?: number;
    markers?: Array<{
        position: [number, number];
        title?: string;
        content?: string;
    }>;
    isPicker?: boolean;
}>();

const emit = defineEmits<{
    (e: 'update:location', location: { lat: number, lng: number }): void;
}>();

const mapContainer = ref<HTMLElement | null>(null);
let map: L.Map | null = null;
const isLocating = ref(false);
let setupPickerMarker: ((latlng: L.LatLng) => void) | null = null;

// Custom Sage Pin SVG (Matches --primary: #84A59D)
const sagePinSvg = `
<svg width="36" height="36" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" style="filter: drop-shadow(0 2px 4px rgba(0,0,0,0.15));">
  <path d="M12 21.75C12 21.75 19.5 15.75 19.5 10.5C19.5 6.35786 16.1421 3 12 3C7.85786 3 4.5 6.35786 4.5 10.5C4.5 15.75 12 21.75 12 21.75Z" fill="#84A59D" stroke="white" stroke-width="1.5" stroke-linejoin="round"/>
  <circle cx="12" cy="10.5" r="3.75" fill="white"/>
</svg>
`;

const locateUser = () => {
    if (!navigator.geolocation) return;
    
    isLocating.value = true;
    navigator.geolocation.getCurrentPosition(
        (position) => {
            const latlng = L.latLng(position.coords.latitude, position.coords.longitude);
            if (map) {
                map.setView(latlng, 16);
                if (props.isPicker && setupPickerMarker) {
                    setupPickerMarker(latlng);
                    emit('update:location', { lat: latlng.lat, lng: latlng.lng });
                }
            }
            isLocating.value = false;
        },
        () => { isLocating.value = false; },
        { enableHighAccuracy: true }
    );
};

onMounted(() => {
    if (!mapContainer.value) return;

    const initialCenter = props.center || [-6.200000, 106.816666]; // Default to Jakarta
    const initialZoom = props.zoom || 13;

    map = L.map(mapContainer.value).setView(initialCenter, initialZoom);

    // Use CartoDB Positron for a minimalist look
    L.tileLayer('https://{s}.basemaps.cartocdn.com/light_all/{z}/{x}/{y}{r}.png', {
        attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors &copy; <a href="https://carto.com/attributions">CARTO</a>',
        subdomains: 'abcd',
        maxZoom: 20
    }).addTo(map);

    const customIcon = L.divIcon({
        html: sagePinSvg,
        className: 'custom-sage-marker',
        iconSize: [36, 36],
        iconAnchor: [18, 36],
        popupAnchor: [0, -32],
    });

    // Add static markers if any
    if (props.markers) {
        props.markers.forEach(m => {
            const marker = L.marker(m.position, { icon: customIcon }).addTo(map!);
            if (m.title || m.content) {
                marker.bindPopup(`
                    <div class="p-1 font-sans">
                        <h4 class="font-bold text-sm text-foreground">${m.title || ''}</h4>
                        <p class="text-xs text-muted-foreground mt-0.5">${m.content || ''}</p>
                    </div>
                `);
            }
        });
    }

    // Interactive Picker Mode
    if (props.isPicker) {
        let pickerMarker: L.Marker | null = null;
        
        setupPickerMarker = (latlng: L.LatLng) => {
            if (pickerMarker) {
                pickerMarker.setLatLng(latlng);
            } else if (map) {
                const marker = L.marker(latlng, { 
                    draggable: true,
                    icon: customIcon
                }).addTo(map);
                
                pickerMarker = marker;
                
                marker.on('dragend', () => {
                    const pos = marker.getLatLng();
                    emit('update:location', { lat: pos.lat, lng: pos.lng });
                });
            }
        };

        if (props.center && map) {
            setupPickerMarker(L.latLng(props.center[0], props.center[1]));
        }

        map?.on('click', (e: L.LeafletMouseEvent) => {
            setupPickerMarker?.(e.latlng);
            emit('update:location', { lat: e.latlng.lat, lng: e.latlng.lng });
        });
    }

    setTimeout(() => { map?.invalidateSize(); }, 100);
});

onUnmounted(() => {
    if (map) {
        map.remove();
        map = null;
    }
});
</script>

<template>
    <div class="w-full h-full min-h-[400px] rounded-md overflow-hidden border border-border/40 relative z-0 bg-[#fdfdfd]">
        <div ref="mapContainer" class="w-full h-full"></div>
        
        <!-- Locate Me Button -->
        <button 
            @click="locateUser"
            type="button"
            class="absolute bottom-6 right-3 z-[1000] bg-white text-slate-500 p-2.5 rounded-full border border-slate-200 shadow-lg hover:bg-slate-50 hover:text-primary transition-all active:scale-95 disabled:opacity-50 group"
            :disabled="isLocating"
        >
            <svg 
                v-if="!isLocating"
                xmlns="http://www.w3.org/2000/svg" 
                width="20" height="20" 
                viewBox="0 0 24 24" fill="none" 
                stroke="currentColor" stroke-width="2.2" 
                stroke-linecap="round" stroke-linejoin="round"
                class="group-hover:text-[#84A59D]"
            >
                <circle cx="12" cy="12" r="10"/>
                <circle cx="12" cy="12" r="3"/>
                <line x1="12" y1="2" x2="12" y2="5"/>
                <line x1="12" y1="19" x2="12" y2="22"/>
                <line x1="2" y1="12" x2="5" y2="12"/>
                <line x1="19" y1="12" x2="22" y2="12"/>
            </svg>
            <div v-else class="w-5 h-5 border-2 border-[#84A59D]/30 border-t-[#84A59D] rounded-full animate-spin"></div>
        </button>
    </div>
</template>

<style scoped>
:deep(.leaflet-container) {
    width: 100%;
    height: 100%;
    background: transparent;
    font-family: inherit;
}

:deep(.custom-sage-marker) {
    background: transparent;
    border: none;
}

/* Custom styling for Zoom controls - Tight & Minimal */
:deep(.leaflet-control-zoom) {
    border: none !important;
    margin: 12px !important;
}

:deep(.leaflet-control-zoom-in),
:deep(.leaflet-control-zoom-out) {
    background-color: white !important;
    color: #64748b !important;
    width: 28px !important;
    height: 28px !important;
    line-height: 28px !important;
    border: 1px solid rgba(226, 232, 240, 1) !important;
    font-size: 14px !important;
    font-weight: 600 !important;
    transition: all 0.2s ease !important;
}

:deep(.leaflet-control-zoom-in) { border-radius: 4px 4px 0 0 !important; }
:deep(.leaflet-control-zoom-out) { border-radius: 0 0 4px 4px !important; border-top: none !important; }

:deep(.leaflet-control-zoom-in:hover),
:deep(.leaflet-control-zoom-out:hover) {
    background-color: #f8fafc !important;
    color: #0f172a !important;
}

/* Minimalist Popups */
:deep(.leaflet-popup-content-wrapper) {
    border-radius: 4px !important;
    border: 1px solid rgba(226, 232, 240, 1) !important;
    box-shadow: 0 4px 12px rgba(0,0,0,0.05) !important;
}
</style>

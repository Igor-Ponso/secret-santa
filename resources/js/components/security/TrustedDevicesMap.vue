<script setup lang="ts">
import type { TrustedDevice } from '@/interfaces/security';
import { nextTick, onMounted, ref, watch } from 'vue';
import { useI18n } from 'vue-i18n';
// Lazy import Leaflet only when component mounted in browser to shrink initial JS payload
let LModule: typeof import('leaflet') | null = null;
let leafletLoaded = false;
async function ensureLeaflet() {
    if (leafletLoaded) return;
    const mod = await import(/* webpackChunkName: 'leaflet' */ 'leaflet');
    await import('leaflet/dist/leaflet.css');
    LModule = mod.default ? (mod as any).default : mod; // handle both ESM/CJS default shapes
    leafletLoaded = true;
}
function getL(): typeof import('leaflet') {
    if (!LModule) {
        throw new Error('Leaflet not loaded');
    }
    return LModule;
}

const props = defineProps<{
    devices: (TrustedDevice & { latitude?: number | null; longitude?: number | null })[];
    currentId: number | null;
}>();

const { t } = useI18n();
let map: import('leaflet').Map | null = null; // assigned after load
const mapEl = ref<HTMLDivElement | null>(null);
let markers: import('leaflet').Layer[] = [];

async function renderMap() {
    if (!mapEl.value) return;
    if (!leafletLoaded) {
        try {
            await ensureLeaflet();
        } catch {
            // silently abort if leaflet fails (offline or blocked)
            return;
        }
    }
    const L = getL();
    if (!map) {
        map = L.map(mapEl.value, { zoomControl: false, attributionControl: false });
        map.setView([0, 0], 2);
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            maxZoom: 19,
            attribution: '© OpenStreetMap',
        }).addTo(map);
    }
    // clear markers
    markers.forEach((m) => m.remove());
    markers = [];
    const bounds: any[] = [];
    props.devices.forEach((d) => {
        if (d.latitude && d.longitude) {
            const marker = L.circleMarker([d.latitude, d.longitude], {
                radius: d.id === props.currentId ? 8 : 5,
                color: d.id === props.currentId ? '#16a34a' : '#2563eb',
                weight: 2,
                fillColor: d.id === props.currentId ? '#16a34a' : '#3b82f6',
                fillOpacity: 0.6,
            }).addTo(map as import('leaflet').Map);
            marker.bindTooltip(`${d.name || 'Device #' + d.id}`);
            markers.push(marker);
            bounds.push([d.latitude, d.longitude]);
        }
    });
    if (bounds.length) {
        map.fitBounds(bounds, { padding: [20, 20] });
    }
}

watch(
    () => props.devices,
    () => {
        void renderMap();
    },
    { deep: true },
);
watch(
    () => props.currentId,
    () => {
        void renderMap();
    },
);

onMounted(async () => {
    await nextTick();
    void renderMap();
});
</script>

<template>
    <div class="space-y-2">
        <h4 class="text-xs font-medium uppercase tracking-wide text-muted-foreground">
            {{ t('security.devices.map_title', 'Localização Aproximada') }}
        </h4>
        <div ref="mapEl" class="h-56 w-full overflow-hidden rounded-md border"></div>
        <p class="text-[10px] text-muted-foreground">
            {{ t('security.devices.map_disclaimer', 'Localização baseada em IP (aproximada).') }}
        </p>
    </div>
</template>

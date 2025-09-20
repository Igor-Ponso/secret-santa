<script setup lang="ts">
import type { TrustedDevice } from '@/interfaces/security';
import { computed, onMounted, ref, watch } from 'vue';
import { useI18n } from 'vue-i18n';
import DeviceCard from './DeviceCard.vue';

const props = defineProps<{
    device: TrustedDevice & { latitude?: number | null; longitude?: number | null };
    isCurrent: boolean;
}>();

const emit = defineEmits<{
    (e: 'revoke', id: number): void;
    (e: 'rename', payload: { id: number; name: string }): void;
    (e: 'reveal-ip', id: number): void;
}>();

const { t } = useI18n();
const mapEl = ref<HTMLDivElement | null>(null);
let map: import('leaflet').Map | null = null;
let marker: import('leaflet').Layer | null = null;
let LModule: typeof import('leaflet') | null = null;
let leafletLoaded = false;

async function ensureLeaflet() {
    if (leafletLoaded) return;
    const mod = await import('leaflet');
    await import('leaflet/dist/leaflet.css');
    LModule = mod.default ? (mod as any).default : mod;
    leafletLoaded = true;
}
function getL() {
    if (!LModule) throw new Error('Leaflet not loaded');
    return LModule;
}

async function renderMiniMap() {
    if (!mapEl.value) return;
    if (!props.device.latitude || !props.device.longitude) return;
    if (!leafletLoaded) {
        try {
            await ensureLeaflet();
        } catch {
            return;
        }
    }
    const L = getL();
    if (!map) {
        map = L.map(mapEl.value, {
            zoomControl: false,
            attributionControl: false,
            dragging: false,
            scrollWheelZoom: false,
            doubleClickZoom: false,
            boxZoom: false,
            keyboard: false,
        }).setView([props.device.latitude, props.device.longitude], 4);
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { maxZoom: 19 }).addTo(map);
    }
    if (marker) {
        (map as any).removeLayer(marker);
        marker = null;
    }
    marker = L.circleMarker([props.device.latitude, props.device.longitude], {
        radius: props.isCurrent ? 8 : 6,
        color: props.isCurrent ? '#16a34a' : '#2563eb',
        weight: 2,
        fillColor: props.isCurrent ? '#16a34a' : '#3b82f6',
        fillOpacity: 0.55,
    }).addTo(map);
}

watch(
    () => [props.device.latitude, props.device.longitude, props.isCurrent],
    () => {
        void renderMiniMap();
    },
);
onMounted(() => {
    void renderMiniMap();
});

const hasGeo = computed(() => !!(props.device.latitude && props.device.longitude));
</script>

<template>
    <div
        class="overflow-hidden rounded-lg border bg-card/60 p-0 shadow-sm transition"
        :class="isCurrent ? 'border-green-500/60 bg-green-50/70 ring-2 ring-green-500/40 dark:bg-green-900/10 dark:ring-green-600/40' : ''"
    >
        <!-- Info Block -->
        <div class="p-4">
            <DeviceCard
                :device="device"
                :is-current="isCurrent"
                :embedded="true"
                @revoke="(id) => emit('revoke', id)"
                @rename="(p) => emit('rename', p)"
                @reveal-ip="(id) => emit('reveal-ip', id)"
            />
        </div>
        <!-- Divider -->
        <div class="h-px w-full bg-border"></div>
        <!-- Map Block -->
        <div class="space-y-2 p-4 pt-3">
            <h5 class="text-[11px] font-medium uppercase tracking-wide text-muted-foreground">
                {{ t('security.devices.map_title', 'Localização') }}
            </h5>
            <div v-if="hasGeo" ref="mapEl" class="h-44 w-full overflow-hidden rounded-md border"></div>
            <div v-else class="grid h-44 w-full place-items-center rounded-md border border-dashed text-[11px] text-muted-foreground">
                {{ t('security.devices.no_geo', 'Sem dados de localização') }}
            </div>
            <p class="text-[10px] leading-snug text-muted-foreground">
                {{ t('security.devices.map_disclaimer', 'Localização baseada em IP (aproximada).') }}
            </p>
        </div>
    </div>
</template>

<script setup lang="ts">
import { computed } from 'vue';

interface Props {
  color?: string; // tailwind color utility or arbitrary e.g. 'red-400'
  size?: number | string; // px size
  pulse?: boolean;
  ring?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
  color: 'red-400',
  size: 8,
  pulse: false,
  ring: true,
});

// Build classes dynamically; allow passing e.g. emerald-400, red-500
const colorClass = computed(() => {
  // If user passes utility like 'bg-red-400' keep it, else prefix bg-
  if (/^bg-/.test(props.color)) return props.color;
  return `bg-${props.color}`;
});

const style = computed(() => {
  const s = typeof props.size === 'number' ? `${props.size}px` : props.size;
  return { width: s, height: s } as Record<string, string>;
});
</script>

<template>
  <span
    :class="[
      'inline-block rounded-full',
      colorClass,
      pulse ? 'animate-bullet-pulse' : '',
      ring ? 'shadow-[0_0_0_3px_rgba(255,255,255,0.18)]' : ''
    ]"
    :style="style"
    aria-hidden="true"
  />
</template>

<style scoped>
@keyframes bulletPulse {
  0% { transform: scale(1); filter: drop-shadow(0 0 0 rgba(255,255,255,0)); opacity: .95; }
  40% { transform: scale(1.18); filter: drop-shadow(0 0 4px rgba(255,255,255,0.45)); opacity: 1; }
  60% { transform: scale(1.12); filter: drop-shadow(0 0 6px rgba(255,255,255,0.35)); opacity: .92; }
  100% { transform: scale(1); filter: drop-shadow(0 0 0 rgba(255,255,255,0)); opacity: .95; }
}
.animate-bullet-pulse { animation: bulletPulse 2.8s ease-in-out infinite; }
</style>

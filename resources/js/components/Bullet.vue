<script setup lang="ts">
import { computed } from 'vue';

interface Props {
    color?: string; // tailwind color utility or arbitrary e.g. 'red-400'
    size?: number | string; // px size or any CSS size
    pulse?: boolean; // legacy Bool (maps to variant="pulse")
    ring?: boolean; // outer subtle ring
    variant?: 'pulse' | 'breathing' | 'glow' | 'none'; // animation style
    desync?: boolean; // randomize animation delay for multiple bullets
}

const props = withDefaults(defineProps<Props>(), {
    color: 'red-400',
    size: 8,
    pulse: false,
    ring: true,
    variant: 'pulse',
    desync: false,
});

// Build classes dynamically; allow passing e.g. emerald-400, red-500
const colorClass = computed(() => {
    // If user passes utility like 'bg-red-400' keep it, else prefix bg-
    if (/^bg-/.test(props.color)) return props.color;
    return `bg-${props.color}`;
});

const style = computed(() => {
    const s = typeof props.size === 'number' ? `${props.size}px` : props.size;
    const delay = props.desync ? `${(Math.random() * 2).toFixed(2)}s` : undefined;
    return { width: s, height: s, animationDelay: delay } as Record<string, string>;
});

const animationClass = computed(() => {
    // Backward compatibility: pulse=true forces variant pulse
    const variant = props.pulse ? 'pulse' : props.variant;
    switch (variant) {
        case 'breathing':
            return 'animate-bullet-breathing';
        case 'glow':
            return 'animate-bullet-glow';
        case 'none':
            return '';
        case 'pulse':
        default:
            return 'animate-bullet-pulse';
    }
});
</script>

<template>
    <span
        :class="['inline-block rounded-full', colorClass, animationClass, ring ? 'shadow-[0_0_0_3px_rgba(255,255,255,0.18)]' : '']"
        :style="style"
        aria-hidden="true"
    />
</template>

<style scoped>
@keyframes bulletPulse {
    0% {
        transform: scale(1);
        filter: drop-shadow(0 0 0 rgba(255, 255, 255, 0));
        opacity: 0.95;
    }
    40% {
        transform: scale(1.18);
        filter: drop-shadow(0 0 4px rgba(255, 255, 255, 0.45));
        opacity: 1;
    }
    60% {
        transform: scale(1.12);
        filter: drop-shadow(0 0 6px rgba(255, 255, 255, 0.35));
        opacity: 0.92;
    }
    100% {
        transform: scale(1);
        filter: drop-shadow(0 0 0 rgba(255, 255, 255, 0));
        opacity: 0.95;
    }
}
.animate-bullet-pulse {
    animation: bulletPulse 2.8s ease-in-out infinite;
}

@keyframes bulletBreathing {
    0%,
    100% {
        transform: scale(1);
        opacity: 0.9;
    }
    50% {
        transform: scale(1.12);
        opacity: 1;
    }
}
.animate-bullet-breathing {
    animation: bulletBreathing 3.6s ease-in-out infinite;
}

@keyframes bulletGlow {
    0% {
        box-shadow: 0 0 0 0 rgba(255, 255, 255, 0);
        transform: scale(1);
    }
    40% {
        box-shadow: 0 0 6px 2px rgba(255, 255, 255, 0.45);
        transform: scale(1.05);
    }
    70% {
        box-shadow: 0 0 10px 4px rgba(255, 255, 255, 0.25);
        transform: scale(1.02);
    }
    100% {
        box-shadow: 0 0 0 0 rgba(255, 255, 255, 0);
        transform: scale(1);
    }
}
.animate-bullet-glow {
    animation: bulletGlow 3.2s ease-in-out infinite;
}
</style>

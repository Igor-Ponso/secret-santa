import { onBeforeUnmount, ref } from 'vue';

export interface CountdownAPI {
    remaining: ReturnType<typeof ref<number>>;
    start: (seconds?: number) => void;
    stop: () => void;
    isExpired: () => boolean;
    mmss: () => string;
}

export const useCountdown = (initialSeconds = 0): CountdownAPI => {
    const remaining = ref(initialSeconds);
    let timer: ReturnType<typeof setInterval> | null = null;

    const stop = () => {
        if (timer) {
            clearInterval(timer);
            timer = null;
        }
    };

    const tick = () => {
        if (remaining.value > 0) remaining.value -= 1;
        if (remaining.value <= 0) stop();
    };

    const start = (seconds?: number) => {
        if (typeof seconds === 'number') remaining.value = seconds;
        if (remaining.value <= 0) return;
        stop();
        timer = setInterval(tick, 1000);
    };

    const isExpired = () => remaining.value <= 0;

    const mmss = () => {
        const m = Math.floor(remaining.value / 60)
            .toString()
            .padStart(2, '0');
        const s = Math.floor(remaining.value % 60)
            .toString()
            .padStart(2, '0');
        return `${m}:${s}`;
    };

    onBeforeUnmount(stop);

    return { remaining, start, stop, isExpired, mmss };
};

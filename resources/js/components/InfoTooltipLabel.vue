<script setup lang="ts">
import { Tooltip, TooltipContent, TooltipProvider, TooltipTrigger } from '@/components/ui/tooltip';
import { Info } from 'lucide-vue-next';

interface Props {
    label: string;
    tooltip: string;
    asBlock?: boolean;
}
const props = withDefaults(defineProps<Props>(), { asBlock: false });
</script>

<template>
    <TooltipProvider>
        <Tooltip>
            <TooltipTrigger as-child>
                <span :class="['inline-flex cursor-help items-center gap-1 font-semibold tracking-wide', props.asBlock ? 'block' : '']">
                    <span>{{ label }}</span>
                    <Info class="h-3.5 w-3.5 opacity-70" />
                </span>
            </TooltipTrigger>
            <TooltipContent class="max-w-xs">
                <p class="text-xs leading-snug">{{ tooltip }}</p>
            </TooltipContent>
        </Tooltip>
    </TooltipProvider>
</template>

<style scoped></style>

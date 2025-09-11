<script setup lang="ts">
import { cn } from '@/lib/utils';
import { PopoverTrigger, type PopoverTriggerProps, useForwardProps } from 'radix-vue';
import { computed, type HTMLAttributes } from 'vue';
const props = defineProps<PopoverTriggerProps & { class?: HTMLAttributes['class'] }>();
const delegated = computed(() => {
  const { class: _c, ...rest } = props; return rest;
});
const forwarded = useForwardProps(delegated);
</script>
<template>
  <PopoverTrigger v-bind="forwarded" :class="cn(props.class)"><slot /></PopoverTrigger>
</template>

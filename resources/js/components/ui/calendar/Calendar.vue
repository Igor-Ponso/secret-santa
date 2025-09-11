<script setup lang="ts">
// Placeholder Calendar component until full shadcn-vue Calendar integration
// You can swap this with generated Calendar from shadcn-vue CLI (reka-ui) later.
import { ref } from 'vue';

interface Props {
  modelValue?: Date | null
}
const props = defineProps<Props>()
const emit = defineEmits<{ (e:'update:modelValue', v: Date | null): void }>()
const today = new Date()
const value = ref(props.modelValue ?? today)
function selectToday() {
  value.value = today
  emit('update:modelValue', today)
}
</script>
<template>
  <div class="text-xs space-y-2">
    <div class="flex items-center justify-between">
      <span class="font-medium">{{ value.toLocaleDateString() }}</span>
      <button type="button" class="rounded border px-2 py-1 hover:bg-accent" @click="selectToday">Today</button>
    </div>
    <p class="text-muted-foreground">(Calendar placeholder – replace with shadcn-vue Calendar)</p>
  </div>
</template>

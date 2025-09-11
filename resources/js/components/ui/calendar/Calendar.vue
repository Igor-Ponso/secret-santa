<script setup lang="ts">
import { computed, ref, watch } from 'vue';

interface Props {
  modelValue?: Date | null;
  min?: Date | null; // disable selections before
  startWeekOn?: 0 | 1; // 0 Sunday 1 Monday
}

const props = withDefaults(defineProps<Props>(), {
  modelValue: null,
  min: null,
  startWeekOn: 1,
});

const emit = defineEmits<{ (e: 'update:modelValue', v: Date | null): void }>();

const selected = ref<Date | null>(props.modelValue ? new Date(props.modelValue) : null);
watch(
  () => props.modelValue,
  (v) => {
    selected.value = v ? new Date(v) : null;
  },
);

// Visible month (1st day)
const today = new Date();
const visibleMonth = ref<Date>(selected.value ? new Date(selected.value.getFullYear(), selected.value.getMonth(), 1) : new Date(today.getFullYear(), today.getMonth(), 1));

function changeMonth(delta: number) {
  const d = new Date(visibleMonth.value);
  d.setMonth(d.getMonth() + delta);
  visibleMonth.value = d;
}

const monthLabel = computed(() => visibleMonth.value.toLocaleDateString(undefined, { month: 'long', year: 'numeric' }));

const weekdayLabels = computed(() => {
  const base = [] as string[];
  for (let i = 0; i < 7; i++) {
    const refDate = new Date(2024, 0, i + (props.startWeekOn === 1 ? 1 : 0));
    base.push(refDate.toLocaleDateString(undefined, { weekday: 'short' }).slice(0, 2));
  }
  if (props.startWeekOn === 1) {
    // ensure Monday-first order
    return [...base.slice(1), base[0]];
  }
  return base;
});

interface CellDay {
  date: Date;
  inMonth: boolean;
  disabled: boolean;
  isToday: boolean;
  isSelected: boolean;
}

const cells = computed<CellDay[]>(() => {
  const first = new Date(visibleMonth.value.getFullYear(), visibleMonth.value.getMonth(), 1);
  const startWeekDay = (first.getDay() + (7 - (props.startWeekOn === 1 ? 1 : 0))) % 7; // offset
  const startDate = new Date(first);
  startDate.setDate(first.getDate() - startWeekDay);
  const out: CellDay[] = [];
  for (let i = 0; i < 42; i++) {
    const d = new Date(startDate);
    d.setDate(startDate.getDate() + i);
    const inMonth = d.getMonth() === visibleMonth.value.getMonth();
    const disabled = props.min ? d < stripTime(props.min) : false;
    const isToday = sameDate(d, today);
    const isSelected = selected.value ? sameDate(d, selected.value) : false;
    out.push({ date: d, inMonth, disabled, isToday, isSelected });
  }
  return out;
});

function sameDate(a: Date, b: Date) {
  return a.getFullYear() === b.getFullYear() && a.getMonth() === b.getMonth() && a.getDate() === b.getDate();
}
function stripTime(d: Date) {
  return new Date(d.getFullYear(), d.getMonth(), d.getDate());
}

function select(day: CellDay) {
  if (day.disabled) return;
  selected.value = day.date;
  emit('update:modelValue', new Date(day.date));
  // auto navigate if choosing outside month (rare if we show grayed days)
  if (!day.inMonth) {
    visibleMonth.value = new Date(day.date.getFullYear(), day.date.getMonth(), 1);
  }
}

function selectToday() {
  const base = stripTime(today);
  selected.value = base;
  visibleMonth.value = new Date(base.getFullYear(), base.getMonth(), 1);
  emit('update:modelValue', base);
}
</script>

<template>
  <div class="space-y-2">
    <div class="flex items-center justify-between gap-2 text-xs font-medium">
      <button type="button" class="rounded-md border px-2 py-1 hover:bg-accent" @click="changeMonth(-1)" aria-label="Previous month">‹</button>
      <div class="flex-1 text-center capitalize">{{ monthLabel }}</div>
      <button type="button" class="rounded-md border px-2 py-1 hover:bg-accent" @click="changeMonth(1)" aria-label="Next month">›</button>
    </div>
    <div class="grid grid-cols-7 gap-1 text-center text-[10px] uppercase tracking-wide text-muted-foreground">
      <span v-for="w in weekdayLabels" :key="w">{{ w }}</span>
    </div>
    <div class="grid grid-cols-7 gap-1 text-center text-xs">
      <button
        v-for="(c,i) in cells"
        :key="i"
        type="button"
        class="h-8 w-8 rounded-md focus:outline-none focus:ring-2 focus:ring-ring"
        :class="[
          !c.inMonth ? 'text-muted-foreground/40' : '',
          c.disabled ? 'opacity-30 cursor-not-allowed' : 'hover:bg-accent',
          c.isSelected ? 'bg-primary text-primary-foreground hover:bg-primary/90' : '',
          !c.isSelected && c.isToday ? 'border border-primary/60' : ''
        ]"
        :disabled="c.disabled"
        @click="select(c)"
      >
        {{ c.date.getDate() }}
      </button>
    </div>
    <div class="flex items-center justify-between pt-1">
      <button type="button" class="text-[11px] text-primary hover:underline" @click="selectToday">Hoje</button>
      <div v-if="selected" class="text-[11px] text-muted-foreground">
        {{ selected.toLocaleDateString() }}
      </div>
    </div>
  </div>
</template>

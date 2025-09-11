<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Calendar } from '@/components/ui/calendar';
import { Popover, PopoverContent, PopoverTrigger } from '@/components/ui/popover/index';
import { Calendar as CalendarIcon, Clock } from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';

interface Props {
    modelValue: string | null; // ISO string or null
    label?: string;
    required?: boolean;
    min?: string | null; // ISO min (date boundary)
    placeholder?: string;
    disabled?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
    label: 'Draw Date',
    required: false,
    min: null,
    placeholder: 'Select date & time',
    disabled: false,
});

const emit = defineEmits<{
    (e: 'update:modelValue', value: string | null): void;
}>();

// Internal date state as Date object
const open = ref(false);
const internal = ref<Date | null>(props.modelValue ? new Date(props.modelValue) : null);

watch(
    () => props.modelValue,
    (v) => {
        if (!v) internal.value = null;
        else internal.value = new Date(v);
    },
);

// Using placeholder Calendar (Date based)
const calendarValue = ref<Date | null>(internal.value);
const minDate = computed(() => (props.min ? new Date(props.min) : null));

function onCalendarSelect(v: Date | null) {
    calendarValue.value = v;
    if (!v) {
        internal.value = null;
        emit('update:modelValue', null);
        return;
    }
    // merge time if already set
    const base = internal.value ?? new Date();
    const merged = new Date(v.getFullYear(), v.getMonth(), v.getDate(), base.getHours(), base.getMinutes(), 0, 0);
    internal.value = merged;
    emit('update:modelValue', merged.toISOString());
}

const hours = ref<string>(internal.value ? String(internal.value.getHours()).padStart(2, '0') : '12');
const minutes = ref<string>(internal.value ? String(internal.value.getMinutes()).padStart(2, '0') : '00');

watch(internal, (v) => {
    if (v) {
        hours.value = String(v.getHours()).padStart(2, '0');
        minutes.value = String(v.getMinutes()).padStart(2, '0');
    }
});

function applyTime() {
    if (!internal.value) return;
    const h = parseInt(hours.value, 10);
    const m = parseInt(minutes.value, 10);
    internal.value.setHours(h);
    internal.value.setMinutes(m);
    internal.value.setSeconds(0);
    internal.value.setMilliseconds(0);
    emit('update:modelValue', internal.value.toISOString());
}

function clearDate() {
    internal.value = null;
    emit('update:modelValue', null);
}

const displayValue = computed(() =>
    internal.value
        ? internal.value.toLocaleDateString(undefined, {
              year: 'numeric',
              month: 'short',
              day: '2-digit',
          }) +
          ' ' +
          internal.value.toLocaleTimeString(undefined, { hour: '2-digit', minute: '2-digit', hour12: false })
        : props.placeholder,
);
</script>

<template>
    <div class="space-y-2">
        <label class="flex items-center gap-1 text-sm font-medium"> {{ label }}<span v-if="required" class="text-destructive">*</span> </label>
        <Popover v-model:open="open">
            <PopoverTrigger as-child>
                <Button variant="outline" type="button" class="w-full justify-between px-3 py-2 text-left font-normal">
                    <span class="truncate" :class="!internal ? 'text-muted-foreground' : ''">{{ displayValue }}</span>
                    <CalendarIcon class="h-4 w-4 opacity-70" />
                </Button>
            </PopoverTrigger>
            <PopoverContent class="w-[300px] space-y-3 p-3" align="start">
                <Calendar v-model="calendarValue" :min="minDate" @update:model-value="onCalendarSelect" class="border-none p-0" />
                <div class="flex items-center gap-2 border-t pt-2">
                    <Clock class="h-4 w-4 text-muted-foreground" />
                    <input
                        v-model="hours"
                        @change="applyTime"
                        type="number"
                        min="0"
                        max="23"
                        class="w-14 rounded-md border bg-background px-2 py-1 text-xs"
                    />
                    <span class="text-xs">:</span>
                    <input
                        v-model="minutes"
                        @change="applyTime"
                        type="number"
                        min="0"
                        max="59"
                        class="w-14 rounded-md border bg-background px-2 py-1 text-xs"
                    />
                    <div class="ml-auto flex gap-2">
                        <button type="button" class="text-[11px] text-muted-foreground hover:underline" @click="clearDate">Clear</button>
                        <button type="button" class="text-[11px] text-primary hover:underline" @click="open = false">Done</button>
                    </div>
                </div>
            </PopoverContent>
        </Popover>
    </div>
</template>

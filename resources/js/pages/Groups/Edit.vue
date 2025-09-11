<script setup lang="ts">
import { Calendar } from '@/components/ui/calendar';
import { Popover, PopoverContent, PopoverTrigger } from '@/components/ui/popover';
import AppLayout from '@/layouts/AppLayout.vue';
import { cn } from '@/lib/utils';
import type { BreadcrumbItem } from '@/types';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { CalendarDate, DateFormatter, getLocalTimeZone, type DateValue } from '@internationalized/date';
import { Calendar as CalendarIcon } from 'lucide-vue-next';
import { ref, watch } from 'vue';

interface GroupPayload {
    id: number;
    name: string;
    description: string | null;
    min_value: number | null;
    max_value: number | null;
    draw_at: string | null;
}

const props = defineProps<{ group: GroupPayload }>();

const form = useForm({
    name: props.group.name,
    description: props.group.description ?? '',
    min_value: props.group.min_value,
    max_value: props.group.max_value,
    draw_at: props.group.draw_at ?? null,
});

// Date picker state (DateValue)
const tz = getLocalTimeZone();
const df = new DateFormatter('en-US', { dateStyle: 'long' });
const dateValue = ref<DateValue | undefined>(
    form.draw_at
        ? (new CalendarDate(
              new Date(form.draw_at).getFullYear(),
              new Date(form.draw_at).getMonth() + 1,
              new Date(form.draw_at).getDate(),
          ) as DateValue)
        : undefined,
);

function onCalendarUpdate(val: unknown) {
    // Reka UI may emit complex date objects. We only care if it has toDate.
    if (val && typeof val === 'object' && 'toDate' in (val as any)) {
        dateValue.value = val as DateValue;
    } else if (Array.isArray(val) && val.length && typeof val[0] === 'object') {
        dateValue.value = val[0] as DateValue;
    } else if (!val) {
        dateValue.value = undefined;
    }
}

// Sync dateValue -> form.draw_at
watch(dateValue, (v) => {
    if (!v) {
        form.draw_at = null;
        return;
    }
    const d = v.toDate(tz);
    d.setHours(12, 0, 0, 0); // normalize to noon local to avoid TZ shift
    form.draw_at = d.toISOString();
});

// Sync form.draw_at -> dateValue (in case server returns new value or is reset)
watch(
    () => form.draw_at,
    (iso) => {
        if (!iso) {
            dateValue.value = undefined;
            return;
        }
        const js = new Date(iso);
        dateValue.value = new CalendarDate(js.getFullYear(), js.getMonth() + 1, js.getDate());
    },
);

function submit() {
    form.put(route('groups.update', props.group.id));
}

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Groups', href: '/groups' },
    { title: 'Edit', href: `/groups/${props.group.id}/edit` },
];
</script>

<template>
    <Head :title="`Edit: ${props.group.name}`" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="mx-auto max-w-xl space-y-8 p-4">
            <div class="flex items-center justify-between">
                <h1 class="text-xl font-semibold tracking-tight">Edit Group</h1>
                <Link :href="route('groups.index')" class="text-sm text-primary hover:underline">Back</Link>
            </div>
            <form @submit.prevent="submit" class="space-y-6">
                <div class="space-y-2">
                    <label for="name" class="text-sm font-medium">Name *</label>
                    <input
                        id="name"
                        v-model="form.name"
                        type="text"
                        class="w-full rounded-md border bg-background px-3 py-2 text-sm outline-none focus:ring-2 focus:ring-ring"
                        :class="{ 'border-destructive': form.errors.name }"
                    />
                    <p v-if="form.errors.name" class="text-xs text-destructive">{{ form.errors.name }}</p>
                </div>
                <div class="space-y-2">
                    <label for="description" class="text-sm font-medium">Description</label>
                    <textarea
                        id="description"
                        v-model="form.description"
                        rows="3"
                        class="w-full resize-none rounded-md border bg-background px-3 py-2 text-sm outline-none focus:ring-2 focus:ring-ring"
                        :class="{ 'border-destructive': form.errors.description }"
                    />
                    <p v-if="form.errors.description" class="text-xs text-destructive">{{ form.errors.description }}</p>
                </div>
                <div class="grid gap-4 sm:grid-cols-2">
                    <div class="space-y-2">
                        <label for="min_value" class="text-sm font-medium">Min Value</label>
                        <div class="relative">
                            <input
                                id="min_value"
                                v-model.number="form.min_value"
                                type="number"
                                min="0"
                                placeholder="Ex: 20"
                                class="w-full rounded-md border bg-background px-3 py-2 pr-10 text-sm outline-none focus:ring-2 focus:ring-ring"
                                :class="{ 'border-destructive': form.errors.min_value }"
                            />
                            <span class="pointer-events-none absolute inset-y-0 right-2 flex items-center text-xs text-muted-foreground">R$</span>
                        </div>
                        <p v-if="form.errors.min_value" class="text-xs text-destructive">{{ form.errors.min_value }}</p>
                    </div>
                    <div class="space-y-2">
                        <label for="max_value" class="text-sm font-medium">Max Value</label>
                        <div class="relative">
                            <input
                                id="max_value"
                                v-model.number="form.max_value"
                                type="number"
                                min="0"
                                placeholder="Ex: 100"
                                class="w-full rounded-md border bg-background px-3 py-2 pr-10 text-sm outline-none focus:ring-2 focus:ring-ring"
                                :class="{ 'border-destructive': form.errors.max_value }"
                            />
                            <span class="pointer-events-none absolute inset-y-0 right-2 flex items-center text-xs text-muted-foreground">R$</span>
                        </div>
                        <p v-if="form.errors.max_value" class="text-xs text-destructive">{{ form.errors.max_value }}</p>
                    </div>
                </div>
                <div class="space-y-2">
                    <label class="text-sm font-medium">Draw Date *</label>
                    <Popover>
                        <PopoverTrigger as-child>
                            <button
                                type="button"
                                :class="
                                    cn(
                                        'flex w-full items-center justify-start gap-2 rounded-md border bg-background px-3 py-2 text-left text-sm font-normal ring-offset-background transition-colors hover:bg-accent focus:outline-none focus:ring-2 focus:ring-ring disabled:cursor-not-allowed disabled:opacity-50',
                                        !dateValue && 'text-muted-foreground',
                                    )
                                "
                            >
                                <CalendarIcon class="h-4 w-4" />
                                <span class="truncate">
                                    {{ dateValue ? df.format(dateValue.toDate(tz)) : 'Pick a date' }}
                                </span>
                            </button>
                        </PopoverTrigger>
                        <PopoverContent class="w-auto p-0" align="start">
                            <Calendar
                                :model-value="dateValue as any"
                                @update:model-value="onCalendarUpdate"
                                initial-focus
                                class="rounded-md border"
                            />
                        </PopoverContent>
                    </Popover>
                    <p v-if="form.errors.draw_at" class="text-xs text-destructive">{{ form.errors.draw_at }}</p>
                </div>
                <div class="flex items-center gap-3">
                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="inline-flex items-center rounded-md bg-primary px-4 py-2 text-sm font-medium text-primary-foreground shadow hover:bg-primary/90 disabled:opacity-50"
                    >
                        Save
                    </button>
                </div>
            </form>
        </div>
    </AppLayout>
</template>

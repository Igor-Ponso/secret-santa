<script lang="ts" setup>
import { cn } from "@/lib/utils"
import type { DateValue } from '@internationalized/date'
import { reactiveOmit } from "@vueuse/core"
import type { CalendarRootEmits, CalendarRootProps } from "reka-ui"
import { CalendarRoot, useForwardPropsEmits } from "reka-ui"
import type { HTMLAttributes } from "vue"
import { computed } from 'vue'
import { CalendarCell, CalendarCellTrigger, CalendarGrid, CalendarGridBody, CalendarGridHead, CalendarGridRow, CalendarHeadCell, CalendarHeader, CalendarHeading, CalendarNextButton, CalendarPrevButton } from "."

const props = defineProps<CalendarRootProps & { class?: HTMLAttributes["class"], min?: Date | string | DateValue }>()
function normalizeMin(d?: Date | string | DateValue) {
  if (!d) return null;
  if (typeof d === 'string') {
    const parsed = new Date(d);
    if (isNaN(parsed.getTime())) return null;
    return parsed;
  }
  if (d instanceof Date) return d;
  // Handle DateValue (from @internationalized/date)
  if (typeof d === 'object' && 'toDate' in d && typeof (d as any).toDate === 'function') {
    try {
      return (d as any).toDate();
    } catch (_) {
      return null;
    }
  }
  return null;
}
const minDate = computed(() => normalizeMin(props.min));

function isDisabledDay(weekDate: any): boolean {
  if (!minDate.value) return false;
  try {
    const d = weekDate.toDate();
    d.setHours(0,0,0,0);
    const cmp = new Date(minDate.value.getFullYear(), minDate.value.getMonth(), minDate.value.getDate());
    cmp.setHours(0,0,0,0);
    return d.getTime() < cmp.getTime();
  } catch (e) {
    return false;
  }
}

const emits = defineEmits<CalendarRootEmits>()

const delegatedProps = reactiveOmit(props, "class")

const forwarded = useForwardPropsEmits(delegatedProps, emits)
</script>

<template>
  <CalendarRoot
    v-slot="{ grid, weekDays }"
    :class="cn('p-3', props.class)"
    v-bind="forwarded"
  >
    <CalendarHeader>
      <CalendarPrevButton />
      <CalendarHeading />
      <CalendarNextButton />
    </CalendarHeader>

    <div class="flex flex-col gap-y-4 mt-4 sm:flex-row sm:gap-x-4 sm:gap-y-0">
      <CalendarGrid v-for="month in grid" :key="month.value.toString()">
        <CalendarGridHead>
          <CalendarGridRow>
            <CalendarHeadCell
              v-for="day in weekDays" :key="day"
            >
              {{ day }}
            </CalendarHeadCell>
          </CalendarGridRow>
        </CalendarGridHead>
        <CalendarGridBody>
          <CalendarGridRow v-for="(weekDates, index) in month.rows" :key="`weekDate-${index}`" class="mt-2 w-full">
            <CalendarCell
              v-for="weekDate in weekDates"
              :key="weekDate.toString()"
              :date="weekDate"
            >
              <CalendarCellTrigger
                :day="weekDate"
                :month="month.value"
                :data-disabled="isDisabledDay(weekDate) ? '' : undefined"
                :aria-disabled="isDisabledDay(weekDate) ? 'true' : undefined"
                :tabindex="isDisabledDay(weekDate) ? -1 : 0"
                :class="isDisabledDay(weekDate) ? 'pointer-events-none select-none' : ''"
                @click.stop.prevent="isDisabledDay(weekDate) ? undefined : null"
              />
            </CalendarCell>
          </CalendarGridRow>
        </CalendarGridBody>
      </CalendarGrid>
    </div>
  </CalendarRoot>
</template>

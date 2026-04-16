<script setup lang="ts">
import { type HTMLAttributes, computed } from 'vue'
import { 
  RangeCalendarRoot, 
  type RangeCalendarRootEmits, 
  type RangeCalendarRootProps, 
  useForwardPropsEmits,
  RangeCalendarHeader,
  RangeCalendarNext,
  RangeCalendarPrev,
  RangeCalendarHeading,
  RangeCalendarGrid,
  RangeCalendarGridHead,
  RangeCalendarGridRow,
  RangeCalendarHeadCell,
  RangeCalendarGridBody,
  RangeCalendarCell,
  RangeCalendarCellTrigger
} from 'reka-ui'
import { ChevronLeft, ChevronRight } from 'lucide-vue-next'
import { buttonVariants } from '@/components/ui/button'
import { cn } from '@/lib/utils'

const props = defineProps<RangeCalendarRootProps & { class?: HTMLAttributes['class'] }>()
const emits = defineEmits<RangeCalendarRootEmits>()

const forwarded = useForwardPropsEmits(props, emits)
</script>

<template>
  <RangeCalendarRoot
    v-slot="{ grid, weekDays }"
    :class="cn('p-4 flex flex-col items-center', props.class)"
    v-bind="forwarded"
  >
    <RangeCalendarHeader class="relative flex w-full items-center justify-between pt-1">
      <RangeCalendarPrev
        :class="cn(
          buttonVariants({ variant: 'outline' }),
          'h-7 w-7 bg-transparent p-0 opacity-50 hover:opacity-100',
        )"
      >
        <ChevronLeft class="h-4 w-4" />
      </RangeCalendarPrev>
      <RangeCalendarHeading class="text-sm font-medium" />
      <RangeCalendarNext
        :class="cn(
          buttonVariants({ variant: 'outline' }),
          'h-7 w-7 bg-transparent p-0 opacity-50 hover:opacity-100',
        )"
      >
        <ChevronRight class="h-4 w-4" />
      </RangeCalendarNext>
    </RangeCalendarHeader>

    <div class="flex flex-col gap-y-4 mt-4 sm:flex-row sm:gap-x-12 sm:gap-y-0 justify-center">
      <RangeCalendarGrid v-for="month in grid" :key="month.value.toString()" class="border-collapse space-y-1">
        <RangeCalendarGridHead>
          <RangeCalendarGridRow class="flex w-full mt-2">
            <RangeCalendarHeadCell
              v-for="day in weekDays" :key="day"
              class="text-muted-foreground rounded-md w-9 font-normal text-[0.8rem]"
            >
              {{ day }}
            </RangeCalendarHeadCell>
          </RangeCalendarGridRow>
        </RangeCalendarGridHead>
        <RangeCalendarGridBody>
    <RangeCalendarGridRow v-for="(weekDates, weekIndex) in month.rows" :key="`weekDate-${weekIndex}`" class="flex w-full mt-1">
            <template v-for="(date, dateIndex) in weekDates">
              <RangeCalendarCell
                v-if="date"
                :key="date.toString()"
                :date="date"
                class="h-9 w-9 text-center text-sm p-0 relative [&:has([data-selected])]:bg-accent [&:has([data-selected][data-outside-view])]:bg-accent/50 first:[&:has([data-selected])]:rounded-l-md last:[&:has([data-selected])]:rounded-r-md focus-within:relative focus-within:z-20"
              >
                <RangeCalendarCellTrigger
                  :day="date"
                  :month="month.value"
                  :class="cn(
                    buttonVariants({ variant: 'ghost' }),
                    'h-9 w-9 p-0 font-normal aria-selected:opacity-100',
                    '[&[data-today]:not([data-selected])]:bg-accent [&[data-today]:not([data-selected])]:text-accent-foreground',
                    // Selected
                    'data-[selected]:bg-primary data-[selected]:text-primary-foreground data-[selected]:opacity-100 data-[selected]:hover:bg-primary data-[selected]:hover:text-primary-foreground data-[selected]:focus:bg-primary data-[selected]:focus:text-primary-foreground',
                    // Outside month
                    'data-[outside-view]:text-muted-foreground data-[outside-view]:opacity-50 data-[outside-view]:aria-selected:bg-accent/50 data-[outside-view]:aria-selected:text-muted-foreground data-[outside-view]:aria-selected:opacity-30',
                    // Disabled
                    'data-[disabled]:text-muted-foreground data-[disabled]:opacity-50',
                    // Unavailable
                    'data-[unavailable]:text-destructive-foreground data-[unavailable]:line-through',
                  )"
                />
              </RangeCalendarCell>
              <div v-else :key="`empty-${weekIndex}-${dateIndex}`" class="h-9 w-9" />
            </template>
          </RangeCalendarGridRow>
        </RangeCalendarGridBody>
      </RangeCalendarGrid>
    </div>
  </RangeCalendarRoot>
</template>

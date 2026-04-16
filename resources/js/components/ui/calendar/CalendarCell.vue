<script setup lang="ts">
import { CalendarCell, type CalendarCellProps, useForwardProps } from 'reka-ui'
import { computed, type HTMLAttributes } from 'vue'
import { cn } from '@/lib/utils'

const props = defineProps<CalendarCellProps & { class?: HTMLAttributes['class'] }>()

const forwardedProps = useForwardProps(props)
</script>

<template>
  <CalendarCell
    :class="cn('h-9 w-9 text-center text-sm p-0 relative [&:has([data-selected])]:bg-accent [&:has([data-selected][data-outside-view])]:bg-accent/50 first:[&:has([data-selected])]:rounded-l-md last:[&:has([data-selected])]:rounded-r-md focus-within:relative focus-within:z-20', props.class)"
    v-bind="forwardedProps"
  >
    <slot />
  </CalendarCell>
</template>

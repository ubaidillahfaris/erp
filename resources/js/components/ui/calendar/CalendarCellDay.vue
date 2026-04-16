<script setup lang="ts">
import { CalendarCellTrigger, type CalendarCellTriggerProps, useForwardProps } from 'reka-ui'
import { computed, type HTMLAttributes } from 'vue'
import { cn } from '@/lib/utils'
import { buttonVariants } from '../button'

const props = defineProps<CalendarCellTriggerProps & { class?: HTMLAttributes['class'] }>()

const forwardedProps = useForwardProps(props)
</script>

<template>
  <CalendarCellTrigger
    v-bind="forwardedProps"
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
      props.class,
    )"
  />
</template>

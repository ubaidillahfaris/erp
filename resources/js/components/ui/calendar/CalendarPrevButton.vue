<script setup lang="ts">
import { CalendarPrev, type CalendarPrevProps, useForwardProps } from 'reka-ui'
import { computed, type HTMLAttributes } from 'vue'
import { ChevronLeft } from 'lucide-vue-next'
import { cn } from '@/lib/utils'
import { buttonVariants } from '../button'

const props = defineProps<CalendarPrevProps & { class?: HTMLAttributes['class'] }>()

const forwardedProps = useForwardProps(props)
</script>

<template>
  <CalendarPrev
    v-bind="forwardedProps"
    :class="cn(
      buttonVariants({ variant: 'outline' }),
      'h-7 w-7 bg-transparent p-0 opacity-50 hover:opacity-100',
      props.class,
    )"
  >
    <slot>
      <ChevronLeft class="h-4 w-4" />
    </slot>
  </CalendarPrev>
</template>

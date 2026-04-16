<script setup lang="ts">
import { CalendarNext, type CalendarNextProps, useForwardProps } from 'reka-ui'
import { computed, type HTMLAttributes } from 'vue'
import { ChevronRight } from 'lucide-vue-next'
import { cn } from '@/lib/utils'
import { buttonVariants } from '../button'

const props = defineProps<CalendarNextProps & { class?: HTMLAttributes['class'] }>()

const forwardedProps = useForwardProps(props)
</script>

<template>
  <CalendarNext
    v-bind="forwardedProps"
    :class="cn(
      buttonVariants({ variant: 'outline' }),
      'h-7 w-7 bg-transparent p-0 opacity-50 hover:opacity-100',
      props.class,
    )"
  >
    <slot>
      <ChevronRight class="h-4 w-4" />
    </slot>
  </CalendarNext>
</template>

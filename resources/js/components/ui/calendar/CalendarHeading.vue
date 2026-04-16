<script setup lang="ts">
import { CalendarHeading, type CalendarHeadingProps, useForwardProps } from 'reka-ui'
import { computed, type HTMLAttributes } from 'vue'
import { cn } from '@/lib/utils'

const props = defineProps<CalendarHeadingProps & { class?: HTMLAttributes['class'] }>()

const forwardedProps = useForwardProps(props)
</script>

<template>
  <CalendarHeading
    v-slot="{ headingValue }"
    :class="cn('text-sm font-medium text-slate-900', props.class)"
    v-bind="forwardedProps"
  >
    <slot :heading-value="headingValue">
      {{ headingValue }}
    </slot>
  </CalendarHeading>
</template>

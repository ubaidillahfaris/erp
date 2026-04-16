<script setup lang="ts">
import { CalendarGridBody, type CalendarGridBodyProps } from 'reka-ui'
import { computed, type HTMLAttributes } from 'vue'
import { cn } from '@/lib/utils'

const props = defineProps<CalendarGridBodyProps & { class?: HTMLAttributes['class'] }>()
</script>

<template>
  <CalendarGridBody :class="cn('', props.class)">
    <slot />
  </CalendarGridBody>
</template>

<script setup lang="ts">
import { CalendarGridRow, type CalendarGridRowProps } from 'reka-ui'
import { computed, type HTMLAttributes } from 'vue'
import { cn } from '@/lib/utils'

const props = defineProps<CalendarGridRowProps & { class?: HTMLAttributes['class'] }>()
</script>

<template>
  <CalendarGridRow :class="cn('flex w-full mt-2', props.class)">
    <slot />
  </CalendarGridRow>
</template>

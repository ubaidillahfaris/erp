<script setup lang="ts">
import { CalendarGridHead, type CalendarGridHeadProps } from 'reka-ui'
import { computed, type HTMLAttributes } from 'vue'
import { cn } from '@/lib/utils'

const props = defineProps<CalendarGridHeadProps & { class?: HTMLAttributes['class'] }>()
</script>

<template>
  <CalendarGridHead :class="cn('', props.class)">
    <slot />
  </CalendarGridHead>
</template>

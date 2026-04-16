<script setup lang="ts">
import { CalendarHeadCell, type CalendarHeadCellProps } from 'reka-ui'
import { computed, type HTMLAttributes } from 'vue'
import { cn } from '@/lib/utils'

const props = defineProps<CalendarHeadCellProps & { class?: HTMLAttributes['class'] }>()
</script>

<template>
  <CalendarHeadCell :class="cn('text-muted-foreground rounded-md w-9 font-normal text-[0.8rem]', props.class)">
    <slot />
  </CalendarHeadCell>
</template>

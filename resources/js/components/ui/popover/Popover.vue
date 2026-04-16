<script setup lang="ts">
import { PopoverContent, type PopoverContentProps, PopoverPortal, PopoverRoot, PopoverTrigger, useForwardPropsEmits } from 'reka-ui'
import { computed, type HTMLAttributes } from 'vue'
import { cn } from '@/lib/utils'

interface Props extends PopoverContentProps {
  class?: HTMLAttributes['class']
}

const props = withDefaults(defineProps<Props>(), {
  align: 'center',
  sideOffset: 4,
})
const emits = defineEmits<{
  (e: 'update:open', value: boolean): void
}>()

const forwarded = useForwardPropsEmits(props, emits)
</script>

<template>
  <PopoverRoot v-bind="forwarded">
    <slot />
  </PopoverRoot>
</template>

<script lang="ts">
export { PopoverTrigger, PopoverAnchor } from 'reka-ui'
</script>

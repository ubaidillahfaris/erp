<script setup lang="ts">
import { SelectLabel, type SelectLabelProps } from "reka-ui"
import { cn } from "@/lib/utils"
import { type HTMLAttributes, computed } from "vue"

const props = defineProps<SelectLabelProps & { class?: HTMLAttributes["class"] }>()

const delegatedProps = computed(() => {
  const { class: _, ...delegated } = props

  return delegated
})
</script>

<template>
  <SelectLabel :class="cn('py-1.5 pl-8 pr-2 text-sm font-semibold text-muted-foreground', props.class)" v-bind="delegatedProps">
    <slot />
  </SelectLabel>
</template>

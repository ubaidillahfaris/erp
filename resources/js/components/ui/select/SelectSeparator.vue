<script setup lang="ts">
import { SelectSeparator, type SelectSeparatorProps } from "reka-ui"
import { cn } from "@/lib/utils"
import { type HTMLAttributes, computed } from "vue"

const props = defineProps<SelectSeparatorProps & { class?: HTMLAttributes["class"] }>()

const delegatedProps = computed(() => {
  const { class: _, ...delegated } = props

  return delegated
})
</script>

<template>
  <SelectSeparator :class="cn('-mx-1 my-1 h-px bg-muted', props.class)" v-bind="delegatedProps" />
</template>

<script setup lang="ts">
import { computed, type HTMLAttributes } from "vue"
import { Primitive, type PrimitiveProps } from "reka-ui"
import { cn } from "@/lib/utils"
import { type ButtonVariants, buttonVariants } from "."

interface Props extends PrimitiveProps {
  variant?: ButtonVariants["variant"]
  size?: ButtonVariants["size"]
  class?: HTMLAttributes["class"]
  // Boolean triggers for variants
  primary?: boolean
  secondary?: boolean
  accent?: boolean
  outline?: boolean
  ghost?: boolean
  link?: boolean
}

const props = withDefaults(defineProps<Props>(), {
  as: "button",
})

const effectiveVariant = computed(() => {
  if (props.primary) return "default"
  if (props.secondary) return "secondary"
  if (props.accent) return "accent"
  if (props.outline) return "outline"
  if (props.ghost) return "ghost"
  if (props.link) return "link"
  return props.variant
})
</script>

<template>
<Primitive data-slot="button" :as="as" :as-child="asChild"
  :class="cn(buttonVariants({ variant: effectiveVariant, size }), props.class)" :data-primary="primary"
  :data-secondary="secondary" :data-accent="accent" :data-outline="outline" :data-ghost="ghost" :data-link="link">
  <slot />
</Primitive>
</template>

<script setup lang="ts">
import { Store } from 'lucide-vue-next';
import type { HTMLAttributes } from 'vue';

defineOptions({
    inheritAttrs: false,
});

type Props = {
    className?: HTMLAttributes['class'];
};

defineProps<Props>();
</script>

<template>
    <div :class="className" v-bind="$attrs" class="flex items-center justify-center">
        <Store class="size-full fill-current" />
    </div>
</template>

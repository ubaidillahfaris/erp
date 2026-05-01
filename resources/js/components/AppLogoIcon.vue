<script setup lang="ts">
import { usePage } from '@inertiajs/vue3';
import { computed, type HTMLAttributes } from 'vue';

defineOptions({
    inheritAttrs: false,
});

type Props = {
    className?: HTMLAttributes['class'];
};

defineProps<Props>();

const page = usePage();
const logoLetter = computed(() => {
    const name = (page.props.name as string) || 'Valee';
    return name.charAt(0).toUpperCase();
});
</script>

<template>
    <div :class="className" v-bind="$attrs" class="flex items-center justify-center font-black select-none">
        <span class="leading-none text-[1.2em]">
            {{ logoLetter }}
        </span>
    </div>
</template>

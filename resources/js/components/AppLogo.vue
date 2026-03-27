<script setup lang="ts">
import AppLogoIcon from '@/components/AppLogoIcon.vue';

withDefaults(defineProps<{
    isIconOnly?: boolean;
}>(), {
    isIconOnly: false,
});
</script>

<template>
    <div
        class="flex aspect-square size-8 items-center justify-center rounded-md bg-sidebar-primary text-sidebar-primary-foreground"
        :class="{ 'size-full rounded-none bg-transparent text-current': isIconOnly }"
    >
        <AppLogoIcon 
            class="fill-current" 
            :class="isIconOnly ? 'size-full' : 'size-5 text-white dark:text-black'" 
        />
    </div>
    <div v-if="!isIconOnly" class="ml-1 grid flex-1 text-left text-sm">
        <span class="mb-0.5 truncate leading-tight font-semibold">Martous</span>
    </div>
</template>

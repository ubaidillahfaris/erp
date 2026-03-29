<script setup lang="ts">
import AppLayout from '@/layouts/app/AppSidebarLayout.vue';
import GlobalConfirmDialog from '@/components/GlobalConfirmDialog.vue';
import type { BreadcrumbItem } from '@/types';

type Props = {
    breadcrumbs?: BreadcrumbItem[];
};

withDefaults(defineProps<Props>(), {
    breadcrumbs: () => [],
});
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <slot />
    </AppLayout>
    <GlobalConfirmDialog />
</template>

<script setup lang="ts">
interface Tab {
    readonly value: string;
    readonly label: string;
    readonly count?: number | string;
}

defineProps<{
    tabs: readonly Tab[];
    activeTab?: string;
}>();

const emit = defineEmits<{
    (e: 'update:activeTab', value: string): void;
}>();
</script>

<template>
    <div v-if="tabs && tabs.length > 0" class="px-8 flex items-center h-14 bg-white border-b">
        <div class="inline-flex h-10 items-center justify-center rounded-md bg-muted p-1 text-muted-foreground">
            <button 
                v-for="tab in tabs" 
                :key="tab.value" 
                @click="emit('update:activeTab', tab.value)" 
                type="button" 
                :class="[
                    'inline-flex items-center justify-center whitespace-nowrap rounded-sm px-3 py-1.5 text-sm font-medium ring-offset-background transition-all focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50',
                    activeTab === tab.value
                        ? 'bg-background text-foreground shadow-sm'
                        : 'hover:bg-background/50 hover:text-foreground'
                ]"
            >
                {{ tab.label }}
                <span v-if="tab.count !== undefined" class="ml-1.5 rounded-full bg-muted-foreground/10 px-1.5 py-0.5 text-[10px] font-bold">
                    {{ tab.count }}
                </span>
            </button>
        </div>
    </div>
</template>

<style scoped>
/* Standard Shadcn Tabs style doesn't need custom scrollbars for short lists, 
   but we'll keep it just in case of overflow on mobile. */
.no-scrollbar::-webkit-scrollbar {
    display: none;
}

.no-scrollbar {
    -ms-overflow-style: none;
    scrollbar-width: none;
}
</style>

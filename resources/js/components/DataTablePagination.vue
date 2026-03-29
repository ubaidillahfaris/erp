<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import { Select, SelectContent, SelectGroup, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';

const props = defineProps<{
    paginator: {
        links: any[];
        current_page: number;
        last_page: number;
        per_page: number;
        total: number;
    };
    perPageOptions?: number[];
}>();

const emit = defineEmits<{
    (e: 'update:perPage', value: string): void
}>();

const defaultOptions = [10, 25, 50, 100];
const options = props.perPageOptions || defaultOptions;

const onPerPageChange = (value: any) => {
    emit('update:perPage', String(value));
};
</script>

<template>
<div v-if="paginator.total > 0" class="flex flex-col sm:flex-row items-center justify-between gap-6">
    <div class="flex items-center gap-6 text-xs text-muted-foreground w-full sm:w-auto">
        <div class="flex flex-col">
            <span class="text-[10px] font-black uppercase tracking-widest text-muted-foreground/30 mb-0.5">Page Progress</span>
            <span class="font-black text-foreground">
                {{ paginator.current_page }} <span class="text-muted-foreground/30 mx-1">/</span> {{ paginator.last_page }}
            </span>
        </div>
        
        <div class="h-8 w-px bg-border/40 mx-2" />

        <div class="flex flex-col gap-1.5">
            <span class="text-[10px] font-black uppercase tracking-widest text-muted-foreground/30">Showing</span>
            <Select :model-value="String(paginator.per_page)" @update:model-value="onPerPageChange">
                <SelectTrigger class="h-9 w-[70px] rounded-xl border-border/40 bg-muted/20 text-xs font-black shadow-none ring-0 focus:ring-0">
                    <SelectValue :placeholder="String(paginator.per_page)" />
                </SelectTrigger>
                <SelectContent class="rounded-xl">
                    <SelectGroup>
                        <SelectItem v-for="opt in options" :key="opt" :value="String(opt)" class="rounded-lg">
                            {{ opt }}
                        </SelectItem>
                    </SelectGroup>
                </SelectContent>
            </Select>
        </div>
        
        <div class="ml-auto sm:ml-4 flex flex-col items-end">
            <span class="text-[10px] font-black uppercase tracking-widest text-muted-foreground/30 mb-0.5">Total Records</span>
            <span class="font-black text-foreground tabular-nums">{{ paginator.total }}</span>
        </div>
    </div>

    <div v-if="paginator.last_page > 1" class="flex items-center justify-center gap-1.5 bg-muted/30 p-1.5 rounded-2xl border border-border/20">
        <template v-for="(link, index) in paginator.links" :key="index">
            <Button v-if="link.url" 
                :variant="link.active ? 'default' : 'ghost'" 
                size="sm" 
                as-child
                :class="[
                    'h-9 min-w-9 rounded-xl text-xs font-black transition-all duration-300',
                    link.active 
                        ? 'bg-accent text-white shadow-lg shadow-accent/25 hover:bg-accent/90' 
                        : 'text-muted-foreground/50 hover:bg-white hover:text-accent hover:shadow-sm'
                ]"
            >
                <Link :href="link.url" preserve-scroll preserve-state>
                    <span v-html="link.label" />
                </Link>
            </Button>
            <Button v-else variant="ghost" size="sm" disabled class="h-9 min-w-9 rounded-xl text-xs text-muted-foreground/20 italic">
                <span v-html="link.label" />
            </Button>
        </template>
    </div>
</div>
</template>

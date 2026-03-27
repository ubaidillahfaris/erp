<script setup lang="ts">
import { Link, router } from '@inertiajs/vue3';
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
<div v-if="paginator.total > 0" class="flex flex-col sm:flex-row items-center justify-between gap-4 py-4 px-2">
    <div class="flex items-center gap-4 text-sm text-muted-foreground w-full sm:w-auto">
        <span class="whitespace-nowrap">
            Halaman {{ paginator.current_page }} dari {{ paginator.last_page }}
            <span class="hidden sm:inline">(Total: {{ paginator.total }} data)</span>
        </span>
        <div class="flex items-center gap-2">
            <span class="hidden sm:inline">Tampilkan</span>
            <Select :model-value="String(paginator.per_page)" @update:model-value="onPerPageChange">
                <SelectTrigger class="h-8 w-[70px]">
                    <SelectValue :placeholder="String(paginator.per_page)" />
                </SelectTrigger>
                <SelectContent>
                    <SelectGroup>
                        <SelectItem v-for="opt in options" :key="opt" :value="String(opt)">
                            {{ opt }}
                        </SelectItem>
                    </SelectGroup>
                </SelectContent>
            </Select>
        </div>
    </div>

    <div v-if="paginator.last_page > 1" class="flex gap-1 w-full sm:w-auto justify-end">
        <template v-for="(link, index) in paginator.links" :key="index">
            <Button v-if="link.url" :variant="link.active ? 'default' : 'outline'" size="sm" as-child>
                <Link :href="link.url" preserve-scroll preserve-state>
                    <span v-html="link.label"></span>
                </Link>
            </Button>
            <Button v-else variant="outline" size="sm" disabled>
                <span v-html="link.label"></span>
            </Button>
        </template>
    </div>
</div>
</template>

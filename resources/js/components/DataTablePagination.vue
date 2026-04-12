<script setup lang="ts">
import { computed } from 'vue';
import { router } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import { 
    Select, 
    SelectContent, 
    SelectGroup, 
    SelectItem, 
    SelectTrigger, 
    SelectValue 
} from '@/components/ui/select';
import { ChevronLeft, ChevronRight, ChevronsLeft, ChevronsRight } from 'lucide-vue-next';

const props = defineProps<{
    paginator: {
        links: any[];
        current_page: number;
        last_page: number;
        per_page: number;
        total: number;
    };
    perPageOptions?: number[];
    selectedCount?: number;
}>();

const emit = defineEmits<{
    (e: 'update:perPage', value: string): void;
}>();

const perPage = computed({
    get: () => String(props.paginator.per_page),
    set: (val) => emit('update:perPage', val),
});

const currentPage = computed({
    get: () => props.paginator.current_page,
    set: (page) => {
        const link = props.paginator.links.find(l => l.label === String(page));
        if (link && link.url) {
            router.get(link.url, {}, { preserveState: true, preserveScroll: true });
        } else {
            const url = new URL(window.location.href);
            url.searchParams.set('page', String(page));
            router.get(url.toString(), {}, { preserveState: true, preserveScroll: true });
        }
    }
});

const options = props.perPageOptions || [10, 25, 50, 100];
</script>

<template>
<div v-if="paginator.total > 0" class="flex items-center justify-between px-4 py-3">
    <!-- Row Selection/Total Info -->
    <div class="flex-1 text-sm text-muted-foreground font-medium">
        <template v-if="selectedCount !== undefined && selectedCount > 0">
            {{ selectedCount }} of {{ paginator.total }} row(s) selected.
        </template>
        <template v-else>
            Total {{ paginator.total }} record(s).
        </template>
    </div>

    <!-- Navigation Bar -->
    <div class="flex items-center space-x-6 lg:space-x-8">
        <!-- Rows per page -->
        <div class="flex items-center space-x-2">
            <p class="text-sm font-medium">Rows per page</p>
            <Select v-model="perPage">
                <SelectTrigger class="h-8 w-[70px] border-input">
                    <SelectValue :placeholder="perPage" />
                </SelectTrigger>
                <SelectContent side="top" class="min-w-[70px]">
                    <SelectGroup>
                        <SelectItem v-for="opt in options" :key="opt" :value="String(opt)">
                            {{ opt }}
                        </SelectItem>
                    </SelectGroup>
                </SelectContent>
            </Select>
        </div>

        <!-- Page info -->
        <div class="flex w-[100px] items-center justify-center text-sm font-medium text-foreground/80">
            Page {{ paginator.current_page }} of {{ paginator.last_page }}
        </div>

        <!-- Navigation Buttons -->
        <div class="flex items-center space-x-2">
            <Button
                variant="outline"
                class="hidden h-8 w-8 p-0 lg:flex border-input"
                :disabled="paginator.current_page === 1"
                @click="currentPage = 1"
            >
                <span class="sr-only">Go to first page</span>
                <ChevronsLeft class="h-4 w-4" />
            </Button>
            <Button
                variant="outline"
                class="h-8 w-8 p-0 border-input"
                :disabled="paginator.current_page === 1"
                @click="currentPage = paginator.current_page - 1"
            >
                <span class="sr-only">Go to previous page</span>
                <ChevronLeft class="h-4 w-4" />
            </Button>
            <Button
                variant="outline"
                class="h-8 w-8 p-0 border-input"
                :disabled="paginator.current_page === paginator.last_page"
                @click="currentPage = paginator.current_page + 1"
            >
                <span class="sr-only">Go to next page</span>
                <ChevronRight class="h-4 w-4" />
            </Button>
            <Button
                variant="outline"
                class="hidden h-8 w-8 p-0 lg:flex border-input"
                :disabled="paginator.current_page === paginator.last_page"
                @click="currentPage = paginator.last_page"
            >
                <span class="sr-only">Go to last page</span>
                <ChevronsRight class="h-4 w-4" />
            </Button>
        </div>
    </div>
</div>
</template>

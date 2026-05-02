<script setup lang="ts">
import { Check, ChevronsUpDown, Plus, Loader2 } from 'lucide-vue-next';
import {
    ComboboxRoot,
    ComboboxInput,
    ComboboxTrigger,
    ComboboxContent,
    ComboboxViewport,
    ComboboxEmpty,
    ComboboxGroup,
    ComboboxItem,
    ComboboxItemIndicator,
    ComboboxAnchor,
    ComboboxPortal
} from 'reka-ui';
import { ref, computed, watch, onMounted } from 'vue';
import { useDebounceFn } from '@vueuse/core';
import { cn } from '@/lib/utils';

interface Option {
    [key: string]: any;
}

const props = defineProps<{
    options: Option[];
    modelValue: string | number | null | undefined;
    placeholder?: string;
    label?: string;
    error?: string;
    hideLabel?: boolean;
    hideError?: boolean;
    displayExpr?: string; // Property to display (default: 'name', supports nested paths like 'product.name')
    valueExpr?: string;   // Property for value (default: 'id')
    class?: string;
    loading?: boolean;
    disabled?: boolean;
}>();

const emit = defineEmits<{
    (e: 'update:modelValue', value: any): void;
    (e: 'create', value: string): void;
    (e: 'focus'): void;
    (e: 'search', value: string): void;
    (e: 'load-more'): void;
}>();

const open = ref(false);
const searchTerm = ref('');
const debouncedSearch = useDebounceFn((val: string) => {
    emit('search', val);
}, 300);

// Emit search event when searchTerm changes
watch(searchTerm, (val) => {
    debouncedSearch(val);
});



// Determinitive property keys from props
const displayKey = props.displayExpr || 'name';
const valueKey = props.valueExpr || 'id';

/**
 * Helper to resolve nested object paths like 'product.name'
 */
const resolvePath = (obj: any, path: string) => {
    if (!obj || !path) return '';
    return path.split('.').reduce((acc, part) => acc && acc[part], obj);
};

const handleScroll = (e: Event) => {
    const target = e.target as HTMLElement;
    // Check if we are near the bottom (within 20px)
    if (target.scrollHeight - target.scrollTop <= target.clientHeight + 20) {
        if (!props.loading) {
            emit('load-more');
        }
    }
};
// Finds the full object matching current modelValue (primitive)
const selectedOption = computed(() => {
    const val = props.modelValue;
    if (val === null || val === undefined || val === '') return null;
    return props.options.find((opt) => String(opt[valueKey]) === String(val));
});

// Syncs primitive value with parent
const internalValue = computed({
    get: () => props.modelValue,
    set: (val: any) => {
        emit('update:modelValue', val);
        searchTerm.value = '';
        open.value = false;
    },
});
// Custom filter that supports simbolo-based search if present
const internalFilter = (item: any, query: string) => {
    // If the parent is likely handling search (listening to search event), 
    // we might want to skip local filtering or keep it as a fallback.
    // For robustness, we'll keep a simple local filter if query is present.
    if (props.loading) return true;
    const term = query.toLowerCase().trim();
    if (!term) return true;

    const displayText = String(resolvePath(item, displayKey) || '').toLowerCase();
    const simbolText = String(item.symbol || '').toLowerCase();
    const skuText = String(item.sku || '').toLowerCase();

    return displayText.includes(term) || simbolText.includes(term) || skuText.includes(term);
};

// Returns the text to display in the input field
// Di dalam <script setup>
const getDisplayValue = (v: any): string => {
    const val = v ?? props.modelValue;
    if (val === null || val === undefined || val === '') return '';

    const opt = props.options.find((opt) => String(opt[valueKey]) === String(val));
    if (!opt) return String(val); // Balikkan nilai mentah jika label tidak ketemu

    let text = resolvePath(opt, displayKey);
    if (!text && opt.sku) text = opt.sku;
    if (!text) text = `ID: ${opt[valueKey]}`;

    const suffix = opt.symbol ? ` (${opt.symbol})` : '';
    return `${text}${suffix}`;
};

// Manually filter options to have full control over the UI states
const filteredOptions = computed(() => {
    if (!searchTerm.value) return props.options;
    return props.options.filter(opt => internalFilter(opt, searchTerm.value));
});

// Check if the current search term matches any existing item exactly
const hasExactMatch = computed(() => {
    const term = searchTerm.value.trim().toLowerCase();
    if (!term) return true;
    return props.options.some(opt => {
        const displayText = String(resolvePath(opt, displayKey) || '').toLowerCase();
        return displayText === term;
    });
});

const handleCreate = () => {
    emit('create', searchTerm.value);
    searchTerm.value = '';
    open.value = false;
};

const shouldShowCreateButton = computed(() => {
    const term = searchTerm.value.trim();
    if (!term || props.loading) return false;

    // Check if there's an exact match in the current options
    const hasExactMatch = props.options.some(opt => {
        const displayText = String(resolvePath(opt, displayKey) || '').toLowerCase();
        return displayText === term.toLowerCase();
    });

    return !hasExactMatch;
});

const handleOpenChange = (isOpen: boolean) => {
    if (isOpen) {
        emit('focus');
        // Trigger initial search if empty or needed
        emit('search', searchTerm.value);
    }
};
</script>

<template>
<div :class="cn('flex flex-col gap-2', props.class)">
    <label v-if="label && !hideLabel" class="text-sm font-medium leading-none">
        {{ label }}
    </label>

    <ComboboxRoot v-model="internalValue" v-model:open="open" :filter-function="() => true"
        @update:open="handleOpenChange" class="w-full">
        <ComboboxAnchor class="relative w-full">
            <ComboboxInput v-model="searchTerm"
                class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background file:border-0 file:bg-transparent file:text-sm font-medium placeholder:text-muted-foreground placeholder:font-normal focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50"
                :placeholder="placeholder" @focus="() => { open = true; }" :display-value="(getDisplayValue as any)"
                :disabled="disabled" />
            <div class="absolute right-0 top-0 h-full flex items-center gap-1 px-3">
                <Loader2 v-if="loading" class="h-4 w-4 animate-spin text-muted-foreground" />
                <ComboboxTrigger :disabled="disabled">
                    <ChevronsUpDown class="h-4 w-4 shrink-0 opacity-50" />
                </ComboboxTrigger>
            </div>
        </ComboboxAnchor>

        <ComboboxContent
            class="z-[100] w-md overflow-hidden rounded-md border bg-popover text-popover-foreground shadow-xl data-[state=open]:animate-in data-[state=closed]:animate-out data-[state=closed]:fade-out-0 data-[state=open]:fade-in-0 data-[state=closed]:zoom-out-95 data-[state=open]:zoom-in-95 data-[side=bottom]:slide-in-from-top-2 data-[side=left]:slide-in-from-right-2 data-[side=right]:slide-in-from-left-2 data-[side=top]:slide-in-from-bottom-2"
            position="popper" :side-offset="5">
            <ComboboxViewport class="p-1 max-h-60 overflow-y-auto" @scroll="handleScroll">

                <!-- Display filtered options -->
                <ComboboxItem v-for="option in filteredOptions" :key="String(option[valueKey])"
                    :value="option[valueKey]"
                    class="relative flex w-full cursor-default select-none items-center rounded-sm py-1.5 pl-8 pr-2 text-sm outline-none focus:bg-accent focus:text-accent-foreground data-[disabled]:pointer-events-none data-[disabled]:opacity-50">
                    <ComboboxItemIndicator class="absolute left-2 flex h-3.5 w-3.5 items-center justify-center">
                        <Check class="h-4 w-4" />
                    </ComboboxItemIndicator>
                    <span>
                        {{ resolvePath(option, displayKey) || option.sku || `ID: ${option[valueKey]}` }}
                        <template v-if="option.symbol"> ({{ option.symbol }})</template>
                    </span>
                </ComboboxItem>

                <!-- Show "Add New" as a ComboboxItem so it's not filtered out -->
                <ComboboxItem v-if="shouldShowCreateButton" value="----create-new-item----"
                    @select.prevent="handleCreate"
                    class="relative flex w-full cursor-pointer select-none items-center rounded-sm py-2 pl-8 pr-2 text-sm outline-none bg-accent/50 text-primary font-medium hover:bg-accent focus:bg-accent mt-1 border-t border-border/50">
                    <Plus class="absolute left-2 h-4 w-4" />
                    <span>Tambah "{{ searchTerm }}"</span>
                </ComboboxItem>

                <!-- Empty state when no items and no search term -->
                <div v-if="filteredOptions.length === 0 && !searchTerm && !loading"
                    class="py-6 text-center text-sm text-muted-foreground">
                    Belum ada data.
                </div>

                <!-- Loading state -->
                <div v-if="loading" class="py-6 text-center text-sm text-muted-foreground">
                    <Loader2 class="mx-auto h-4 w-4 animate-spin mb-2" />
                    Mencari data...
                </div>
            </ComboboxViewport>
        </ComboboxContent>
    </ComboboxRoot>

    <p v-if="error && !hideError" class="text-sm font-medium text-destructive">
        {{ error }}
    </p>
</div>
</template>

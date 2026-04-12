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
import { ref, computed, watch } from 'vue';
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
    displayExpr?: string; // Property to display (default: 'nama', supports nested paths like 'produk.nama')
    valueExpr?: string;   // Property for value (default: 'id')
    class?: string;
    loading?: boolean;
    disabled?: boolean;
}>();

const emit = defineEmits(['update:modelValue', 'create', 'search', 'load-more', 'focus']);

const open = ref(false);
const searchTerm = ref('');

// Determinitive property keys from props
const displayKey = props.displayExpr || 'nama';
const valueKey = props.valueExpr || 'id';

/**
 * Helper to resolve nested object paths like 'produk.nama'
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

// Emit search event when searchTerm changes
watch(searchTerm, (val) => {
    emit('search', val);
});

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
    if (props.loading) return true; // Don't filter locally while loading remotely
    const term = query.toLowerCase();
    const displayText = String(resolvePath(item, displayKey) || '').toLowerCase();
    const simbolText = String(item.simbol || '').toLowerCase();
    return displayText.includes(term) || simbolText.includes(term);
};

// Returns the text to display in the input field
const getDisplayValue = (v: any) => {
    const opt = selectedOption.value;
    if (!opt) return searchTerm.value || '';

    // Resolve display value, fallback to SKU if present and display value is empty
    let text = resolvePath(opt, displayKey);
    if (!text && opt.sku) text = opt.sku;
    if (!text) text = `ID: ${opt[valueKey]}`;

    const suffix = opt.simbol ? ` (${opt.simbol})` : '';
    return text + suffix;
};

const handleCreate = () => {
    emit('create', searchTerm.value);
    searchTerm.value = '';
    open.value = false;
};
</script>

<template>
<div :class="cn('flex flex-col gap-2', props.class)">
    <label v-if="label && !hideLabel" class="text-sm font-medium leading-none">
        {{ label }}
    </label>

    <ComboboxRoot v-model="internalValue" v-model:open="open" v-model:searchTerm="searchTerm"
        :filter-function="internalFilter">
        <ComboboxAnchor class="relative w-full">
            <ComboboxInput
                class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background file:border-0 file:bg-transparent file:text-sm font-medium placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50"
                :placeholder="placeholder" @focus="() => { open = true; emit('focus'); }"
                :display-value="getDisplayValue" :disabled="disabled" />
            <div class="absolute right-0 top-0 h-full flex items-center gap-1 px-3">
                <Loader2 v-if="loading" class="h-4 w-4 animate-spin text-muted-foreground" />
                <ComboboxTrigger :disabled="disabled">
                    <ChevronsUpDown class="h-4 w-4 shrink-0 opacity-50" />
                </ComboboxTrigger>
            </div>
        </ComboboxAnchor>

        <ComboboxPortal>
            <ComboboxContent
                class="z-50 w-[var(--reka-combobox-anchor-width)] overflow-hidden rounded-md border bg-popover text-popover-foreground shadow-none data-[state=open]:animate-in data-[state=closed]:animate-out data-[state=closed]:fade-out-0 data-[state=open]:fade-in-0 data-[state=closed]:zoom-out-95 data-[state=open]:zoom-in-95 data-[side=bottom]:slide-in-from-top-2 data-[side=left]:slide-in-from-right-2 data-[side=right]:slide-in-from-left-2 data-[side=top]:slide-in-from-bottom-2"
                position="popper" :side-offset="5">
                <ComboboxViewport class="p-1 max-h-60 overflow-y-auto" @scroll="handleScroll">
                    <ComboboxGroup v-if="props.options.length > 0">
                        <ComboboxItem v-for="option in props.options" :key="String(option[valueKey])"
                            :value="option[valueKey]"
                            class="relative flex w-full cursor-default select-none items-center rounded-sm py-1.5 pl-8 pr-2 text-sm outline-none focus:bg-accent focus:text-accent-foreground data-[disabled]:pointer-events-none data-[disabled]:opacity-50">
                            <ComboboxItemIndicator class="absolute left-2 flex h-3.5 w-3.5 items-center justify-center">
                                <Check class="h-4 w-4" />
                            </ComboboxItemIndicator>
                            <span>
                                {{ resolvePath(option, displayKey) || option.sku || `ID: ${option[valueKey]}` }}
                                <template v-if="option.simbol"> ({{ option.simbol }})</template>
                            </span>
                        </ComboboxItem>
                    </ComboboxGroup>

                    <ComboboxEmpty class="w-full">
                        <div v-if="loading" class="py-6 text-center text-sm text-muted-foreground">
                            <Loader2 class="mx-auto h-4 w-4 animate-spin mb-2" />
                            Mencari data...
                        </div>
                        <template v-else-if="searchTerm">
                            <button type="button"
                                class="flex w-full items-center gap-2 rounded-sm px-2 py-2 text-sm hover:bg-accent hover:text-accent-foreground text-left"
                                @click="handleCreate" @mousedown.prevent>
                                <Plus class="h-4 w-4" />
                                <span>Tambah "{{ searchTerm }}"</span>
                            </button>
                        </template>
                        <div v-else class="py-6 text-center text-sm text-muted-foreground">
                            Belum ada data.
                        </div>
                    </ComboboxEmpty>
                </ComboboxViewport>
            </ComboboxContent>
        </ComboboxPortal>
    </ComboboxRoot>

    <p v-if="error && !hideError" class="text-sm font-medium text-destructive">
        {{ error }}
    </p>
</div>
</template>

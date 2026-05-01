<script setup lang="ts">
import { ref, computed, watch } from 'vue';
import { Check, ChevronsUpDown, Search, Loader2 } from 'lucide-vue-next';
import {
    Popover,
    PopoverContent,
    PopoverTrigger
} from '@/components/ui/popover';
import { Button } from '@/components/ui/button';
import { cn } from '@/lib/utils';

interface Option {
    value: string | number;
    label: string;
}

interface Props {
    modelValue: string | number | null;
    options: Option[];
    placeholder?: string;
    searchPlaceholder?: string;
    disabled?: boolean;
    loading?: boolean;
    class?: string;
}

const props = withDefaults(defineProps<Props>(), {
    placeholder: 'Pilih...',
    searchPlaceholder: 'Cari...',
    loading: false,
});

const emit = defineEmits(['update:modelValue', 'search']);

const open = ref(false);
const searchTerm = ref('');

watch(searchTerm, (val) => {
    emit('search', val);
});

// Fix Bug 2: compare as string untuk handle number vs string mismatch
const selectedLabel = computed(() => {
    if (props.modelValue === null || props.modelValue === undefined || props.modelValue === '') {
        return null;
    }
    const option = props.options.find(
        opt => opt.value.toString() === props.modelValue?.toString()
    );
    return option ? option.label : null;
});

// Fix Bug 1: filter manual, tidak pakai ComboboxRoot filtering
const filteredOptions = computed(() => {
    // If we are doing server-side search, we might not want to filter locally 
    // but usually it's fine to do both.
    if (!searchTerm.value.trim()) return props.options;
    const term = searchTerm.value.toLowerCase();
    return props.options.filter(opt =>
        opt.label.toLowerCase().includes(term)
    );
});

const selectOption = (option: Option) => {
    emit('update:modelValue', option.value);
    open.value = false;
    searchTerm.value = '';
};

const clearSelection = () => {
    emit('update:modelValue', null);
};

// Reset search saat popover dibuka
const handleOpenChange = (val: boolean) => {
    open.value = val;
    if (val) {
        searchTerm.value = '';
        emit('search', '');
    }
};
</script>

<template>
<Popover :open="open" @update:open="handleOpenChange">
    <PopoverTrigger as-child>
        <Button variant="outline" role="combobox" :aria-expanded="open" :disabled="disabled" :class="cn(
            'w-full justify-between h-10 rounded-xl border-slate-200 shadow-none px-3 font-medium hover:bg-slate-50 transition-colors',
            !selectedLabel && 'text-muted-foreground font-normal',
            props.class
        )">
            <span class="truncate">{{ selectedLabel ?? placeholder }}</span>
            <ChevronsUpDown class="ml-2 h-4 w-4 shrink-0 opacity-50" />
        </Button>
    </PopoverTrigger>

    <PopoverContent class="w-[var(--reka-popover-trigger-width)] p-0 rounded-xl shadow-lg border border-slate-200"
        align="start" :side-offset="4">
        <!-- Search input -->
        <div class="flex items-center gap-2 border-b border-slate-100 px-3">
            <Loader2 v-if="loading" class="h-3.5 w-3.5 shrink-0 text-muted-foreground animate-spin" />
            <Search v-else class="h-3.5 w-3.5 shrink-0 text-muted-foreground" />
            <input v-model="searchTerm" :placeholder="searchPlaceholder"
                class="flex h-10 w-full bg-transparent py-3 text-sm outline-none placeholder:text-muted-foreground placeholder:font-normal"
                @keydown.enter.prevent @keydown.escape="open = false" />
        </div>

        <!-- Options list -->
        <div class="p-1 max-h-60 overflow-y-auto">
            <template v-if="filteredOptions.length > 0">
                <button v-for="option in filteredOptions" :key="option.value" type="button"
                    @click="selectOption(option)"
                    class="relative flex w-full cursor-pointer select-none items-center rounded-lg py-2 pl-8 pr-3 text-sm outline-none hover:bg-slate-50 focus:bg-slate-50 transition-colors text-left"
                    :class="{
                        'text-accent font-semibold': option.value.toString() === modelValue?.toString()
                    }">
                    <span v-if="option.value.toString() === modelValue?.toString()"
                        class="absolute left-2 flex h-3.5 w-3.5 items-center justify-center">
                        <Check class="h-3.5 w-3.5" />
                    </span>
                    <span class="truncate">{{ option.label }}</span>
                </button>
            </template>

            <div v-else class="py-6 text-center text-sm text-muted-foreground">
                Tidak ditemukan.
            </div>
        </div>
    </PopoverContent>
</Popover>
</template>
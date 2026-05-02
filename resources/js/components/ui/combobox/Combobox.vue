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
    group?: string; // NEW: Group identifier
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

// Compare as string to handle number vs string mismatch
const selectedLabel = computed(() => {
    if (props.modelValue === null || props.modelValue === undefined || props.modelValue === '') {
        return null;
    }
    const option = props.options.find(
        opt => opt.value.toString() === props.modelValue?.toString()
    );
    return option ? option.label : null;
});

// Grouped options for rendering
const groupedOptions = computed(() => {
    let options = props.options;
    
    if (searchTerm.value.trim()) {
        const term = searchTerm.value.toLowerCase();
        options = options.filter(opt =>
            opt.label.toLowerCase().includes(term) || 
            (opt.group && opt.group.toLowerCase().includes(term))
        );
    }

    // Grouping logic
    const groups: Record<string, Option[]> = {};
    const noGroup: Option[] = [];

    options.forEach(opt => {
        if (opt.group) {
            if (!groups[opt.group]) groups[opt.group] = [];
            groups[opt.group].push(opt);
        } else {
            noGroup.push(opt);
        }
    });

    return { groups, noGroup };
});

const selectOption = (option: Option) => {
    emit('update:modelValue', option.value);
    open.value = false;
    searchTerm.value = '';
};

const clearSelection = () => {
    emit('update:modelValue', null);
};

// Reset search when popover opens
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
            'w-full justify-between h-10 rounded-xl border-slate-200 shadow-none px-3 font-medium hover:bg-slate-50 transition-colors text-[13px]',
            !selectedLabel && 'text-muted-foreground font-normal',
            props.class
        )">
            <span class="truncate">{{ selectedLabel ?? placeholder }}</span>
            <div class="flex items-center">
                <Check v-if="selectedLabel" class="h-3.5 w-3.5 shrink-0 text-accent opacity-50 cursor-pointer hover:opacity-100" @click.stop="clearSelection" />
                <ChevronsUpDown v-else class="h-4 w-4 shrink-0 opacity-50" />
            </div>
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
        <div class="p-1 max-h-80 overflow-y-auto overflow-x-hidden">
            <template v-if="Object.keys(groupedOptions.groups).length > 0 || groupedOptions.noGroup.length > 0">
                <!-- Grouped Items -->
                <div v-for="(items, groupName) in groupedOptions.groups" :key="groupName" class="mb-2">
                    <div class="px-3 py-1.5 text-[10px] font-semibold text-accent uppercase tracking-wider bg-slate-50/80 rounded-md mb-1">
                        {{ groupName }}
                    </div>
                    <button v-for="option in items" :key="option.value" type="button"
                        @click="selectOption(option)"
                        class="relative flex w-full cursor-pointer select-none items-center rounded-lg py-2.5 pl-9 pr-3 text-[13px] outline-none hover:bg-slate-50 focus:bg-slate-50 transition-colors text-left"
                        :class="{
                            'text-accent font-semibold bg-accent/5': option.value.toString() === modelValue?.toString()
                        }">
                        <span v-if="option.value.toString() === modelValue?.toString()"
                            class="absolute left-3 flex h-4 w-4 items-center justify-center">
                            <Check class="h-4 w-4" />
                        </span>
                        <span class="truncate">{{ option.label }}</span>
                    </button>
                </div>

                <!-- Ungrouped Items -->
                <button v-for="option in groupedOptions.noGroup" :key="option.value" type="button"
                    @click="selectOption(option)"
                    class="relative flex w-full cursor-pointer select-none items-center rounded-lg py-2.5 pl-9 pr-3 text-[13px] outline-none hover:bg-slate-50 focus:bg-slate-50 transition-colors text-left"
                    :class="{
                        'text-accent font-semibold bg-accent/5': option.value.toString() === modelValue?.toString()
                    }">
                    <span v-if="option.value.toString() === modelValue?.toString()"
                        class="absolute left-3 flex h-4 w-4 items-center justify-center">
                        <Check class="h-4 w-4" />
                    </span>
                    <span class="truncate">{{ option.label }}</span>
                </button>
            </template>

            <div v-else class="py-10 text-center text-sm text-muted-foreground">
                <div class="flex flex-col items-center gap-2">
                    <Search class="h-8 w-8 opacity-20" />
                    <p class="text-xs font-medium uppercase tracking-widest opacity-40">Tidak ditemukan</p>
                </div>
            </div>
        </div>
    </PopoverContent>
</Popover>
</template>
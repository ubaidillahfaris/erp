<script setup lang="ts">
import { computed } from 'vue';
import { cn } from '@/lib/utils';

interface Props {
    modelValue: number | null;
    placeholder?: string;
    disabled?: boolean;
    class?: string;
}

const props = withDefaults(defineProps<Props>(), {
    placeholder: '0',
});

const emit = defineEmits(['update:modelValue']);

const formatDisplayValue = (val: number | null): string => {
    if (val === null || val === undefined) return '';
    return new Intl.NumberFormat('id-ID').format(val);
};

const displayValue = computed({
    get: () => formatDisplayValue(props.modelValue),
    set: (val: string) => {
        // Strip everything except digits
        const numericValue = parseInt(val.replace(/\D/g, ''), 10);
        emit('update:modelValue', isNaN(numericValue) ? null : numericValue);
    },
});

const handleInput = (event: Event) => {
    const target = event.target as HTMLInputElement;
    const rawValue = target.value.replace(/\D/g, '');
    
    // Update model value
    const numericValue = rawValue === '' ? null : parseInt(rawValue, 10);
    emit('update:modelValue', numericValue);
    
    // Re-format the input field display immediately to keep cursor in check and formatting correct
    target.value = formatDisplayValue(numericValue);
};
</script>

<template>
    <div 
        :class="cn(
            'group flex h-10 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm ring-offset-background focus-within:ring-2 focus-within:ring-ring focus-within:ring-offset-2',
            disabled && 'cursor-not-allowed opacity-50',
            props.class
        )"
    >
        <span class="flex items-center pr-1 text-muted-foreground select-none font-medium">Rp</span>
        <input
            type="text"
            :value="displayValue"
            @input="handleInput"
            :placeholder="placeholder"
            :disabled="disabled"
            class="w-full bg-transparent p-0 focus-visible:outline-none disabled:cursor-not-allowed font-medium"
        />
    </div>
</template>

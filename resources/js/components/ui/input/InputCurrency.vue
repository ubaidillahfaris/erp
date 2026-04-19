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
        // Strip everything except digits and negative sign at start
        const isNegative = val.trim().startsWith('-');
        const numericPart = val.replace(/\D/g, '');
        const numericValue = parseInt(numericPart, 10);
        
        if (isNaN(numericValue)) {
            emit('update:modelValue', null);
        } else {
            emit('update:modelValue', isNegative ? -numericValue : numericValue);
        }
    },
});

const handleInput = (event: Event) => {
    const target = event.target as HTMLInputElement;
    const value = target.value;
    
    // Check if it's negative
    const isNegative = value.trim().startsWith('-');
    // Strip everything except digits
    const numericPart = value.replace(/\D/g, '');
    
    // Update model value
    const numericValue = numericPart === '' ? null : parseInt(numericPart, 10);
    const finalValue = (numericValue !== null && isNegative) ? -numericValue : numericValue;
    
    emit('update:modelValue', finalValue);
    
    // Re-format the input field display
    target.value = (isNegative && (numericValue === null || numericValue === 0)) ? '-' : formatDisplayValue(finalValue);
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

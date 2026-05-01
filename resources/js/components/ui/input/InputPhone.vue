<script setup lang="ts">
import { ref, computed, watch } from 'vue';
import { cn } from '@/lib/utils';
import { 
    Popover,
    PopoverContent,
    PopoverTrigger
} from '@/components/ui/popover';
import { ChevronDown } from 'lucide-vue-next';

interface Props {
    modelValue: string | null;
    placeholder?: string;
    disabled?: boolean;
    class?: string;
}

const props = withDefaults(defineProps<Props>(), {
    placeholder: 'Nomor telepon',
});

const emit = defineEmits(['update:modelValue']);

const countries = [
    { name: 'Indonesia', code: '+62', flag: '🇮🇩' },
    { name: 'Malaysia', code: '+60', flag: '🇲🇾' },
    { name: 'Singapore', code: '+65', flag: '🇸🇬' },
    { name: 'Thailand', code: '+66', flag: '🇹🇭' },
    { name: 'Philippines', code: '+63', flag: '🇵🇭' },
    { name: 'Australia', code: '+61', flag: '🇦🇺' },
    { name: 'USA', code: '+1', flag: '🇺🇸' },
    { name: 'UK', code: '+44', flag: '🇬🇧' },
    { name: 'Japan', code: '+81', flag: '🇯🇵' },
    { name: 'South Korea', code: '+82', flag: '🇰🇷' },
    { name: 'China', code: '+86', flag: '🇨🇳' },
    { name: 'India', code: '+91', flag: '🇮🇳' },
    { name: 'Saudi Arabia', code: '+966', flag: '🇸🇦' },
    { name: 'UAE', code: '+971', flag: '🇦🇪' },
];

const selectedCountry = ref(countries[0]);
const phoneNumber = ref('');

// Parse modelValue on mount and when it changes externally
watch(() => props.modelValue, (val) => {
    if (!val) {
        phoneNumber.value = '';
        return;
    }
    
    const country = countries.find(c => val.startsWith(c.code));
    if (country) {
        selectedCountry.value = country;
        phoneNumber.value = val.slice(country.code.length);
    } else {
        // Fallback or custom handling
        phoneNumber.value = val;
    }
}, { immediate: true });

const updateValue = () => {
    const combined = selectedCountry.value.code + phoneNumber.value.replace(/\D/g, '');
    emit('update:modelValue', combined);
};

const handleCountrySelect = (country: typeof countries[0]) => {
    selectedCountry.value = country;
    updateValue();
};

const handleInput = (event: Event) => {
    const target = event.target as HTMLInputElement;
    phoneNumber.value = target.value.replace(/\D/g, '');
    updateValue();
};
</script>

<template>
    <div 
        :class="cn(
            'group flex h-10 w-full rounded-xl border border-slate-200 bg-white ring-offset-background focus-within:ring-2 focus-within:ring-ring focus-within:ring-offset-2',
            disabled && 'cursor-not-allowed opacity-50',
            props.class
        )"
    >
        <Popover>
            <PopoverTrigger as-child>
                <button 
                    type="button"
                    :disabled="disabled"
                    class="flex items-center gap-1 px-3 border-r border-slate-100 hover:bg-slate-50 transition-colors h-full rounded-l-xl text-sm font-medium"
                >
                    <span>{{ selectedCountry.flag }}</span>
                    <span>{{ selectedCountry.code }}</span>
                    <ChevronDown class="h-3 w-3 opacity-50" />
                </button>
            </PopoverTrigger>
            <PopoverContent class="w-64 p-0 rounded-xl overflow-hidden" align="start">
                <div class="max-h-60 overflow-y-auto">
                    <button
                        v-for="country in countries"
                        :key="country.code"
                        type="button"
                        @click="handleCountrySelect(country)"
                        class="flex items-center w-full px-3 py-2 text-sm hover:bg-slate-50 transition-colors gap-3"
                        :class="selectedCountry.code === country.code && 'bg-slate-50'"
                    >
                        <span class="text-lg">{{ country.flag }}</span>
                        <span class="flex-1 text-left">{{ country.name }}</span>
                        <span class="text-muted-foreground">{{ country.code }}</span>
                    </button>
                </div>
            </PopoverContent>
        </Popover>

        <input
            type="text"
            :value="phoneNumber"
            @input="handleInput"
            :placeholder="placeholder"
            :disabled="disabled"
            class="flex-1 bg-transparent px-3 py-2 text-sm focus-visible:outline-none disabled:cursor-not-allowed font-medium placeholder:text-muted-foreground placeholder:font-normal"
        />
    </div>
</template>

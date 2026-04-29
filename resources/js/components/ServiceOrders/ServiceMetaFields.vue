<script setup lang="ts">
import { Clock, Weight, Wrench, Scissors } from 'lucide-vue-next';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { 
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from "@/components/ui/select";

interface Props {
    orderType: string;
    metadata: Record<string, any>;
    estimatedAt: string;
}

const props = defineProps<Props>();

const emit = defineEmits<{
    'update:metadata': [metadata: Record<string, any>];
    'update:estimatedAt': [date: string];
}>();

const updateMetadata = (key: string, value: any) => {
    emit('update:metadata', { ...props.metadata, [key]: value });
};
</script>

<template>
    <div class="space-y-4">
        <!-- Laundry Specific Fields -->
        <template v-if="orderType === 'laundry'">
            <div class="grid grid-cols-2 gap-4">
                <div class="flex flex-col gap-1.5">
                    <Label class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Berat (Kg)</Label>
                    <div class="relative">
                        <Weight class="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-slate-400" />
                        <Input 
                            type="number" 
                            :value="metadata.weight_kg" 
                            @input="updateMetadata('weight_kg', ($event.target as HTMLInputElement).value)"
                            class="pl-9 bg-white rounded-xl border-slate-200 h-10" 
                            step="0.1"
                        />
                    </div>
                </div>
                <div class="flex flex-col gap-1.5">
                    <Label class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Tipe Layanan</Label>
                    <Select :model-value="metadata.service_type" @update:model-value="updateMetadata('service_type', $event)">
                        <SelectTrigger class="bg-white rounded-xl border-slate-200 h-10">
                            <SelectValue placeholder="Pilih tipe" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="reguler">Reguler</SelectItem>
                            <SelectItem value="express">Express</SelectItem>
                            <SelectItem value="kilat">Kilat</SelectItem>
                        </SelectContent>
                    </Select>
                </div>
            </div>
        </template>

        <!-- Salon Specific Fields (Placeholder for extension) -->
        <template v-else-if="orderType === 'salon'">
            <div class="grid grid-cols-1 gap-4">
                <div class="flex flex-col gap-1.5">
                    <Label class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Stylist</Label>
                    <div class="relative">
                        <Scissors class="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-slate-400" />
                        <Input 
                            type="text" 
                            :value="metadata.stylist" 
                            @input="updateMetadata('stylist', ($event.target as HTMLInputElement).value)"
                            placeholder="Nama Stylist"
                            class="pl-9 bg-white rounded-xl border-slate-200 h-10" 
                        />
                    </div>
                </div>
            </div>
        </template>

        <!-- Bengkel Specific Fields (Placeholder for extension) -->
        <template v-else-if="orderType === 'bengkel'">
            <div class="grid grid-cols-2 gap-4">
                <div class="flex flex-col gap-1.5">
                    <Label class="text-[11px] font-bold uppercase tracking-wider text-slate-400">No. Plat</Label>
                    <div class="relative">
                        <Wrench class="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-slate-400" />
                        <Input 
                            type="text" 
                            :value="metadata.plate_number" 
                            @input="updateMetadata('plate_number', ($event.target as HTMLInputElement).value)"
                            placeholder="B 1234 ABC"
                            class="pl-9 bg-white rounded-xl border-slate-200 h-10" 
                        />
                    </div>
                </div>
                <div class="flex flex-col gap-1.5">
                    <Label class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Merk/Model</Label>
                    <Input 
                        type="text" 
                        :value="metadata.vehicle_model" 
                        @input="updateMetadata('vehicle_model', ($event.target as HTMLInputElement).value)"
                        placeholder="Vario 150"
                        class="bg-white rounded-xl border-slate-200 h-10" 
                    />
                </div>
            </div>
        </template>

        <!-- Generic Estimasi Selesai (Always show) -->
        <div class="flex flex-col gap-1.5">
            <Label class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Estimasi Selesai</Label>
            <div class="relative">
                <Clock class="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-slate-400" />
                <Input 
                    type="datetime-local" 
                    :value="estimatedAt" 
                    @input="emit('update:estimatedAt', ($event.target as HTMLInputElement).value)"
                    class="pl-9 bg-white rounded-xl border-slate-200 h-10 text-xs" 
                />
            </div>
        </div>
    </div>
</template>

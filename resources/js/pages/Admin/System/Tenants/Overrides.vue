<script setup lang="ts">
import { Head, router, Link, useForm } from '@inertiajs/vue3';
import { 
    Puzzle, ArrowLeft, Plus, Trash2, ShieldCheck,
    Clock, CheckCircle2, XCircle, Info, Calendar
} from 'lucide-vue-next';
import { ref } from 'vue';
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem } from '@/types';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Label } from '@/components/ui/label';
import { Switch } from '@/components/ui/switch';
import { Input } from '@/components/ui/input';
import { Select, SelectContent, SelectGroup, SelectItem, SelectLabel, SelectTrigger, SelectValue } from '@/components/ui/select';
import { storeOverride, destroyOverride } from '@/actions/App/Http/Controllers/Admin/TenantManagerController';

const props = defineProps<{
    company: any;
    overrides: any[];
    availableFeatures: Record<string, any[]>;
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Tenant Manager', href: '/admin/system/tenants' },
    { title: `Add-ons: ${props.company.name}`, href: '#' },
];

const form = useForm({
    feature_key: '',
    is_enabled: true,
    expires_at: '',
});

const submit = () => {
    form.post(storeOverride({ company: props.company.id }).url, {
        onSuccess: () => {
            form.reset('feature_key', 'expires_at');
        }
    });
};

const deleteOverride = (id: number) => {
    if (confirm('Remove this override? The feature will revert to tier defaults.')) {
        router.delete(destroyOverride({ company: props.company.id, override: id }).url);
    }
};

const formatDate = (dateString: string | null) => {
    if (!dateString) return 'Lifetime';
    return new Date(dateString).toLocaleDateString('id-ID', {
        day: '2-digit', month: 'short', year: 'numeric'
    });
};

const isExpired = (dateString: string | null) => {
    if (!dateString) return false;
    return new Date(dateString) < new Date();
};
</script>

<template>
<Head :title="`Add-ons - ${company.name}`" />

<AppLayout :breadcrumbs="breadcrumbs">
    <div class="px-6 py-8 bg-slate-50 min-h-[calc(100vh-64px)] flex flex-col gap-8 font-sans">
        
        <!-- HEADER -->
        <div class="flex items-center justify-between gap-4">
            <div class="flex items-center gap-4">
                <Link href="/admin/system/tenants">
                    <Button variant="outline" size="icon" class="btn-secondary h-8 w-8 shrink-0">
                        <ArrowLeft class="h-4 w-4" />
                    </Button>
                </Link>
                <div>
                    <div class="flex items-center gap-2">
                        <h1 class="text-xl font-black tracking-tight text-slate-900 uppercase">Tenant Add-ons</h1>
                        <Badge variant="outline" class="h-5 px-1.5 text-[10px] font-bold border-slate-200 text-slate-400 uppercase">Tenant: {{ company.name }}</Badge>
                    </div>
                    <p class="text-sm text-slate-400 mt-0.5">Override tier limits or provision specific modules for this company.</p>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <!-- LEFT: ADD OVERRIDE -->
            <div class="lg:col-span-1 flex flex-col gap-6">
                <div class="bg-white rounded-2xl border border-slate-200 overflow-hidden shadow-sm">
                    <div class="px-5 py-4 border-b border-slate-100 bg-slate-50/50">
                        <h2 class="text-xs font-black uppercase tracking-[0.2em] text-slate-500 flex items-center gap-2">
                            <Plus class="h-3.5 w-3.5" />
                            Provision Add-on
                        </h2>
                    </div>
                    <div class="p-5 flex flex-col gap-4">
                        <div class="grid gap-2">
                            <Label class="text-[10px] font-black uppercase tracking-widest text-slate-400">Select Menu / Feature</Label>
                            <Select v-model="form.feature_key">
                                <SelectTrigger class="h-11 border-slate-200 rounded-xl font-bold text-[13px] shadow-none">
                                    <SelectValue placeholder="Choose a menu..." />
                                </SelectTrigger>
                                <SelectContent class="font-sans max-h-[400px]">
                                    <SelectGroup v-for="(features, moduleName) in availableFeatures" :key="moduleName">
                                        <SelectLabel class="text-[10px] font-black uppercase tracking-[0.3em] text-accent pt-4 pb-1.5 px-4 bg-slate-50/50">{{ moduleName }}</SelectLabel>
                                        <SelectItem v-for="feature in features" :key="feature.feature_key" :value="feature.feature_key" class="py-3 cursor-pointer">
                                            <div class="flex flex-col gap-0.5">
                                                <span class="text-[13px] font-bold text-slate-900">{{ feature.name }}</span>
                                                <span class="text-[9px] font-mono font-bold text-slate-400 uppercase tracking-tighter">{{ feature.feature_key }}</span>
                                            </div>
                                        </SelectItem>
                                    </SelectGroup>
                                </SelectContent>
                            </Select>
                            <div v-if="form.errors.feature_key" class="text-xs text-destructive font-bold">{{ form.errors.feature_key }}</div>
                        </div>

                        <div class="grid gap-2">
                            <Label class="text-[10px] font-black uppercase tracking-widest text-slate-400">Expiration (Optional)</Label>
                            <div class="relative">
                                <Calendar class="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-slate-300" />
                                <Input type="date" v-model="form.expires_at" class="h-11 pl-10 border-slate-200 rounded-xl font-bold text-xs shadow-none" />
                            </div>
                        </div>

                        <div class="flex items-center justify-between p-4 bg-slate-50 rounded-xl border border-slate-100">
                            <div class="space-y-0.5">
                                <Label class="text-[11px] font-bold text-slate-700 uppercase">Override Value</Label>
                                <p class="text-[10px] text-slate-400">{{ form.is_enabled ? 'Force Grant' : 'Force Deny' }}</p>
                            </div>
                            <Switch 
                                :checked="form.is_enabled" 
                                @update:checked="form.is_enabled = $event" 
                                class="data-[state=checked]:bg-emerald-500"
                            />
                        </div>

                        <Button @click="submit" class="w-full bg-accent hover:bg-accent/90 text-white font-black uppercase tracking-widest text-[11px] h-12 rounded-xl shadow-lg shadow-accent/20 mt-2" :disabled="form.processing">
                            Apply Add-on
                        </Button>
                    </div>
                </div>
            </div>

            <!-- RIGHT: ACTIVE OVERRIDES -->
            <div class="lg:col-span-2">
                <div class="bg-white rounded-2xl border border-slate-200 overflow-hidden shadow-sm min-h-[400px]">
                    <div class="px-6 py-5 border-b border-slate-100 flex items-center justify-between">
                        <h2 class="text-xs font-black uppercase tracking-[0.2em] text-slate-500 flex items-center gap-2">
                            <ShieldCheck class="h-4 w-4 text-emerald-500" />
                            Active Overrides
                        </h2>
                        <Badge variant="outline" class="h-6 font-mono text-[10px] font-bold border-slate-100 text-slate-400">COUNT: {{ overrides.length }}</Badge>
                    </div>

                    <div class="divide-y divide-slate-100">
                        <div v-for="override in overrides" :key="override.id" class="px-6 py-4 flex items-center justify-between hover:bg-slate-50/50 transition-colors group">
                            <div class="flex items-center gap-4">
                                <div :class="[
                                    'h-10 w-10 rounded-xl flex items-center justify-center border',
                                    override.is_enabled ? 'bg-emerald-50 border-emerald-100 text-emerald-600' : 'bg-rose-50 border-rose-100 text-rose-600'
                                ]">
                                    <CheckCircle2 v-if="override.is_enabled" class="h-5 w-5" />
                                    <XCircle v-else class="h-5 w-5" />
                                </div>
                                <div>
                                    <h3 class="text-[13px] font-black text-slate-900 tracking-tight uppercase">{{ override.feature_key }}</h3>
                                    <div class="flex items-center gap-3 mt-1">
                                        <div class="flex items-center gap-1.5">
                                            <Clock class="h-3 w-3 text-slate-300" />
                                            <span :class="[
                                                'text-[10px] font-bold uppercase tracking-widest',
                                                isExpired(override.expires_at) ? 'text-rose-500' : 'text-slate-400'
                                            ]">
                                                {{ isExpired(override.expires_at) ? 'Expired' : (override.expires_at ? `Until ${formatDate(override.expires_at)}` : 'Permanent') }}
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="flex items-center gap-4 opacity-0 group-hover:opacity-100 transition-opacity">
                                <Badge :variant="override.is_enabled ? 'default' : 'destructive'" class="h-6 text-[10px] font-black tracking-widest px-2 shadow-none">
                                    {{ override.is_enabled ? 'ENABLED' : 'DISABLED' }}
                                </Badge>
                                <Button variant="ghost" size="icon" @click="deleteOverride(override.id)" class="h-8 w-8 text-slate-300 hover:text-rose-600 hover:bg-rose-50">
                                    <Trash2 class="h-4 w-4" />
                                </Button>
                            </div>
                        </div>

                        <div v-if="overrides.length === 0" class="flex flex-col items-center justify-center py-20 text-center opacity-20">
                            <Puzzle class="h-12 w-12 text-slate-400 mb-4" />
                            <p class="text-xs font-black uppercase tracking-widest text-slate-500">No Custom Overrides</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</AppLayout>
</template>

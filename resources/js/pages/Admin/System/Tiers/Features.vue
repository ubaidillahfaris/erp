<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { 
    Layers, ArrowLeft, Save, ShieldCheck, 
    CheckCircle2, Info, Search, Package,
    X, Check, Lock, Unlock
} from 'lucide-vue-next';
import { ref, computed } from 'vue';
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem } from '@/types';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Checkbox } from '@/components/ui/checkbox';
import { Label } from '@/components/ui/label';
import { Input } from '@/components/ui/input';
import { syncFeatures } from '@/actions/App/Http/Controllers/Admin/TierManagerController';

const props = defineProps<{
    tier: any;
    availableFeatures: Record<string, any[]>;
    currentFeatures: string[];
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Tier Definitions', href: '/admin/system/tiers' },
    { title: `${props.tier.name} Features`, href: '#' },
];

const form = useForm({
    features: [...props.currentFeatures],
});

const searchQuery = ref('');

const filteredFeatures = computed(() => {
    if (!searchQuery.value) return props.availableFeatures;

    const filtered: Record<string, any[]> = {};
    const query = searchQuery.value.toLowerCase();

    Object.entries(props.availableFeatures).forEach(([moduleName, features]) => {
        const matches = features.filter(f => 
            f.name.toLowerCase().includes(query) || 
            f.feature_key.toLowerCase().includes(query)
        );
        if (matches.length > 0) {
            filtered[moduleName] = matches;
        }
    });

    return filtered;
});

const toggleFeature = (key: string) => {
    const index = form.features.indexOf(key);
    if (index === -1) {
        form.features.push(key);
    } else {
        form.features.splice(index, 1);
    }
};

const toggleModule = (moduleName: string, features: any[]) => {
    const keys = features.map(f => f.feature_key);
    const allSelected = keys.every(k => form.features.includes(k));

    if (allSelected) {
        form.features = form.features.filter(k => !keys.includes(k));
    } else {
        keys.forEach(k => {
            if (!form.features.includes(k)) form.features.push(k);
        });
    }
};

const submit = () => {
    form.post(syncFeatures({ tier: props.tier.id }).url);
};

const isAllSelected = (features: any[]) => {
    return features.map(f => f.feature_key).every(k => form.features.includes(k));
};

const getModuleIcon = (moduleName: string) => {
    return Package; // Could be customized
};
</script>

<template>
<Head :title="`${tier.name} Features`" />

<AppLayout :breadcrumbs="breadcrumbs">
    <div class="px-6 py-8 bg-slate-50 min-h-[calc(100vh-64px)] flex flex-col gap-8 font-sans">
        
        <!-- HEADER -->
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-6">
            <div class="flex items-center gap-4">
                <Link href="/admin/system/tiers">
                    <Button variant="outline" size="icon" class="btn-secondary h-8 w-8 shrink-0">
                        <ArrowLeft class="h-4 w-4" />
                    </Button>
                </Link>
                <div>
                    <div class="flex items-center gap-2">
                        <h1 class="text-xl font-black tracking-tight text-slate-900 uppercase">{{ tier.name }} Features</h1>
                        <Badge variant="outline" class="h-5 px-1.5 text-[10px] font-bold border-slate-200 text-slate-400">ID: #{{ tier.id }}</Badge>
                    </div>
                    <p class="text-sm text-slate-400 mt-0.5 font-medium">Select which features and menu items are included in this subscription package.</p>
                </div>
            </div>

            <div class="flex items-center gap-3">
                <div class="relative w-64">
                    <Search class="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-slate-300" />
                    <Input 
                        v-model="searchQuery" 
                        placeholder="Search features..." 
                        class="h-10 pl-10 border-slate-200 rounded-xl font-bold text-xs shadow-none focus-visible:ring-accent/10"
                    />
                </div>
                <Button @click="submit" class="bg-accent hover:bg-accent/90 text-white gap-2 h-10 px-6 rounded-xl text-xs font-black uppercase tracking-widest shadow-lg shadow-accent/20" :disabled="form.processing">
                    <Save class="h-4 w-4" /> Save Configuration
                </Button>
            </div>
        </div>

        <!-- MATRIX -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
            <div v-for="(features, moduleName) in filteredFeatures" :key="moduleName" class="flex flex-col gap-4">
                <div class="bg-white rounded-[1.5rem] border border-slate-200 overflow-hidden shadow-sm flex flex-col h-full">
                    
                    <!-- Module Header -->
                    <div class="px-6 py-4 bg-slate-50/50 border-b border-slate-100 flex items-center justify-between group cursor-pointer" @click="toggleModule(moduleName, features)">
                        <div class="flex items-center gap-3">
                            <div class="h-8 w-8 rounded-lg bg-white border border-slate-200 flex items-center justify-center text-slate-400 group-hover:bg-accent group-hover:text-white transition-all">
                                <component :is="getModuleIcon(moduleName)" class="h-4 w-4" />
                            </div>
                            <h3 class="text-xs font-black uppercase tracking-[0.2em] text-slate-900">{{ moduleName }}</h3>
                        </div>
                        <div :class="['h-5 w-5 rounded-full flex items-center justify-center border transition-all', isAllSelected(features) ? 'bg-emerald-500 border-emerald-500 text-white' : 'bg-white border-slate-200 text-slate-300']">
                            <Check v-if="isAllSelected(features)" class="h-3 w-3 stroke-[4]" />
                        </div>
                    </div>

                    <!-- Features List -->
                    <div class="p-6 flex flex-col gap-4">
                        <div 
                            v-for="feature in features" 
                            :key="feature.feature_key" 
                            @click="toggleFeature(feature.feature_key)"
                            class="flex items-center justify-between p-3 rounded-xl border border-transparent hover:border-slate-100 hover:bg-slate-50 transition-all cursor-pointer group/item"
                        >
                            <div class="flex flex-col">
                                <span class="text-xs font-bold text-slate-700 group-hover/item:text-slate-900">{{ feature.name }}</span>
                                <span class="text-[9px] font-mono font-bold text-slate-300 uppercase tracking-tighter mt-0.5">{{ feature.feature_key }}</span>
                            </div>
                            
                            <div :class="['h-6 w-6 rounded-lg flex items-center justify-center border transition-all', form.features.includes(feature.feature_key) ? 'bg-emerald-50 border-emerald-100 text-emerald-600' : 'bg-white border-slate-200 text-slate-100 group-hover/item:border-slate-300']">
                                <CheckCircle2 v-if="form.features.includes(feature.feature_key)" class="h-4 w-4" />
                                <div v-else class="h-1 w-1 rounded-full bg-current"></div>
                            </div>
                        </div>
                    </div>

                    <div class="mt-auto px-6 py-3 bg-slate-50/30 border-t border-slate-50 flex items-center justify-between">
                        <span class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">
                            {{ features.filter(f => form.features.includes(f.feature_key)).length }} / {{ features.length }} Selected
                        </span>
                    </div>
                </div>
            </div>

            <div v-if="Object.keys(filteredFeatures).length === 0" class="lg:col-span-3 py-32 flex flex-col items-center gap-4 opacity-20 text-center">
                <Search class="h-16 w-16" />
                <div>
                    <p class="text-lg font-black uppercase tracking-widest">No Features Found</p>
                    <p class="text-sm">Try adjusting your search query or ensure features have feature_keys.</p>
                </div>
            </div>
        </div>

        <!-- SUMMARY BAR (Sticky if needed, but currently at bottom) -->
        <div class="mt-auto bg-white border border-slate-200 rounded-2xl p-6 flex items-center justify-between shadow-lg shadow-slate-200/50">
            <div class="flex items-center gap-6">
                <div class="flex flex-col">
                    <span class="text-2xl font-black text-slate-900">{{ form.features.length }}</span>
                    <span class="text-[10px] font-black uppercase tracking-widest text-slate-400">Total Features Provisioned</span>
                </div>
                <div class="h-10 w-px bg-slate-100"></div>
                <div class="flex items-center gap-2">
                    <div class="flex -space-x-2">
                        <div v-for="i in Math.min(form.features.length, 5)" :key="i" class="h-8 w-8 rounded-full border-2 border-white bg-slate-100 flex items-center justify-center">
                            <ShieldCheck class="h-3 w-3 text-slate-400" />
                        </div>
                        <div v-if="form.features.length > 5" class="h-8 w-8 rounded-full border-2 border-white bg-accent text-white flex items-center justify-center text-[10px] font-bold">
                            +{{ form.features.length - 5 }}
                        </div>
                    </div>
                </div>
            </div>

            <div class="flex items-center gap-3">
                <Button variant="ghost" @click="form.reset()" class="text-xs font-bold uppercase tracking-widest px-6 h-11 rounded-xl">Reset Changes</Button>
                <Button @click="submit" class="bg-accent hover:bg-accent/90 text-white gap-2 h-11 px-10 rounded-xl text-xs font-black uppercase tracking-widest shadow-xl shadow-accent/30" :disabled="form.processing">
                    <Save class="h-4 w-4" /> Apply to {{ tier.name }} Tier
                </Button>
            </div>
        </div>

    </div>
</AppLayout>
</template>

<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { 
    Layers, ArrowLeft, Plus, MoreHorizontal, 
    ShieldCheck, Star, Crown, ChevronRight,
    Puzzle, Settings2
} from 'lucide-vue-next';
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem } from '@/types';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { 
    DropdownMenu, 
    DropdownMenuContent, 
    DropdownMenuItem, 
    DropdownMenuTrigger 
} from '@/components/ui/dropdown-menu';

const props = defineProps<{
    tiers: any[];
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'System Management', href: '/admin/system' },
    { title: 'Tier Definitions', href: '/admin/system/tiers' },
];

const getTierIcon = (slug: string) => {
    switch (slug) {
        case 'enterprise': return Crown;
        case 'pro': return Star;
        default: return Layers;
    }
};

const getTierColor = (slug: string) => {
    switch (slug) {
        case 'enterprise': return 'text-amber-500 bg-amber-50 border-amber-100';
        case 'pro': return 'text-blue-500 bg-blue-50 border-blue-100';
        default: return 'text-slate-500 bg-slate-50 border-slate-100';
    }
};
</script>

<template>
<Head title="Tier Management" />

<AppLayout :breadcrumbs="breadcrumbs">
    <div class="px-6 py-8 bg-slate-50 min-h-[calc(100vh-64px)] flex flex-col gap-6 font-sans">
        
        <!-- HEADER -->
        <div class="flex items-center justify-between gap-4">
            <div class="flex items-center gap-4">
                <Link href="/admin/system">
                    <Button variant="outline" size="icon" class="btn-secondary h-8 w-8 shrink-0">
                        <ArrowLeft class="h-4 w-4" />
                    </Button>
                </Link>
                <div>
                    <h1 class="text-xl font-bold tracking-tight text-slate-900 uppercase">Tier Definitions</h1>
                    <p class="text-sm text-slate-400 mt-0.5">Define subscription packages and manage their default feature access.</p>
                </div>
            </div>
            
            <div class="flex items-center gap-2">
                <Button class="bg-slate-900 hover:bg-slate-800 text-white gap-2 h-9 px-4 rounded-lg text-xs font-bold uppercase tracking-widest">
                    <Plus class="h-3.5 w-3.5" /> New Tier
                </Button>
            </div>
        </div>

        <!-- GRID TIERS -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <div 
                v-for="tier in tiers" 
                :key="tier.id" 
                class="bg-white rounded-[1.5rem] border border-slate-200 overflow-hidden shadow-sm hover:shadow-xl hover:shadow-slate-200/50 transition-all group"
            >
                <div class="p-8 flex flex-col gap-8 h-full">
                    <div class="flex items-start justify-between">
                        <div :class="['h-14 w-14 rounded-2xl flex items-center justify-center transition-transform group-hover:scale-110', getTierColor(tier.slug)]">
                            <component :is="getTierIcon(tier.slug)" class="h-7 w-7" />
                        </div>
                        
                        <DropdownMenu>
                            <DropdownMenuTrigger as-child>
                                <button class="h-8 w-8 flex items-center justify-center rounded-lg text-slate-300 hover:bg-slate-50 hover:text-slate-900 transition-all">
                                    <MoreHorizontal class="h-4 w-4" />
                                </button>
                            </DropdownMenuTrigger>
                            <DropdownMenuContent align="end" class="rounded-xl p-1.5 w-44 shadow-none border-slate-200 font-sans">
                                <DropdownMenuItem @click="$inertia.visit(`/admin/system/tiers/${tier.id}/features`)" class="rounded-lg h-9 px-2.5 gap-2.5 cursor-pointer text-[12px] font-bold">
                                    <Settings2 class="h-3.5 w-3.5" /> Configure Features
                                </DropdownMenuItem>
                                <DropdownMenuItem class="rounded-lg h-9 px-2.5 gap-2.5 cursor-pointer text-[12px] font-bold">
                                    <Plus class="h-3.5 w-3.5" /> Edit Package
                                </DropdownMenuItem>
                            </DropdownMenuContent>
                        </DropdownMenu>
                    </div>

                    <div class="space-y-2">
                        <h2 class="text-2xl font-black tracking-tighter text-slate-900 uppercase">{{ tier.name }}</h2>
                        <div class="flex items-center gap-2">
                            <Badge variant="outline" class="h-5 px-1.5 text-[9px] font-bold border-slate-100 text-slate-400 bg-slate-50 uppercase tracking-widest">
                                SLUG: {{ tier.slug }}
                            </Badge>
                        </div>
                    </div>

                    <div class="flex items-center justify-between p-4 bg-slate-50 rounded-2xl border border-slate-100 mt-auto">
                        <div class="flex flex-col">
                            <span class="text-xl font-black text-slate-900">{{ tier.features_count }}</span>
                            <span class="text-[9px] font-bold text-slate-400 uppercase tracking-widest">Active Features</span>
                        </div>
                        
                        <Link :href="`/admin/system/tiers/${tier.id}/features`">
                            <Button variant="outline" class="h-9 px-4 rounded-xl border-slate-200 bg-white hover:bg-accent hover:text-white hover:border-accent transition-all group/btn shadow-none">
                                <span class="text-[11px] font-black uppercase tracking-widest">Manage</span>
                                <ChevronRight class="h-3.5 w-3.5 ml-1 transition-transform group-hover/btn:translate-x-1" />
                            </Button>
                        </Link>
                    </div>
                </div>
            </div>

            <!-- EMPTY STATE / ADD NEW -->
            <div class="border-2 border-dashed border-slate-200 rounded-[1.5rem] p-8 flex flex-col items-center justify-center text-center gap-4 hover:border-accent/20 hover:bg-accent/5 transition-all group cursor-pointer min-h-[300px]">
                <div class="h-12 w-12 rounded-full bg-slate-100 flex items-center justify-center text-slate-300 group-hover:bg-accent group-hover:text-white transition-all">
                    <Plus class="h-6 w-6" />
                </div>
                <div>
                    <h3 class="text-sm font-black text-slate-400 uppercase tracking-widest group-hover:text-accent">Create New Tier</h3>
                    <p class="text-xs text-slate-300 mt-1 max-w-[180px]">Add a custom subscription plan to your platform.</p>
                </div>
            </div>
        </div>
    </div>
</AppLayout>
</template>

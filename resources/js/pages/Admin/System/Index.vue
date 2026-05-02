<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { 
    Package, Building2, Layers, 
    Activity, Database,
    ArrowRight, Sparkles
} from 'lucide-vue-next';
import AppLayout from '@/layouts/AppLayout.vue';
import PageHeader from '@/components/PageHeader.vue';
import type { BreadcrumbItem } from '@/types';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';

const props = defineProps<{
    stats: {
        modules: number;
        active_modules: number;
        tenants: number;
        tiers: number;
    };
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'System Management', href: '/admin/system' },
];

const navigationCards = [
    {
        title: 'Module Registry',
        description: 'Manage global application modules, versions, and deployment status.',
        href: '/admin/system/modules',
        icon: Package,
        color: 'bg-blue-500',
        lightColor: 'bg-blue-50',
        borderColor: 'border-blue-100',
        textColor: 'text-blue-600',
        badge: `${props.stats.active_modules}/${props.stats.modules} Active`
    },
    {
        title: 'Tenant Manager',
        description: 'Configure tenant subscriptions, tiers, and granular feature overrides.',
        href: '/admin/system/tenants',
        icon: Building2,
        color: 'bg-accent',
        lightColor: 'bg-accent/5',
        borderColor: 'border-accent/10',
        textColor: 'text-accent',
        badge: `${props.stats.tenants} Tenants`
    },
    {
        title: 'Tier Definitions',
        description: 'Define subscription packages and their default feature permissions.',
        href: '/admin/system/tiers',
        icon: Layers,
        color: 'bg-emerald-500',
        lightColor: 'bg-emerald-50',
        borderColor: 'border-emerald-100',
        textColor: 'text-emerald-600',
        badge: `${props.stats.tiers} Tiers`
    }
];
</script>

<template>
<Head title="System Management" />

<AppLayout :breadcrumbs="breadcrumbs">
    <div class="px-6 py-8 bg-slate-50 min-h-[calc(100vh-64px)] flex flex-col gap-8 font-sans">
        
        <PageHeader 
            title="System Management" 
            description="Central control unit for the Valee ERP platform. Orchestrate modules, manage multi-tenant compliance, and override features."
        >
            <template #actions>
                <div class="flex items-center gap-3">
                    <div class="flex flex-col items-end">
                        <span class="text-[10px] font-semibold uppercase tracking-wider text-slate-400">Platform Status</span>
                        <div class="flex items-center gap-1.5">
                            <span class="text-xs font-medium text-slate-900">Operational</span>
                            <div class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></div>
                        </div>
                    </div>
                </div>
            </template>
        </PageHeader>

        <!-- STATS OVERVIEW -->
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 max-w-7xl mx-auto w-full">
            <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-sm flex flex-col gap-1">
                <span class="text-[10px] font-semibold uppercase tracking-wider text-slate-400">Total Modules</span>
                <span class="text-2xl font-semibold text-slate-900 tabular-nums">{{ stats.modules }}</span>
            </div>
            <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-sm flex flex-col gap-1">
                <span class="text-[10px] font-semibold uppercase tracking-wider text-slate-400">Active Modules</span>
                <span class="text-2xl font-semibold text-emerald-600 tabular-nums">{{ stats.active_modules }}</span>
            </div>
            <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-sm flex flex-col gap-1">
                <span class="text-[10px] font-semibold uppercase tracking-wider text-slate-400">Active Tenants</span>
                <span class="text-2xl font-semibold text-slate-900 tabular-nums">{{ stats.tenants }}</span>
            </div>
            <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-sm flex flex-col gap-1">
                <span class="text-[10px] font-semibold uppercase tracking-wider text-slate-400">Subscription Tiers</span>
                <span class="text-2xl font-semibold text-slate-900 tabular-nums">{{ stats.tiers }}</span>
            </div>
        </div>

        <!-- NAVIGATION GRID -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 max-w-7xl mx-auto w-full">
            <Link 
                v-for="card in navigationCards" 
                :key="card.title" 
                :href="card.href"
                class="group block relative overflow-hidden bg-white rounded-[2rem] border border-slate-200 p-8 transition-all hover:shadow-xl hover:shadow-slate-200/50 hover:-translate-y-1"
            >
                <!-- Geometric Background Decor -->
                <div class="absolute -right-8 -top-8 w-32 h-32 rounded-full transition-transform duration-500 group-hover:scale-150" :class="card.lightColor"></div>
                
                <div class="relative flex flex-col h-full justify-between gap-12">
                    <div class="flex items-start justify-between">
                        <div :class="['p-4 rounded-2xl shadow-lg shadow-current/10', card.color, 'text-white']">
                            <component :is="card.icon" class="h-6 w-6" />
                        </div>
                        <Badge variant="outline" class="border-slate-100 font-medium text-[10px] text-slate-400 bg-slate-50 uppercase tracking-wider">
                            {{ card.badge }}
                        </Badge>
                    </div>

                    <div class="space-y-2">
                        <h3 class="text-lg font-semibold tracking-tight text-slate-900 group-hover:text-accent transition-colors flex items-center gap-2">
                            {{ card.title }}
                            <ArrowRight class="h-4 w-4 opacity-0 -translate-x-2 transition-all group-hover:opacity-100 group-hover:translate-x-0" />
                        </h3>
                        <p class="text-[13px] leading-relaxed text-slate-500 font-medium">
                            {{ card.description }}
                        </p>
                    </div>
                </div>
            </Link>

            <!-- Quick Action Card -->
            <div class="bg-slate-900 rounded-[2rem] p-8 flex flex-col justify-between text-white relative overflow-hidden group">
                <Sparkles class="absolute top-4 right-4 h-12 w-12 text-white/5 rotate-12" />
                
                <div class="space-y-1">
                    <p class="text-[10px] font-semibold uppercase tracking-widest text-white/40">Quick Insights</p>
                    <h3 class="text-lg font-semibold tracking-tight">Platform Health</h3>
                </div>

                <div class="space-y-4">
                    <div class="flex items-center justify-between text-[11px] border-b border-white/10 pb-2">
                        <span class="text-white/60">Cache Status</span>
                        <span class="font-semibold text-emerald-400">OPTIMIZED</span>
                    </div>
                    <div class="flex items-center justify-between text-[11px] border-b border-white/10 pb-2">
                        <span class="text-white/60">Database Latency</span>
                        <span class="font-semibold">12ms</span>
                    </div>
                    <Button variant="outline" class="w-full bg-transparent border-white/20 hover:bg-white hover:text-black transition-all rounded-xl h-10 text-[11px] font-semibold uppercase tracking-widest">
                        Clear System Cache
                    </Button>
                </div>
            </div>
        </div>

        <!-- SECONDARY ACTIONS -->
        <div class="grid grid-cols-1 lg:grid-cols-4 gap-6 max-w-7xl mx-auto w-full pb-12">
            <div class="lg:col-span-3 bg-white rounded-2xl border border-slate-200 p-6 flex items-center justify-between group cursor-pointer hover:border-accent/20 transition-all">
                <div class="flex items-center gap-4">
                    <div class="h-12 w-12 rounded-xl bg-slate-50 flex items-center justify-center text-slate-400 group-hover:bg-accent/5 group-hover:text-accent transition-all">
                        <Database class="h-5 w-5" />
                    </div>
                    <div>
                        <h4 class="text-sm font-semibold text-slate-900 tracking-tight">System Audit Logs</h4>
                        <p class="text-xs text-slate-400 font-medium">Track all administrative changes and login attempts across the platform.</p>
                    </div>
                </div>
                <ArrowRight class="h-4 w-4 text-slate-200 group-hover:text-accent group-hover:translate-x-1 transition-all" />
            </div>

            <div class="bg-white rounded-2xl border border-slate-200 p-6 flex items-center justify-center gap-3 hover:bg-slate-50 transition-all cursor-pointer group">
                <Activity class="h-4 w-4 text-slate-400 group-hover:text-accent" />
                <span class="text-xs font-semibold text-slate-900 tracking-wider uppercase">Maintenance</span>
            </div>
        </div>

    </div>
</AppLayout>
</template>

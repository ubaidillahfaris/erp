<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { computed } from 'vue';
import { 
    Home, 
    ShieldAlert, 
    SearchX, 
    ServerCrash,
    Construction
} from 'lucide-vue-next';
import { Button } from '@/components/ui/button';

const props = defineProps<{
    status: number;
    message?: string;
}>();

const title = computed(() => {
    return {
        503: 'Service Unavailable',
        500: 'Server Error',
        404: 'Halaman Tidak Ditemukan',
        403: 'Halaman yang anda cari tidak dapat ditemukan',
    }[props.status] || 'Error Occurred';
});

const description = computed(() => {
    if (props.message) return props.message;
    
    return {
        503: 'Maaf, kami sedang melakukan pemeliharaan rutin. Silakan coba beberapa saat lagi.',
        500: 'Terjadi kesalahan pada server kami. Tim teknis kami sedang menanganinya.',
        404: 'Maaf, halaman yang Anda cari tidak tersedia atau telah dipindahkan.',
        403: 'Fitur ini tidak tersedia untuk paket langganan Anda.',
    }[props.status] || 'Terjadi kesalahan yang tidak terduga.';
});

const icon = computed(() => {
    switch (props.status) {
        case 403: return ShieldAlert;
        case 404: return SearchX;
        case 500: return ServerCrash;
        case 503: return Construction;
        default: return ShieldAlert;
    }
});

const iconColor = computed(() => {
    switch (props.status) {
        case 403: return 'text-orange-500 bg-orange-50';
        case 404: return 'text-slate-500 bg-slate-50';
        case 500: return 'text-rose-500 bg-rose-50';
        case 503: return 'text-amber-500 bg-amber-50';
        default: return 'text-slate-500 bg-slate-50';
    }
});
</script>

<template>
    <Head :title="title" />

    <div class="min-h-screen bg-slate-50 flex items-center justify-center p-6 font-sans">
        <div class="max-w-md w-full bg-white rounded-[2.5rem] border border-slate-200 shadow-xl shadow-slate-200/50 overflow-hidden p-12 flex flex-col items-center text-center gap-8 animate-in fade-in zoom-in duration-500">
            
            <!-- ICON -->
            <div :class="[
                'h-24 w-24 rounded-3xl flex items-center justify-center transition-all duration-700',
                iconColor
            ]">
                <component :is="icon" class="h-10 w-10" />
            </div>

            <!-- CONTENT -->
            <div class="space-y-3">
                <h1 class="text-2xl font-semibold text-slate-900 leading-tight">
                    {{ title }}
                </h1>
                <p class="text-[13px] text-slate-500 font-medium leading-relaxed px-4">
                    {{ description }}
                </p>
            </div>

            <!-- ACTION -->
            <div class="w-full pt-4">
                <Link href="/dashboard">
                    <Button class="w-full bg-slate-900 hover:bg-slate-800 text-white font-semibold uppercase tracking-widest text-[11px] h-14 rounded-2xl shadow-lg shadow-slate-200 transition-all active:scale-95 group">
                        <Home class="mr-2 h-4 w-4 opacity-50 group-hover:opacity-100 transition-opacity" />
                        Kembali ke Dashboard
                    </Button>
                </Link>
            </div>

            <!-- FOOTER INFO -->
            <div class="pt-4 flex items-center gap-2 opacity-30">
                <div class="h-1 w-1 rounded-full bg-slate-400"></div>
                <span class="text-[10px] font-mono font-bold uppercase tracking-tighter text-slate-500">Status Code: {{ status }}</span>
                <div class="h-1 w-1 rounded-full bg-slate-400"></div>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { dashboard, login, register } from '@/routes';
import FloatingReceipt from '@/components/Landing/FloatingReceipt.vue';
import FloatingStock from '@/components/Landing/FloatingStock.vue';
import FloatingProfit from '@/components/Landing/FloatingProfit.vue';
import { Button } from '@/components/ui/button';
import {
    Store,
    Package,
    ChefHat,
    BarChart3,
    ChevronRight,
    ShieldCheck,
    Zap,
    Globe
} from 'lucide-vue-next';

withDefaults(
    defineProps<{
        canRegister: boolean;
    }>(),
    {
        canRegister: true,
    },
);
</script>

<template>
<Head title="Selamat Datang">
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" />
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap"
        rel="stylesheet" />
</Head>

<div
    class="relative min-h-screen w-full overflow-hidden bg-white font-['Plus_Jakarta_Sans',sans-serif] selection:bg-blue-100 selection:text-blue-900 dark:bg-slate-950">
    <!-- Dot Grid Background -->
    <div
        class="absolute inset-0 z-0 bg-[radial-gradient(#e5e7eb_1px,transparent_1px)] [background-size:32px_32px] [mask-image:radial-gradient(ellipse_50%_50%_at_50%_50%,#000_70%,transparent_100%)] dark:bg-[radial-gradient(#1e293b_1px,transparent_1px)]">
    </div>

    <!-- Navigation -->
    <nav class="sticky top-0 z-50 w-full border-b border-slate-200 bg-white dark:border-slate-800 dark:bg-slate-950/80">
        <div class="mx-auto flex h-16 max-w-7xl items-center justify-between px-4 sm:px-6 lg:px-8">
            <!-- Logo (Text-based) -->
            <div class="flex items-center gap-2">
                <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-blue-600 text-white shadow-none ">
                    <Store class="h-5 w-5" />
                </div>
                <span class="text-xl font-extrabold tracking-tight text-slate-900 dark:text-white">
                    {{ $page.props.name }}
                </span>
            </div>

            <!-- Desktop Nav -->
            <div class="hidden items-center gap-8 md:flex">
                <a href="#features"
                    class="text-sm font-semibold text-slate-600 hover:text-blue-600 dark:text-slate-400 dark:hover:text-blue-400 transition-colors">Fitur</a>
                <a href="#solutions"
                    class="text-sm font-semibold text-slate-600 hover:text-blue-600 dark:text-slate-400 dark:hover:text-blue-400 transition-colors">Solusi</a>
                <a href="#pricing"
                    class="text-sm font-semibold text-slate-600 hover:text-blue-600 dark:text-slate-400 dark:hover:text-blue-400 transition-colors">Price</a>
            </div>

            <!-- Auth Actions -->
            <div class="flex items-center gap-3">
                <template v-if="$page.props.auth.user">
                    <Link :href="dashboard()">
                        <Button rounded="full" class="bg-blue-600 font-bold hover:bg-blue-700">
                            Dashboard
                        </Button>
                    </Link>
                </template>
                <template v-else>
                    <Link :href="login()">
                        <Button variant="ghost" class="font-bold text-slate-600 dark:text-slate-400">Masuk</Button>
                    </Link>
                    <Link v-if="canRegister" :href="register()">
                        <Button rounded="full"
                            class="bg-blue-600 font-bold hover:bg-blue-700 shadow-none px-6">Daftar</Button>
                    </Link>
                </template>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section
        class="relative z-10 mx-auto flex max-w-7xl flex-col items-center px-4 pt-24 pb-32 text-center sm:px-6 lg:px-8 lg:pt-36">
        <!-- Floating Elements (Desktop Only) -->
        <div class="absolute inset-0 pointer-events-none hidden lg:block overflow-hidden">
            <div class="absolute left-[7%] top-[20%] animate-bounce-slow">
                <FloatingReceipt />
            </div>
            <div class="absolute right-[8%] top-[15%] animate-bounce-slow [animation-delay:1.5s]">
                <FloatingStock />
            </div>
            <div class="absolute left-[10%] bottom-[20%] animate-bounce-slow [animation-delay:2.5s]">
                <FloatingProfit />
            </div>
            <!-- 100+ Integrations Badge -->
            <div class="absolute right-[12%] bottom-[25%] animate-bounce-slow [animation-delay:0.8s]">
                <div
                    class="flex w-44 flex-col gap-2.5 rounded-xl bg-white p-4 shadow-none ring-1 dark:bg-slate-900 border border-slate-200">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-extrabold uppercase tracking-widest text-slate-400">Integrasi</span>
                        <span class="text-xs font-bold text-blue-600">100+</span>
                    </div>
                    <div class="flex gap-2">
                        <div class="h-7 w-7 rounded-lg bg-blue-50 dark:bg-slate-800 flex items-center justify-center">
                            <div class="h-3 w-3 rounded-full bg-blue-200 dark:bg-blue-900" />
                        </div>
                        <div class="h-7 w-7 rounded-lg bg-slate-50 dark:bg-slate-800 flex items-center justify-center">
                            <div class="h-3 w-3 rounded-full bg-slate-200 dark:bg-slate-700" />
                        </div>
                        <div class="h-7 w-7 rounded-lg bg-slate-50 dark:bg-slate-800 flex items-center justify-center">
                            <div class="h-3 w-3 rounded-full bg-slate-200 dark:bg-slate-700" />
                        </div>
                        <div class="h-7 w-7 rounded-lg bg-indigo-50 dark:bg-slate-800 flex items-center justify-center">
                            <div class="h-3 w-3 rounded-full bg-indigo-200 dark:bg-indigo-900" />
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Hero Content -->
        <div class="">
            <div
                class="mb-8 inline-flex items-center gap-2 rounded-full border border-blue-200 bg-blue-50/50 px-4 py-1.5 text-xs font-bold text-blue-700 dark:border-blue-900/50 dark:bg-blue-950/50 dark:text-blue-400 animate-in fade-in slide-in-from-top-4 duration-1000">
                <span class="relative flex h-2 w-2">
                    <span
                        class="animate-ping absolute inline-flex h-full w-full rounded-full bg-blue-400 opacity-75"></span>
                    <span class="relative inline-flex rounded-full h-2 w-2 bg-blue-600"></span>
                </span>
                Sistem Operasional Warung Modern #1
            </div>
            <h1
                class="mb-8 text-5xl font-black tracking-tight text-slate-900 sm:text-7xl lg:text-8xl dark:text-white animate-in fade-in slide-in-from-bottom-6 duration-1000 delay-100 italic">
                Think, plan, <br />and track.
            </h1>
            <p
                class="mb-12 text-xl leading-8 text-slate-600 dark:text-slate-400 animate-in fade-in slide-in-from-bottom-6 duration-1000 delay-200 max-w-2xl mx-auto">
                Kelola stok, kasir, hingga productsi dalam satu platform cloud <br class="hidden md:block" />
                yang dirancang khusus untuk kemajuan bisnis menengah ke bawah.
            </p>
            <div
                class="flex flex-col items-center justify-center gap-4 sm:flex-row animate-in fade-in slide-in-from-bottom-6 duration-1000 delay-300">
                <Link :href="register()" v-if="canRegister">
                    <Button size="lg" rounded="full"
                        class="h-16 bg-blue-600 px-12 text-lg font-bold shadow-none hover:bg-blue-700 transition-all hover:scale-105 active:scale-95">
                        Coba Demo Gratis
                    </Button>
                </Link>
                <Link :href="login()">
                    <Button variant="outline" size="lg" rounded="full"
                        class="h-16 border-slate-200 bg-white px-12 text-lg font-bold dark:border-slate-800 dark:bg-slate-950 hover:bg-slate-50 transition-all">
                        Hubungi Kami
                    </Button>
                </Link>
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section id="features" class="relative z-10 mx-auto max-w-7xl px-4 py-24 sm:px-6 lg:px-8">
        <div class="mb-16 text-center">
            <h2 class="text-3xl font-black text-slate-900 dark:text-white sm:text-4xl">Fitur Unggulan</h2>
            <p class="mt-4 text-slate-600 dark:text-slate-400">Semua yang Anda butuhkan untuk mengelola warung ada di
                sini.
            </p>
        </div>
        <div class="grid grid-cols-1 gap-10 sm:grid-cols-2 lg:grid-cols-4">
            <!-- Feature 1 -->
            <div
                class="group relative rounded-xl border border-slate-200 bg-white p-10 transition-all hover:-translate-y-2 hover:border-blue-500/50 hover:shadow-none hover: dark:border-slate-800 dark:bg-slate-900">
                <div
                    class="mb-8 flex h-14 w-14 items-center justify-center rounded-xl bg-blue-50 text-blue-600 group-hover:rotate-12 transition-transform dark:bg-blue-900/30 dark:text-blue-400">
                    <Store class="h-7 w-7" />
                </div>
                <h3 class="mb-3 text-xl font-extrabold text-slate-900 dark:text-white">POS Kasir</h3>
                <p class="text-sm leading-7 text-slate-600 dark:text-slate-400 italic">Transaksi cepat dengan sekali
                    sentuh,
                    support cetak struk Bluetooth.</p>
            </div>

            <!-- Feature 2 -->
            <div
                class="group relative rounded-xl border border-slate-200 bg-white p-10 transition-all hover:-translate-y-2 hover:border-indigo-500/50 hover:shadow-none hover: dark:border-slate-800 dark:bg-slate-900">
                <div
                    class="mb-8 flex h-14 w-14 items-center justify-center rounded-xl bg-indigo-50 text-indigo-600 group-hover:rotate-12 transition-transform dark:bg-indigo-900/30 dark:text-indigo-400">
                    <Package class="h-7 w-7" />
                </div>
                <h3 class="mb-3 text-xl font-extrabold text-slate-900 dark:text-white">Inventaris</h3>
                <p class="text-sm leading-7 text-slate-600 dark:text-slate-400 italic">Manajemen stok pintar dengan
                    peringatan otomatis saat barang menipis.</p>
            </div>

            <!-- Feature 3 -->
            <div
                class="group relative rounded-xl border border-slate-200 bg-white p-10 transition-all hover:-translate-y-2 hover:border-emerald-500/50 hover:shadow-none hover: dark:border-slate-800 dark:bg-slate-900">
                <div
                    class="mb-8 flex h-14 w-14 items-center justify-center rounded-xl bg-emerald-50 text-emerald-600 group-hover:rotate-12 transition-transform dark:bg-emerald-900/30 dark:text-emerald-400">
                    <ChefHat class="h-7 w-7" />
                </div>
                <h3 class="mb-3 text-xl font-extrabold text-slate-900 dark:text-white">Productsi</h3>
                <p class="text-sm leading-7 text-slate-600 dark:text-slate-400 italic">Kelola Bill of Materials untuk
                    product
                    rakitan atau olahan makanan.</p>
            </div>

            <!-- Feature 4 -->
            <div
                class="group relative rounded-xl border border-slate-200 bg-white p-10 transition-all hover:-translate-y-2 hover:border-orange-500/50 hover:shadow-none hover: dark:border-slate-800 dark:bg-slate-900">
                <div
                    class="mb-8 flex h-14 w-14 items-center justify-center rounded-xl bg-orange-50 text-orange-600 group-hover:rotate-12 transition-transform dark:bg-orange-900/30 dark:text-orange-400">
                    <BarChart3 class="h-7 w-7" />
                </div>
                <h3 class="mb-3 text-xl font-extrabold text-slate-900 dark:text-white">Keuangan</h3>
                <p class="text-sm leading-7 text-slate-600 dark:text-slate-400 italic">Laporan laba rugi, jurnal
                    otomatis,
                    dan rekonsiliasi kas harian.</p>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="border-t border-slate-100 bg-white py-20 dark:border-slate-800 dark:bg-slate-950">
        <div class="mx-auto max-w-7xl px-4 text-center sm:px-6 lg:px-8">
            <div class="flex items-center justify-center gap-2 mb-8">
                <span class="text-2xl font-black tracking-tighter text-slate-900 dark:text-white">
                    {{ $page.props.name }}
                </span>
            </div>
            <div
                class="mb-10 flex flex-wrap justify-center gap-x-12 gap-y-4 text-sm font-bold text-slate-500 dark:text-slate-400 uppercase tracking-widest">
                <a href="#" class="hover:text-blue-600">Fitur</a>
                <a href="#" class="hover:text-blue-600">Tentang Kami</a>
                <a href="#" class="hover:text-blue-600">Keamanan</a>
                <a href="#" class="hover:text-blue-600">Kontak</a>
            </div>
            <p class="text-xs text-slate-400 uppercase tracking-widest">© 2026 Warung ERP. Made with passion for MSMEs
                in
                Indonesia.</p>
        </div>
    </footer>
</div>
</template>

<style scoped>
@keyframes bounce-slow {

    0%,
    100% {
        transform: translateY(0) rotate(0);
    }

    50% {
        transform: translateY(-20px) rotate(2deg);
    }
}

.animate-bounce-slow {
    animation: bounce-slow 6s ease-in-out infinite;
}
</style>

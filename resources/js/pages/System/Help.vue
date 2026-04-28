<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { 
    Search, 
    BookOpen, 
    Zap, 
    ShieldCheck, 
    Package, 
    ShoppingCart, 
    BarChart3, 
    History,
    HelpCircle,
    ChevronRight,
    PlayCircle,
    FileText,
    MessageCircle,
    ArrowRight,
    Building2
} from 'lucide-vue-next';
import { ref, computed } from 'vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { Input } from '@/components/ui/input';
import { Card } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import type { BreadcrumbItem } from '@/types';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Bantuan & Dokumentasi', href: '#' },
];

const searchQuery = ref('');

const categories = [
    {
        title: 'Manajemen Stok',
        description: 'Pelajari cara mengelola inventaris, batch, dan kadaluarsa.',
        icon: Package,
        color: 'bg-blue-50 text-blue-600',
        articles: [
            { title: 'Cara aktivasi pelacakan Batch/Expiry', slug: 'batch-expiry-activation' },
            { title: 'Memahami logika FEFO (First Expired First Out)', slug: 'fefo-logic' },
            { title: 'Panduan Stock Opname berkala', slug: 'stock-opname-guide' },
            { title: 'Melakukan Stock Transfer antar gudang', slug: 'stock-transfer' }
        ]
    },
    {
        title: 'Penjualan & POS',
        description: 'Panduan lengkap transaksi kasir dan laporan harian.',
        icon: ShoppingCart,
        color: 'bg-emerald-50 text-emerald-600',
        articles: [
            { title: 'Membuat transaksi di halaman POS', slug: 'pos-transaction' },
            { title: 'Membatalkan (Void) transaksi penjualan', slug: 'void-sale' },
            { title: 'Menerbitkan Credit Note (Retur)', slug: 'credit-note' },
            { title: 'Melihat laporan margin harian', slug: 'margin-report' }
        ]
    },
    {
        title: 'Produksi & Resep',
        description: 'Kelola Bill of Materials dan alur kerja produksi.',
        icon: Zap,
        color: 'bg-orange-50 text-orange-600',
        articles: [
            { title: 'Cara membuat Resep (BOM) produk', slug: 'bom-creation' },
            { title: 'Memulai dan memantau status produksi', slug: 'production-flow' },
            { title: 'Menghitung biaya overhead produksi', slug: 'overhead-calc' },
            { title: 'Konversi satuan bahan baku ke produk jadi', slug: 'unit-conversion' }
        ]
    },
    {
        title: 'Akuntansi & Keuangan',
        description: 'Memahami jurnal, buku besar, dan audit log.',
        icon: BarChart3,
        color: 'bg-indigo-50 text-indigo-600',
        articles: [
            { title: 'Melihat Jurnal Transaksi otomatis', slug: 'auto-journal' },
            { title: 'Memahami Trial Balance & Profit Loss', slug: 'financial-reports' },
            { title: 'Cara mengunci periode (Period Lock)', slug: 'period-lock' },
            { title: 'Melacak perubahan melalui Audit Log', slug: 'audit-log' }
        ]
    },
    {
        title: 'Manajemen Aset Tetap',
        description: 'Kelola inventaris aset, nilai residu, dan penyusutan otomatis.',
        icon: Building2,
        color: 'bg-peach-50 text-peach-600',
        articles: [
            { title: 'Cara registrasi aset tetap baru', slug: 'fixed-asset-registration' },
            { title: 'Memahami tarif & metode penyusutan', slug: 'depreciation-rates' },
            { title: 'Posting beban penyusutan bulanan', slug: 'monthly-depreciation-posting' },
            { title: 'Manajemen nilai residu (Salvage Value)', slug: 'salvage-value-guide' }
        ]
    }
];

const filteredCategories = computed(() => {
    if (!searchQuery.value) return categories;
    const query = searchQuery.value.toLowerCase();
    return categories.map(c => ({
        ...c,
        articles: c.articles.filter(a => a.title.toLowerCase().includes(query))
    })).filter(c => 
        c.title.toLowerCase().includes(query) || 
        c.description.toLowerCase().includes(query) ||
        c.articles.length > 0
    );
});
</script>

<template>
    <Head title="Pusat Bantuan" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="px-6 py-12 bg-slate-50 min-h-[calc(100vh-64px)] flex flex-col gap-12 font-sans">
            <!-- Hero Section -->
            <div class="max-w-4xl mx-auto w-full text-center space-y-6">
                <Badge variant="outline" class="bg-white px-3 py-1 rounded-full border-slate-200 text-primary font-bold">
                    Warung ERP Support Center
                </Badge>
                <h1 class="text-4xl md:text-5xl font-black tracking-tight text-slate-900 italic">
                    Apa yang bisa kami bantu hari ini?
                </h1>
                <p class="text-lg text-slate-500 max-w-2xl mx-auto">
                    Cari panduan penggunaan, tips efisiensi operasional, atau pelajari fitur-fitur baru di Warung ERP.
                </p>
                <div class="relative max-w-2xl mx-auto pt-4">
                    <Search class="absolute left-4 top-1/2 -translate-y-1/2 h-5 w-5 text-slate-400" />
                    <Input 
                        v-model="searchQuery"
                        placeholder="Cari fitur, panduan, atau tutorial..." 
                        class="h-14 pl-12 pr-6 rounded-2xl border-slate-200 bg-white shadow-xl shadow-slate-200/50 text-lg focus:ring-primary/20"
                    />
                </div>
            </div>

            <!-- Categories Grid -->
            <div class="max-w-7xl mx-auto w-full grid grid-cols-1 md:grid-cols-2 gap-6">
                <Card 
                    v-for="cat in filteredCategories" 
                    :key="cat.title"
                    class="group border-slate-200 shadow-none hover:shadow-xl hover:shadow-slate-200/50 hover:border-primary/20 transition-all duration-300 rounded-3xl p-8 bg-white overflow-hidden relative"
                >
                    <div class="flex flex-col gap-6 relative z-10">
                        <div class="flex items-center justify-between">
                            <div :class="['h-14 w-14 rounded-2xl flex items-center justify-center transition-transform group-hover:scale-110', cat.color]">
                                <component :is="cat.icon" class="h-7 w-7" />
                            </div>
                            <Button variant="ghost" size="icon" class="rounded-full group-hover:bg-primary group-hover:text-white transition-colors">
                                <ArrowRight class="h-4 w-4" />
                            </Button>
                        </div>
                        
                        <div>
                            <h3 class="text-xl font-bold text-slate-900 mb-2">{{ cat.title }}</h3>
                            <p class="text-slate-500 leading-relaxed">{{ cat.description }}</p>
                        </div>

                        <div class="space-y-3 pt-2">
                            <Link 
                                v-for="article in cat.articles" 
                                :key="article.slug"
                                :href="`/help/${article.slug}`"
                                class="flex items-center gap-3 p-3 rounded-xl hover:bg-slate-50 cursor-pointer transition-colors group/item"
                            >
                                <FileText class="h-4 w-4 text-slate-300 group-hover/item:text-primary" />
                                <span class="text-sm font-medium text-slate-600 group-hover/item:text-slate-900">{{ article.title }}</span>
                            </Link>
                        </div>
                    </div>
                    
                    <!-- Decorative background icon -->
                    <component 
                        :is="cat.icon" 
                        class="absolute -right-8 -bottom-8 h-48 w-48 text-slate-50 opacity-50 group-hover:opacity-100 group-hover:scale-110 transition-all duration-500" 
                    />
                </Card>
            </div>

            <!-- FAQ Quick Links -->
            <div class="max-w-7xl mx-auto w-full flex flex-col gap-8">
                <div class="flex items-center justify-between">
                    <h2 class="text-2xl font-bold text-slate-900">Pertanyaan Populer</h2>
                    <Button variant="link" class="text-primary font-bold">
                        Lihat Semua FAQ <ChevronRight class="ml-1 h-4 w-4" />
                    </Button>
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div class="p-6 rounded-2xl bg-white border border-slate-100 hover:border-primary/20 transition-colors cursor-pointer space-y-3">
                        <Badge class="bg-blue-100 text-blue-700 hover:bg-blue-100 border-none shadow-none">Feature</Badge>
                        <h4 class="font-bold text-slate-900">Bagaimana cara import data produk massal?</h4>
                        <p class="text-sm text-slate-500">Gunakan template Excel yang tersedia di menu Produk > Import untuk memigrasikan data stok lama Anda.</p>
                    </div>
                    <div class="p-6 rounded-2xl bg-white border border-slate-100 hover:border-primary/20 transition-colors cursor-pointer space-y-3">
                        <Badge class="bg-emerald-100 text-emerald-700 hover:bg-emerald-100 border-none shadow-none">Billing</Badge>
                        <h4 class="font-bold text-slate-900">Apakah bisa cetak struk via Bluetooth?</h4>
                        <p class="text-sm text-slate-500">Tentu! Halaman POS kami mendukung printer Bluetooth standard (ESC/POS) baik di mobile maupun desktop.</p>
                    </div>
                    <div class="p-6 rounded-2xl bg-white border border-slate-100 hover:border-primary/20 transition-colors cursor-pointer space-y-3">
                        <Badge class="bg-orange-100 text-orange-700 hover:bg-orange-100 border-none shadow-none">Security</Badge>
                        <h4 class="font-bold text-slate-900">Siapa saja yang bisa melihat Audit Log?</h4>
                        <p class="text-sm text-slate-500">Hanya user dengan role 'Admin' atau yang memiliki permission 'view reports' yang dapat memantau jejak audit.</p>
                    </div>
                </div>
            </div>

            <!-- Support Footer -->
            <Card class="max-w-7xl mx-auto w-full border-none shadow-2xl shadow-primary/5 bg-primary rounded-3xl p-10 flex flex-col md:flex-row items-center justify-between gap-8">
                <div class="space-y-2 text-center md:text-left">
                    <h3 class="text-2xl font-black text-white italic">Masih butuh bantuan?</h3>
                    <p class="text-primary-foreground/80">Tim support kami siap membantu operasional warung Anda 24/7.</p>
                </div>
                <div class="flex flex-wrap items-center justify-center gap-4">
                    <Button class="h-12 px-8 rounded-xl bg-white text-primary hover:bg-slate-100 font-bold shadow-none">
                        <MessageCircle class="mr-2 h-5 w-5" /> Chat via WhatsApp
                    </Button>
                    <Button variant="ghost" class="h-12 px-8 rounded-xl text-white hover:bg-primary-foreground/10 font-bold">
                        Hubungi Email Support
                    </Button>
                </div>
            </Card>
        </div>
    </AppLayout>
</template>

<style scoped>
@reference "../../../css/app.css";
</style>

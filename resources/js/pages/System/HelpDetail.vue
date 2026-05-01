<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import {
    ArrowLeft,
    BookOpen,
    Calendar,
    User,
    Share2,
    Printer,
    ThumbsUp,
    MessageCircle,
    Package,
    Zap,
    ShoppingCart,
    BarChart3,
    FileText,
    Building2
} from 'lucide-vue-next';
import AppLayout from '@/layouts/AppLayout.vue';
import { Button } from '@/components/ui/button';
import { Card } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import type { BreadcrumbItem } from '@/types';
import { computed } from 'vue';

const props = defineProps<{
    article: {
        title: string;
        category: string;
        content: string;
        icon: string;
    };
    slug: string;
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Pusat Bantuan', href: '/help' },
    { title: props.article.title, href: '#' },
];

const iconMap: Record<string, any> = {
    Package,
    Zap,
    ShoppingCart,
    BarChart3,
    FileText,
    Building2
};

const getIcon = (name: string) => iconMap[name] || BookOpen;

// Simple markdown-to-html like converter
const formattedContent = computed(() => {
    let lines = props.article.content.split('\n');
    let html = '';
    let inList = false;
    let listType = '';

    lines.forEach(line => {
        const trimmed = line.trim();

        // Handle Lists
        const isUnordered = trimmed.startsWith('*');
        const isOrdered = trimmed.match(/^\d\./);

        if (isUnordered || isOrdered) {
            if (!inList) {
                inList = true;
                listType = isUnordered ? 'ul' : 'ol';
                html += `<${listType} class="mb-6 space-y-2">`;
            }
            const content = isUnordered ? trimmed.substring(1).trim() : trimmed.substring(2).trim();
            html += `<li class="${isUnordered ? 'list-disc' : 'list-decimal'} ml-6 text-slate-700 leading-relaxed">${formatText(content)}</li>`;
        } else {
            if (inList) {
                html += `</${listType}>`;
                inList = false;
            }
            if (trimmed === '') {
                html += '<div class="h-4"></div>';
            } else {
                html += `<p class="mb-4 text-slate-700 leading-relaxed text-base">${formatText(line)}</p>`;
            }
        }
    });

    if (inList) html += `</${listType}>`;
    return html;
});

function formatText(text: string) {
    // Bold matching **text**
    return text.replace(/\*\*(.*?)\*\*/g, '<strong class="font-extrabold text-slate-900">$1</strong>');
}
</script>

<template>
<Head :title="article.title" />

<AppLayout :breadcrumbs="breadcrumbs">
    <div class="px-6 py-12 bg-slate-50 min-h-[calc(100vh-64px)] flex justify-center font-sans">
        <div class=" w-full flex flex-col gap-8">
            <!-- Navigation & Actions -->
            <div class="flex items-center justify-between">
                <Link href="/help">
                    <Button variant="ghost" class="text-slate-500 hover:text-primary pl-0">
                        <ArrowLeft class="mr-2 h-4 w-4" /> Kembali ke Pusat Bantuan
                    </Button>
                </Link>
                <div class="flex gap-2">
                    <Button variant="outline" size="icon" class="rounded-xl border-slate-200">
                        <Printer class="h-4 w-4" />
                    </Button>
                    <Button variant="outline" size="icon" class="rounded-xl border-slate-200">
                        <Share2 class="h-4 w-4" />
                    </Button>
                </div>
            </div>

            <!-- Article Header -->
            <div class="space-y-6">
                <div class="flex items-center gap-3">
                    <Badge variant="outline" class="bg-blue-50 text-blue-700 border-blue-200 px-3 py-1">
                        {{ article.category }}
                    </Badge>
                    <span class="text-slate-300">•</span>
                    <span class="text-xs text-slate-400 flex items-center gap-1">
                        <Calendar class="h-3 w-3" /> Diperbarui 2 hari yang lalu
                    </span>
                </div>
                <h1 class="text-3xl md:text-5xl font-black text-slate-900 leading-tight">
                    {{ article.title }}
                </h1>
                <div class="flex items-center gap-4 py-4 border-b border-slate-200">
                    <div
                        class="h-10 w-10 rounded-full bg-slate-200 flex items-center justify-center text-slate-500 overflow-hidden">
                        <User class="h-6 w-6" />
                    </div>
                    <div class="flex flex-col">
                        <span class="text-sm font-bold text-slate-900">Admin valee ERP</span>
                        <span class="text-xs text-slate-500">Pusat Dokumentasi Sistem</span>
                    </div>
                </div>
            </div>

            <!-- Article Content -->
            <Card class="border-none shadow-none bg-transparent">
                <div class="prose prose-slate max-w-none" v-html="formattedContent"></div>
            </Card>

            <!-- Feedback Section -->
            <div class="py-12 border-t border-slate-200 mt-8 flex flex-col items-center gap-6 text-center">
                <div class="space-y-2">
                    <h4 class="text-lg font-bold text-slate-900 italic">Apakah artikel ini membantu?</h4>
                    <p class="text-sm text-slate-500">Feedback Anda membantu kami meningkatkan kualitas dokumentasi.</p>
                </div>
                <div class="flex gap-4">
                    <Button variant="outline"
                        class="h-12 px-8 rounded-2xl border-slate-200 hover:bg-emerald-50 hover:text-emerald-700 hover:border-emerald-200 transition-all">
                        <ThumbsUp class="mr-2 h-5 w-5" /> Ya, Sangat Membantu
                    </Button>
                    <Button variant="outline"
                        class="h-12 px-8 rounded-2xl border-slate-200 hover:bg-rose-50 hover:text-rose-700 hover:border-rose-200 transition-all">
                        Tidak, Saya Masih Bingung
                    </Button>
                </div>
            </div>

            <!-- Footer Support -->
            <Card
                class="border-slate-200 bg-white p-8 rounded-3xl flex flex-col md:flex-row items-center gap-6 justify-between">
                <div class="flex items-center gap-6">
                    <div
                        class="h-16 w-16 rounded-2xl bg-primary/10 flex items-center justify-center text-primary shrink-0">
                        <MessageCircle class="h-8 w-8" />
                    </div>
                    <div class="space-y-1">
                        <h4 class="font-bold text-slate-900">Belum menemukan jawaban?</h4>
                        <p class="text-sm text-slate-500">Bicaralah langsung dengan tim ahli kami untuk solusi instan.
                        </p>
                    </div>
                </div>
                <Button class="h-12 px-8 rounded-xl font-bold">
                    Buka Tiket Support
                </Button>
            </Card>
        </div>
    </div>
</AppLayout>
</template>

<style scoped>
@reference "../../../css/app.css";

/* Basic reset/style for injected HTML */
:deep(p) {
    @apply text-slate-600 mb-4 leading-relaxed;
}

:deep(li) {
    @apply text-slate-600 mb-2;
}

:deep(strong) {
    @apply font-extrabold text-slate-900;
}
</style>

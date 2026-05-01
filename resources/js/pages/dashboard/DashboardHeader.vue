<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { Plus, Search } from 'lucide-vue-next';

defineProps<{
  user: { name?: string } | null;
}>();
</script>

<template>
<header class="flex items-center justify-between gap-4 font-['Plus_Jakarta_Sans',sans-serif]">
  <div class="flex items-center gap-4">
    <div class="flex items-center gap-3">
      <div
        class="h-12 w-12 rounded-full bg-slate-900 dark:bg-zinc-100 text-white dark:text-zinc-900 flex items-center justify-center font-black text-xl shadow-md tracking-tighter">
        W</div>
      <div class="leading-tight">
        <div class="text-[17px] font-extrabold text-foreground tracking-tight">valee ERP</div>
        <div class="text-[13px] font-medium text-muted-foreground -mt-0.5">Control Panel</div>
      </div>
    </div>
  </div>

  <div class="flex items-center gap-4">
    <Link href="/pos"
      class="h-12 w-12 rounded-full bg-card border border-border/50 flex items-center justify-center hover:bg-muted transition shadow-sm"
      aria-label="Add POS">
      <Plus class="h-5 w-5" />
    </Link>
    <div class="flex items-center gap-3 pr-2">
      <div
        class="h-12 w-12 rounded-full bg-primary/10 flex items-center justify-center border border-primary/20 overflow-hidden shadow-sm">
        <span class="text-xl font-extrabold text-primary">{{ user?.name?.charAt(0) ?? 'A' }}</span>
      </div>
      <div class="leading-tight hidden sm:block">
        <div class="text-[15px] font-bold text-foreground">{{ user?.name ?? 'Admin' }}</div>
        <div class="text-[12px] font-semibold text-muted-foreground uppercase tracking-widest">Administrator</div>
      </div>
    </div>
    <div class="relative hidden lg:block">
      <Search class="absolute left-4 top-1/2 -translate-y-1/2 h-4 w-4 text-muted-foreground" />
      <input type="text" placeholder="Start searching here ..."
        class="h-12 w-72 rounded-full pl-11 pr-4 bg-card border border-border/50 shadow-sm outline-none focus:ring-2 focus:ring-primary/40 focus:border-transparent text-sm font-medium transition-all placeholder:font-normal" />
    </div>
  </div>
</header>
</template>

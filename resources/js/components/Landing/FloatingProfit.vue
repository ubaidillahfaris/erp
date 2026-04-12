<script setup lang="ts">
import { TrendingUp } from 'lucide-vue-next';
</script>

<template>
  <div class="flex w-56 flex-col gap-3 rounded-xl bg-white p-4 shadow-none ring-1 dark:bg-slate-900 border border-slate-200 dark:border-slate-800 animate-in fade-in slide-in-from-bottom-10 duration-1000 delay-400">
    <div class="flex items-center gap-2">
      <div class="flex h-7 w-7 items-center justify-center rounded-lg bg-emerald-50 text-emerald-600 dark:bg-emerald-900/40 dark:text-emerald-400">
        <TrendingUp class="h-4 w-4" />
      </div>
      <span class="text-xs font-semibold text-slate-700 dark:text-slate-300">Laba Bersih</span>
    </div>
    <div class="flex items-end justify-between gap-4">
      <div class="space-y-1">
        <span class="text-xs font-medium text-slate-400">Hari ini</span>
        <div class="flex items-baseline gap-1">
          <span class="text-lg font-bold text-slate-900 dark:text-slate-100">+Rp 450K</span>
          <span class="text-xs font-bold text-emerald-500 text-sm">↑ 12%</span>
        </div>
      </div>
      <div class="flex gap-1 h-12 flex-1 items-end pt-1">
         <div class="h-1/5 w-2 flex-1 rounded-full bg-emerald-100 dark:bg-emerald-900/60" />
         <div class="h-2/5 w-2 flex-1 rounded-full bg-emerald-200 dark:bg-emerald-800/60" />
         <div class="h-3/5 w-2 flex-1 rounded-full bg-emerald-300 dark:bg-emerald-700/60" />
         <div class="h-5/5 w-2 flex-1 rounded-full bg-emerald-500 dark:bg-emerald-500/60" />
         <div class="h-4/5 w-2 flex-1 rounded-full bg-emerald-400 dark:bg-emerald-600/60" />
      </div>
    </div>
  </div>
</template>

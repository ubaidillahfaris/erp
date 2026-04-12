<script setup lang="ts">
import { Receipt } from 'lucide-vue-next';
</script>

<template>
  <div class="flex w-48 flex-col gap-3 rounded-xl bg-white p-4 shadow-none ring-1 dark:bg-slate-900 border border-slate-200 dark:border-slate-800 animate-in fade-in zoom-in duration-1000">
    <div class="flex items-center justify-between">
      <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-blue-50 text-blue-600 dark:bg-blue-900/30 dark:text-blue-400">
        <Receipt class="h-4 w-4" />
      </div>
      <span class="text-xs font-medium text-slate-400">#TRX-882</span>
    </div>
    <div class="space-y-1.5">
      <div class="h-1.5 w-16 rounded-full bg-slate-100 dark:bg-slate-800" />
      <div class="h-1.5 w-24 rounded-full bg-slate-100 dark:bg-slate-800" />
    </div>
    <div class="flex items-center justify-between pt-1 border-t border-slate-100 dark:border-slate-800">
      <span class="text-xs font-semibold text-slate-800 dark:text-slate-200">Total</span>
      <span class="text-xs font-bold text-blue-600 dark:text-blue-400">Rp 125K</span>
    </div>
  </div>
</template>

<script setup lang="ts">
import { AlertCircle } from 'lucide-vue-next';
</script>

<template>
  <div class="flex w-52 flex-col gap-2 rounded-xl bg-white p-4 shadow-none ring-1 dark:bg-slate-900 border border-slate-200 dark:border-slate-800 animate-in fade-in slide-in-from-right-10 duration-1000 delay-200">
    <div class="flex items-center gap-2">
      <div class="flex h-7 w-7 items-center justify-center rounded-full bg-red-50 text-red-500 dark:bg-red-900/40 dark:text-red-400">
        <AlertCircle class="h-4 w-4 fill-red-100 dark:fill-red-900/20" stroke-width="2.5" />
      </div>
      <span class="text-xs font-semibold text-slate-700 dark:text-slate-300">Stok Rendah</span>
    </div>
    <div class="space-y-2">
      <div class="flex items-baseline justify-between">
         <span class="text-[13px] font-medium text-slate-800 dark:text-slate-200">Gula Pasir 1kg</span>
         <span class="text-xs font-bold text-red-600 dark:text-red-400">2 pcs</span>
      </div>
      <div class="h-2 w-full overflow-hidden rounded-full bg-slate-100 dark:bg-slate-800">
        <div class="h-full w-1/5 rounded-full bg-red-500 dark:bg-red-400" />
      </div>
    </div>
  </div>
</template>

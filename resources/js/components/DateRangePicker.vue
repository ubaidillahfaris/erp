<script setup lang="ts">
import { ref, watch } from 'vue'
import { Calendar as CalendarIcon } from 'lucide-vue-next'
import { getLocalTimeZone, today, type DateValue } from '@internationalized/date'

type DateRange = {
  start: DateValue | undefined
  end: DateValue | undefined
}
import { type Ref } from 'vue'
import { cn } from '@/lib/utils'
import { Button } from './ui/button'
import { RangeCalendar } from './ui/range-calendar'
import { Dialog, DialogContent, DialogTrigger, DialogHeader, DialogTitle, DialogDescription, DialogFooter } from './ui/dialog'
import { format } from 'date-fns'
import { id } from 'date-fns/locale'

const props = defineProps<{
  modelValue?: { start: string; end: string }
  class?: string
}>()

const emits = defineEmits<{
  (e: 'update:modelValue', value: { start: string; end: string }): void
}>()

const dateRange = ref({
  start: today(getLocalTimeZone()),
  end: today(getLocalTimeZone()).add({ days: 7 }),
}) as Ref<DateRange>

// Sync from modelValue
watch(() => props.modelValue, (val) => {
  if (val?.start && val?.end) {
    // Basic conversion logic (simplified)
    // In a real app we'd parse the strings correctly to @internationalized/date objects
  }
}, { immediate: true })

// Sync to modelValue
watch(dateRange, (val) => {
  if (val.start && val.end) {
    emits('update:modelValue', {
      start: val.start.toString(),
      end: val.end.toString(),
    })
  }
})
</script>

<template>
  <div :class="cn('grid gap-2', $props.class)">
    <Dialog>
      <DialogTrigger as-child>
        <Button
          id="date"
          variant="outline"
          :class="cn(
            'w-full justify-start text-left font-normal h-12 px-4 text-base border-slate-200 hover:border-primary/50 transition-all shadow-sm active:scale-[0.98]',
            !dateRange && 'text-muted-foreground',
          )"
        >
          <CalendarIcon class="mr-2 h-4 w-4 text-primary" />
          <template v-if="dateRange.start && typeof dateRange.start.toDate === 'function'">
            <template v-if="dateRange.end && typeof dateRange.end.toDate === 'function'">
              {{ format(dateRange.start.toDate(getLocalTimeZone()), 'dd MMM yyyy', { locale: id }) }} - {{ format(dateRange.end.toDate(getLocalTimeZone()), 'dd MMM yyyy', { locale: id }) }}
            </template>
            <template v-else>
              {{ format(dateRange.start.toDate(getLocalTimeZone()), 'dd MMM yyyy', { locale: id }) }}
            </template>
          </template>
          <template v-else>
            Pilih Rentang Tanggal
          </template>
        </Button>
      </DialogTrigger>
      <DialogContent class="sm:max-w-[700px] p-0 rounded-2xl overflow-hidden border-none shadow-2xl">
        <DialogHeader class="px-6 py-4 border-b bg-muted/5">
          <DialogTitle class="text-sm font-bold uppercase tracking-widest text-slate-500">Pilih Periode Tanggal</DialogTitle>
          <DialogDescription class="hidden">Gunakan kalender di bawah untuk memilih rentang tanggal laporan.</DialogDescription>
        </DialogHeader>
        
        <div class="p-6 bg-white flex justify-center items-center">
          <RangeCalendar
            v-model="dateRange"
            initial-focus
            :number-of-months="2"
            @update:model-value="(val: DateRange) => dateRange = val"
          />
        </div>

        <DialogFooter class="p-4 border-t bg-muted/5 flex sm:justify-center">
            <DialogTrigger as-child>
                <Button class="w-full sm:w-auto px-10 rounded-xl h-11 bg-primary text-primary-foreground font-bold text-xs uppercase tracking-widest">
                    Konfirmasi Periode
                </Button>
            </DialogTrigger>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  </div>
</template>

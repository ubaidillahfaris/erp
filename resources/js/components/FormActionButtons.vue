<script setup lang="ts">
import { Loader2, Save, Plus } from 'lucide-vue-next';
import { Button } from '@/components/ui/button';

defineProps<{
    processing: boolean;
    showAddAnother?: boolean;
    showCancel?: boolean;
}>();

const emit = defineEmits<{
    (e: 'save'): void;
    (e: 'saveAndAddAnother'): void;
    (e: 'cancel'): void;
}>();
</script>

<template>
<div class="flex flex-wrap items-center justify-end gap-3 pt-4 border-t">
    <!-- Action: Batal -->
    <Button v-if="showCancel" type="button" variant="ghost" @click="$emit('cancel')">
        Batal
    </Button>
    <!-- Action: Simpan & Tutup (Default) -->
    <Button type="submit" :disabled="processing" @click="$emit('save')" class="btn-primary min-w-[120px]">
        <template v-if="processing">
            <Loader2 class="mr-2 h-4 w-4 animate-spin" />
            Menyimpan...
        </template>
        <template v-else>
            <Save class="mr-2 h-4 w-4" />
            Simpan
        </template>
    </Button>

    <!-- Action: Simpan & Tambah Lagi -->
    <Button v-if="showAddAnother" type="button" :disabled="processing" @click="$emit('saveAndAddAnother')"
        variant="outline" class="min-w-[160px] border-primary text-primary hover:bg-primary/5 shadow-none ">
        <template v-if="processing">
            <Loader2 class="mr-2 h-4 w-4 animate-spin" />
            Proses...
        </template>
        <template v-else>
            <Plus class="mr-2 h-4 w-4" />
            Simpan & Tambah Lagi
        </template>
    </Button>
</div>
</template>

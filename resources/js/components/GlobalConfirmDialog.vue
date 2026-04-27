<script setup lang="ts">
import {
    AlertDialog,
    AlertDialogContent,
    AlertDialogDescription,
    AlertDialogFooter,
    AlertDialogHeader,
    AlertDialogTitle,
} from '@/components/ui/alert-dialog'
import { Button } from '@/components/ui/button'
import { useConfirm } from '@/composables/useConfirm'

const { isOpen, title, description, handleConfirm, handleCancel, isConfirming } = useConfirm()

const onOpenChange = (open: boolean) => {
    if (!open) {
        handleCancel()
    }
}
</script>

<template>
    <AlertDialog :open="isOpen" @update:open="onOpenChange">
        <AlertDialogContent>
            <AlertDialogHeader>
                <AlertDialogTitle>{{ title }}</AlertDialogTitle>
                <AlertDialogDescription v-if="description">
                    {{ description }}
                </AlertDialogDescription>
            </AlertDialogHeader>
            <AlertDialogFooter class="flex w-full items-center gap-3 sm:justify-between">
                <Button variant="outline" class="w-full sm:w-1/2 mt-2 sm:mt-0" @click="handleCancel" :disabled="isConfirming">Cancel</Button>
                <Button class="w-full sm:w-1/2" @click="handleConfirm" :disabled="isConfirming">Ya, Lanjutkan</Button>
            </AlertDialogFooter>
        </AlertDialogContent>
    </AlertDialog>
</template>

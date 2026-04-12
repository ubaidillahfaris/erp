<script setup lang="ts">
import { ref, watch, onMounted, onUnmounted } from 'vue';
import { UploadCloud, X, FileIcon, ImageIcon } from 'lucide-vue-next';

const props = defineProps<{
    modelValue: File[];
    maxSizeMB?: number;
    accept?: string;
}>();

const emit = defineEmits<{
    (e: 'update:modelValue', files: File[]): void;
    (e: 'error', message: string): void;
}>();

const isDragging = ref(false);
const fileInput = ref<HTMLInputElement | null>(null);

const maxSize = (props.maxSizeMB || 20) * 1024 * 1024; // Default 20MB

const handleDragOver = (e: DragEvent) => {
    e.preventDefault();
    isDragging.value = true;
};

const handleDragLeave = (e: DragEvent) => {
    e.preventDefault();
    isDragging.value = false;
};

const validateAndAddFiles = (newFiles: FileList | File[]) => {
    const validFiles: File[] = [];
    let hasError = false;

    Array.from(newFiles).forEach(file => {
        if (file.size > maxSize) {
            emit('error', `Filenya terlalu besar bos: ${file.name} (Max ${props.maxSizeMB || 20}MB)`);
            hasError = true;
        } else {
            validFiles.push(file);
        }
    });

    if (validFiles.length > 0) {
        emit('update:modelValue', [...props.modelValue, ...validFiles]);
    }
};

const handleDrop = (e: DragEvent) => {
    e.preventDefault();
    isDragging.value = false;
    if (e.dataTransfer?.files) {
        validateAndAddFiles(e.dataTransfer.files);
    }
};

const handleFileInput = (e: Event) => {
    const target = e.target as HTMLInputElement;
    if (target.files) {
        validateAndAddFiles(target.files);
    }
    // Reset input so same file can be selected again if removed
    if (target) target.value = '';
};

const removeFile = (index: number) => {
    const newFiles = [...props.modelValue];
    newFiles.splice(index, 1);
    emit('update:modelValue', newFiles);
};

const formatSize = (bytes: number) => {
    if (bytes === 0) return '0 B';
    const k = 1024;
    const sizes = ['B', 'KB', 'MB', 'GB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return parseFloat((bytes / Math.pow(k, i)).toFixed(1)) + ' ' + sizes[i];
};

const triggerInput = () => {
    fileInput.value?.click();
};
</script>

<template>
    <div class="flex flex-col gap-4">
        <div
            @dragover="handleDragOver"
            @dragleave="handleDragLeave"
            @drop="handleDrop"
            @click="triggerInput"
            :class="[
                'border-2 border-dashed rounded-lg p-8 flex flex-col items-center justify-center gap-3 cursor-pointer transition-all duration-200',
                isDragging ? 'border-primary bg-primary/5' : 'border-input hover:border-primary/50 hover:bg-slate-50'
            ]"
        >
            <input 
                type="file" 
                ref="fileInput" 
                class="hidden" 
                multiple 
                :accept="accept"
                @change="handleFileInput" 
            />
            
            <div class="h-12 w-12 rounded-full bg-secondary flex items-center justify-center">
                <UploadCloud class="h-6 w-6 text-muted-foreground" />
            </div>
            
            <div class="text-center">
                <p class="text-sm font-semibold text-foreground mb-1">Upload Bukti Transaksi, Invoice, dsb</p>
                <p class="text-xs text-muted-foreground">Seret dan lepas file di sini, atau klik untuk memilih file</p>
            </div>
        </div>

        <!-- File List -->
        <div v-if="modelValue.length > 0" class="flex flex-col gap-2">
            <div 
                v-for="(file, index) in modelValue" 
                :key="file.name + index"
                class="flex items-center justify-between p-3 border border-input rounded-md bg-white group"
            >
                <div class="flex items-center gap-3 overflow-hidden">
                    <div class="h-10 w-10 shrink-0 bg-secondary rounded flex items-center justify-center">
                        <ImageIcon v-if="file.type.startsWith('image/')" class="h-5 w-5 text-muted-foreground" />
                        <FileIcon v-else class="h-5 w-5 text-muted-foreground" />
                    </div>
                    <div class="flex flex-col truncate">
                        <span class="text-sm font-medium text-foreground truncate">{{ file.name }}</span>
                        <span class="text-xs text-muted-foreground">{{ formatSize(file.size) }}</span>
                    </div>
                </div>
                
                <button 
                    type="button" 
                    @click.stop="removeFile(index)"
                    class="h-8 w-8 shrink-0 flex items-center justify-center rounded hover:bg-destructive/10 hover:text-destructive text-muted-foreground transition-colors"
                >
                    <X class="h-4 w-4" />
                </button>
            </div>
        </div>
    </div>
</template>

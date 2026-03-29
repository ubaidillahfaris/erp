import { ref } from 'vue';

const isOpen = ref(false);
const title = ref('');
const description = ref('');
const isConfirming = ref(false);
let resolvePromise: ((value: boolean) => void) | null = null;

export function useConfirm() {
    const confirmDialog = (msgTitle: string, msgDescription: string = '') => {
        title.value = msgTitle;
        description.value = msgDescription;
        isConfirming.value = false;
        isOpen.value = true;

        return new Promise<boolean>((resolve) => {
            resolvePromise = resolve;
        });
    };

    const handleConfirm = async () => {
        if (!resolvePromise) return;
        
        isConfirming.value = true;
        
        // Simpan referensi dan null-kan agar tidak double resolve
        const resolver = resolvePromise;
        resolvePromise = null;
        
        resolver(true);
        isOpen.value = false;
        isConfirming.value = false;
    };

    const handleCancel = () => {
        if (!resolvePromise) return;
        
        // Simpan referensi dan null-kan agar tidak double resolve
        const resolver = resolvePromise;
        resolvePromise = null;
        
        resolver(false);
        isOpen.value = false;
        isConfirming.value = false;
    };

    return {
        isOpen,
        title,
        description,
        isConfirming,
        confirmDialog,
        handleConfirm,
        handleCancel,
    };
}

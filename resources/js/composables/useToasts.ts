import { usePage } from '@inertiajs/vue3';
import { watch } from 'vue';
import { toast } from 'vue-sonner';

export function useToasts() {
    const page = usePage();

    watch(
        () => page.props.flash,
        (flash: any) => {
            if (flash?.success) {
                toast.success(flash.success);
            }
            if (flash?.error) {
                toast.error(flash.error);
            }
        },
        { deep: true, immediate: true }
    );
}

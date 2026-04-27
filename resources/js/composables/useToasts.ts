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

    watch(
        () => page.props.errors,
        (errors: any) => {
            if (errors?.period_lock) {
                toast.error(errors.period_lock, {
                    duration: 5000,
                    id: 'period-lock-error'
                });
            }
        },
        { deep: true, immediate: true }
    );
}

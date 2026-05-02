<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import AppLogoIcon from '@/components/AppLogoIcon.vue';
import { home } from '@/routes';

defineProps<{
    title?: string;
    description?: string;
}>();
</script>

<template>
<div
    class="relative flex min-h-screen flex-col items-center justify-center bg-hero p-6 font-sans selection:bg-primary/30 text-foreground md:p-10 overflow-hidden">
    
    <!-- Glow Accents -->
    <div class="absolute -top-32 -right-32 h-96 w-96 rounded-full bg-lavender/30 blur-[100px] pointer-events-none animate-float-slow" />
    <div class="absolute bottom-0 left-1/3 h-72 w-72 rounded-full bg-peach/20 blur-[100px] pointer-events-none animate-float-slower" />

    <div class="relative z-10 w-full max-w-sm py-4">
        <div class="flex flex-col gap-6">
            <div class="flex flex-col items-center gap-4">
                <!-- Logo Section -->
                <Link :href="home()"
                    class="group flex items-center gap-3 transition-transform hover:scale-105 active:scale-95">
                    <div class="h-9 w-9 rounded-full bg-foreground flex items-center justify-center">
                        <div class="h-3.5 w-3.5 rounded-full bg-background" />
                    </div>
                    <span class="text-3xl font-semibold tracking-tighter text-foreground">
                        Valee
                    </span>
                </Link>

                <!-- Header Content -->
                <div class="space-y-1 text-center">
                    <h1 class="text-2xl font-semibold tracking-tighter text-foreground">{{ title }}</h1>
                    <p class="text-sm font-medium text-muted-foreground">
                        {{ description }}
                    </p>
                </div>
            </div>

            <!-- Form Area -->
            <div class="rounded-[2rem] border border-foreground/5 bg-white/40 backdrop-blur-xl p-7 shadow-soft">
                <slot />
            </div>
        </div>
    </div>

    <!-- Footer Info -->
    <div class="relative z-10 mt-6 text-center">
        <p class="text-[10px] font-medium uppercase tracking-widest text-muted-foreground/50">© 2026 {{ $page.props.name }}</p>
    </div>
</div>
</template>

<style scoped>
@keyframes float {
  0%, 100% { transform: translate(0, 0); }
  33% { transform: translate(30px, -20px); }
  66% { transform: translate(-20px, 30px); }
}

@keyframes float-reverse {
  0%, 100% { transform: translate(0, 0); }
  33% { transform: translate(-30px, 20px); }
  66% { transform: translate(20px, -30px); }
}

.animate-float-slow {
  animation: float 18s ease-in-out infinite;
}

.animate-float-slower {
  animation: float-reverse 25s ease-in-out infinite;
}
</style>

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
    class="relative flex min-h-screen flex-col items-center justify-center bg-[hsl(220_18%_8%)] p-6 font-sans selection:bg-primary/30 text-[hsl(30_20%_96%)] md:p-10 overflow-hidden">
    
    <!-- Glow Accents -->
    <div class="absolute -top-32 -right-32 h-96 w-96 rounded-full bg-primary/10 blur-3xl pointer-events-none animate-float-slow" />
    <div class="absolute bottom-0 left-1/3 h-72 w-72 rounded-full bg-primary/5 blur-3xl pointer-events-none animate-float-slower" />

    <div class="relative z-10 w-full max-w-sm py-10">
        <div class="flex flex-col gap-8">
            <div class="flex flex-col items-center gap-6">
                <!-- Logo Section -->
                <Link :href="home()"
                    class="group flex flex-col items-center gap-3 transition-transform hover:scale-105 active:scale-95">
                    <div class="h-12 w-12 rounded-2xl bg-gradient-coral flex items-center justify-center text-white shadow-coral">
                        <AppLogoIcon class="size-6" />
                    </div>
                    <span class="text-2xl font-black tracking-tighter text-white">
                        {{ $page.props.name }}
                    </span>
                </Link>

                <!-- Header Content -->
                <div class="space-y-2 text-center">
                    <h1 class="text-2xl font-black tracking-tight text-white">{{ title }}</h1>
                    <p class="text-sm font-medium text-white/50">
                        {{ description }}
                    </p>
                </div>
            </div>

            <!-- Form Area -->
            <div class="rounded-3xl border border-white/10 bg-white/[0.04] backdrop-blur-md p-8 shadow-2xl">
                <slot />
            </div>
        </div>
    </div>

    <!-- Footer Info -->
    <div class="relative z-10 mt-12 text-center">
        <p class="text-xs font-bold uppercase tracking-widest text-white/30">© 2026 {{ $page.props.name }}</p>
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

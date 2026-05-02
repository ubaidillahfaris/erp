<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { login, register, dashboard } from '@/routes';
import { ArrowRight } from 'lucide-vue-next';
import { Button } from '@/components/ui/button';
import Navbar from '@/components/Landing/Navbar.vue';
import FloatingMockups from '@/components/Landing/FloatingMockups.vue';

defineProps<{
  canRegister: boolean;
}>();
</script>

<template>
  <Head :title="`${$page.props.name || 'TrueCaller'} - Amplify Customer Engagement`" />

  <main class="min-h-screen bg-background font-sans selection:bg-primary/30">
    <div class="mx-4 md:mx-8 my-4 md:my-6 rounded-[2.5rem] bg-hero overflow-hidden shadow-soft border border-border/50">
      <Navbar />
      
      <section class="px-6 md:px-10 pt-10 md:pt-16 pb-12 text-center relative">
        <!-- Background decorative elements -->
        <div class="absolute -top-24 left-1/4 h-64 w-64 bg-lavender/30 blur-[100px] pointer-events-none rounded-full" />
        <div class="absolute bottom-0 right-1/4 h-80 w-80 bg-peach/20 blur-[120px] pointer-events-none rounded-full" />
        
        <div class="relative z-10">
          <h1 class="mx-auto max-w-4xl text-4xl md:text-6xl font-semibold tracking-tighter text-foreground leading-[1.1] mb-6 animate-fade-in-up">
            Amplify Your Customer<br />
            <span class="text-primary italic">Engagement</span> Efforts
          </h1>
          
          <p class="mx-auto mt-6 max-w-xl text-base md:text-lg text-muted-foreground leading-relaxed animate-fade-in-up [animation-delay:200ms]">
            True CallerID protects your brand, boosts contact rates and improves experiences.
          </p>
          
          <div class="mt-12 flex flex-col sm:flex-row items-center justify-center gap-4 animate-fade-in-up [animation-delay:400ms]">
            <Link :href="register()" class="w-full sm:w-auto">
              <Button class="w-full sm:w-auto rounded-full bg-gradient-coral hover:opacity-90 text-white h-12 px-8 text-base font-semibold transition-all hover:scale-[1.02] active:scale-95 shadow-coral border-none">
                Book a Demo <ArrowRight class="ml-2 h-4 w-4" />
              </Button>
            </Link>
            
            <Link :href="login()" class="w-full sm:w-auto">
              <Button variant="outline" class="w-full sm:w-auto rounded-full bg-white/40 backdrop-blur-md border-foreground/5 hover:bg-white/60 text-foreground h-12 px-8 text-base font-semibold transition-all hover:scale-[1.02] active:scale-95">
                Connect with us
              </Button>
            </Link>
          </div>
        </div>

        <FloatingMockups class="animate-fade-in-up [animation-delay:600ms]" />
      </section>
    </div>

    <!-- Features Section -->
    <section class="max-w-7xl mx-auto px-6 py-24">
      <div class="grid md:grid-cols-3 gap-12">
        <div v-for="f in [
          { title: 'Branded Caller ID', desc: 'Display your name and logo on every call to increase trust.' },
          { title: 'Spam Protection', desc: 'Identify and block fraudulent calls before they reach your customers.' },
          { title: 'Rich Analytics', desc: 'Get deep insights into your communication performance and reach.' }
        ]" :key="f.title" class="space-y-5">
          <div class="h-12 w-12 rounded-2xl bg-primary/10 flex items-center justify-center text-primary">
            <Sparkles class="h-6 w-6" />
          </div>
          <h3 class="text-3xl font-semibold tracking-tight">{{ f.title }}</h3>
          <p class="text-muted-foreground text-lg leading-relaxed">{{ f.desc }}</p>
        </div>
      </div>
    </section>

    <!-- Footer -->
    <footer class="border-t border-border mt-12 py-12 bg-surface/30">
      <div class="max-w-7xl mx-auto px-6 flex flex-col md:flex-row justify-between items-center gap-8">
        <div class="flex items-center gap-3">
          <div class="h-7 w-7 rounded-full bg-foreground flex items-center justify-center">
            <div class="h-3 w-3 rounded-full bg-background" />
          </div>
          <span class="font-semibold text-2xl tracking-tighter">Valee</span>
        </div>
        
        <div class="flex gap-8 text-sm font-medium text-muted-foreground">
          <a href="#" class="hover:text-foreground transition-colors">Privacy Policy</a>
          <a href="#" class="hover:text-foreground transition-colors">Terms of Service</a>
          <a href="#" class="hover:text-foreground transition-colors">Cookies</a>
        </div>
        
        <p class="text-sm text-muted-foreground">
          © 2026 Valee. All rights reserved.
        </p>
      </div>
    </footer>
  </main>
</template>

<style scoped>
@keyframes fade-in-up {
  from {
    opacity: 0;
    transform: translateY(20px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

.animate-fade-in-up {
  opacity: 0;
  animation: fade-in-up 0.8s cubic-bezier(0.2, 0.8, 0.2, 1) forwards;
}
</style>

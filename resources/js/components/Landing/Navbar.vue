<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import { Phone, ChevronDown } from 'lucide-vue-next';
import { Button } from '@/components/ui/button';
import { login, register, dashboard } from '@/routes';

const page = usePage();
const links = ["Services", "Solutions", "Resources", "Company"];
</script>

<template>
  <nav class="flex items-center justify-between px-6 md:px-10 py-5">
    <div class="flex items-center gap-2.5">
      <div class="h-7 w-7 rounded-full bg-foreground flex items-center justify-center">
        <div class="h-3 w-3 rounded-full bg-background" />
      </div>
      <span class="font-semibold text-2xl tracking-tighter">Valee</span>
    </div>
    
    <div class="hidden lg:flex items-center gap-10 text-base font-medium text-foreground/70">
      <button v-for="(l, i) in links" :key="l" class="flex items-center gap-1 hover:text-foreground transition-colors">
        {{ l }}
        <ChevronDown v-if="i < 2" class="h-3.5 w-3.5" />
      </button>
    </div>
    
    <div class="flex items-center gap-6">
      <div class="hidden md:flex items-center gap-2 text-sm font-medium">
        <Phone class="h-4 w-4" />
        <a href="tel:8448783343" class="underline underline-offset-4 font-semibold text-base">(844) TRUEID</a>
      </div>
      <span class="hidden md:block h-5 w-px bg-border" />
      <a href="#contact" class="hidden sm:block text-sm font-medium hover:underline">Contact Us</a>
      
      <Link v-if="!$page.props.auth.user" :href="login()">
        <Button variant="ghost" class="rounded-full font-medium">Login</Button>
      </Link>
      
      <Link :href="$page.props.auth.user ? dashboard() : register()">
        <Button class="rounded-full bg-gradient-coral hover:opacity-90 text-white px-8 h-12 text-base font-semibold transition-all hover:scale-[1.02] active:scale-95 shadow-coral border-none">
          {{ $page.props.auth.user ? 'Dashboard' : 'Book a Demo' }}
        </Button>
      </Link>
    </div>
  </nav>
</template>

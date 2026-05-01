<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { dashboard, login, register } from '@/routes';
import {
  ArrowRight, Star, Sparkles, ShoppingBag, BarChart3, Wrench, Shield,
  Check, Calendar, Zap, Globe2, LineChart
} from 'lucide-vue-next';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Badge } from '@/components/ui/badge';

withDefaults(
  defineProps<{
    canRegister: boolean;
  }>(),
  {
    canRegister: true,
  },
);
</script>

<template>
<Head :title="`${$page.props.name} - All-in-one Business Platform`" />

<div class="min-h-screen bg-[hsl(220_18%_8%)] text-[hsl(30_20%_96%)] font-sans selection:bg-primary/30">
  <!-- ===== NAV ===== -->
  <nav class="mx-auto max-w-[1400px] px-6 md:px-10 pt-6">
    <div
      class="flex items-center justify-between rounded-full border border-white/10 bg-white/[0.03] backdrop-blur px-4 py-3">
      <!-- Logo brand -->
      <Link href="/" class="flex items-center gap-2 pl-2" aria-label="Home">
        <div class="h-8 w-8 rounded-full bg-gradient-coral flex items-center justify-center font-bold text-white">{{
          $page.props.name.charAt(0) }}</div>
        <span class="font-bold tracking-tight text-white">{{ $page.props.name }}</span>
      </Link>
      <!-- Menu utama -->
      <div class="hidden md:flex items-center gap-1 text-sm text-white/70">
        <a href="#modules" class="px-4 py-2 rounded-full hover:text-white hover:bg-white/5 transition">Modules</a>
        <a href="#why" class="px-4 py-2 rounded-full hover:text-white hover:bg-white/5 transition">Why us</a>
        <a href="#pricing" class="px-4 py-2 rounded-full hover:text-white hover:bg-white/5 transition">Pricing</a>
        <Link v-if="$page.props.auth.user" :href="dashboard()"
          class="px-4 py-2 rounded-full hover:text-white hover:bg-white/5 transition">Dashboard</Link>
      </div>
      <!-- CTA nav: buka hub modul -->
      <div class="flex items-center gap-3">
        <Link v-if="!$page.props.auth.user" :href="login()"
          class="hidden sm:block text-sm font-bold text-white/70 hover:text-white px-4">Login</Link>
        <Link :href="$page.props.auth.user ? dashboard() : register()">
          <Button class="rounded-full bg-primary text-primary-foreground hover:bg-primary/90 shadow-coral">
            {{ $page.props.auth.user ? 'Go to Dashboard' : 'Request Demo' }}
          </Button>
        </Link>
      </div>
    </div>
  </nav>

  <!-- ===== HERO ===== -->
  <section class="mx-auto max-w-[1400px] px-6 md:px-10 pt-10 md:pt-16">
    <div
      class="relative overflow-hidden rounded-[2rem] border border-white/10 bg-gradient-to-br from-[hsl(220_18%_12%)] via-[hsl(220_18%_10%)] to-[hsl(14_40%_14%)] p-8 md:p-14">
      <!-- Glow accent -->
      <div
        class="absolute -top-32 -right-32 h-96 w-96 rounded-full bg-primary/20 blur-3xl pointer-events-none animate-float-slow" />
      <div
        class="absolute bottom-0 left-1/3 h-72 w-72 rounded-full bg-primary/10 blur-3xl pointer-events-none animate-float-slower" />

      <div class="relative grid lg:grid-cols-[1.2fr_1fr] gap-10 items-center">
        <div class="space-y-7">
          <Badge class="rounded-full bg-white/5 border border-white/10 text-white/80 hover:bg-white/10 px-3 py-1">
            <Sparkles class="h-3 w-3 mr-1.5 text-primary" /> All-in-one ERP for modern business
          </Badge>
          <h1 class="text-5xl md:text-7xl font-bold leading-[1.05] tracking-tight">
            Run your business.<br />
            <span class="text-primary">In one place.</span>
          </h1>
          <p class="text-white/60 text-lg max-w-xl leading-relaxed">
            POS, inventory, finance, dan service booking — semuanya terhubung
            dalam satu platform. Hemat biaya, percepat operasi, scale dengan tenang.
          </p>

          <!-- Rating -->
          <div class="flex items-center gap-2">
            <div class="flex">
              <Star v-for="i in 5" :key="i" class="h-4 w-4 fill-primary text-primary" />
            </div>
            <span class="text-sm text-white/60">4.9 from <span class="text-white underline">12,408 reviews</span></span>
          </div>

          <!-- CTA utama: minta demo / mulai trial -->
          <div class="flex flex-wrap gap-3 pt-2">
            <Link :href="register()">
              <Button size="lg"
                class="rounded-full bg-primary text-primary-foreground hover:bg-primary/90 shadow-coral h-12 px-7">
                Request Demo
                <ArrowRight class="ml-2 h-4 w-4" />
              </Button>
            </Link>
            <a href="#modules">
              <Button size="lg" variant="outline"
                class="rounded-full bg-white/5 border-white/15 text-white hover:bg-white/10 h-12 px-7">
                Lihat modul
              </Button>
            </a>
          </div>
        </div>

        <!-- Quote/booking mini card -->
        <div
          class="relative rounded-3xl border border-white/10 bg-white/[0.04] backdrop-blur-sm p-6 space-y-4 shadow-2xl">
          <div class="flex items-center gap-2 text-sm text-white/70">
            <Zap class="h-4 w-4 text-primary" /> Get started in 60 seconds
          </div>
          <div class="grid grid-cols-3 gap-2 p-1 rounded-2xl bg-white/5 border border-white/10">
            <!-- Tab pilih jenis usaha — visual only -->
            <button v-for="(t, i) in [
              { icon: ShoppingBag, label: 'Retail' },
              { icon: Wrench, label: 'Service' },
              { icon: BarChart3, label: 'Finance' },
            ]" :key="t.label"
              class="flex flex-col items-center gap-1 py-3 rounded-xl text-xs font-medium transition"
              :class="i === 0 ? 'bg-primary text-primary-foreground shadow-sm' : 'text-white/70 hover:bg-white/5'">
              <component :is="t.icon" class="h-4 w-4" /> {{ t.label }}
            </button>
          </div>

          <div class="space-y-2">
            <div class="flex items-center gap-2 rounded-xl bg-white/5 border border-white/10 px-3 py-2.5">
              <Calendar class="h-4 w-4 text-white/50" />
              <Input placeholder="Kapan mau mulai?"
                class="border-0 bg-transparent text-sm text-white placeholder:text-white/40 focus-visible:ring-0 h-7 px-0" />
            </div>
            <div class="flex items-center gap-2 rounded-xl bg-white/5 border border-white/10 px-3 py-2.5">
              <Globe2 class="h-4 w-4 text-white/50" />
              <Input placeholder="Email kerja"
                class="border-0 bg-transparent text-sm text-white placeholder:text-white/40 focus-visible:ring-0 h-7 px-0" />
            </div>
          </div>

          <!-- CTA card: kirim request demo -->
          <Button class="w-full rounded-xl bg-primary text-primary-foreground hover:bg-primary/90 h-11">
            Request a call
          </Button>
          <p class="text-xs text-white/40 text-center">No credit card. Cancel anytime.</p>
        </div>
      </div>

      <!-- Stats pills bawah hero -->
      <div class="relative mt-10 flex flex-wrap gap-3">
        <div v-for="s in [
          { label: 'Active stores', value: '31,081' },
          { label: 'Transactions / day', value: '215,076' },
          { label: 'Service bookings', value: '5,053' },
          { label: 'Countries', value: '1,875' },
        ]" :key="s.label"
          class="flex items-center gap-3 rounded-full bg-white/[0.04] border border-white/10 px-4 py-2.5">
          <span class="text-xs text-white/50">{{ s.label }}</span>
          <span class="text-sm font-semibold">{{ s.value }}</span>
        </div>
      </div>
    </div>
  </section>

  <!-- ===== MODULE SHOWCASE (collage) ===== -->
  <section id="modules" class="mx-auto max-w-[1400px] px-6 md:px-10 pt-16">
    <div class="flex items-end justify-between mb-6 flex-wrap gap-4">
      <div>
        <h2 class="text-3xl md:text-5xl font-bold tracking-tight text-white">
          Modul yang siap pakai <span class="text-primary">hari ini</span>
        </h2>
        <p class="text-white/50 mt-2">Semua modul terhubung satu data — pilih yang lo butuh, scale kapanpun di {{
          $page.props.name }}.</p>
      </div>
      <Link href="/onboarding" class="text-sm text-primary hover:underline flex items-center gap-1">
        Buka semua modul
        <ArrowRight class="h-4 w-4" />
      </Link>
    </div>

    <!-- Grid collage modul -->
    <div class="grid md:grid-cols-6 gap-4">
      <!-- Module 1: POS Retail — large card -->
      <Link href="/pos"
        class="md:col-span-4 group rounded-3xl border border-white/10 bg-gradient-to-br from-[hsl(14_40%_18%)] to-[hsl(220_18%_12%)] p-7 hover:border-primary/40 transition relative overflow-hidden">
        <div class="absolute -bottom-16 -right-10 h-64 w-64 rounded-full bg-primary/20 blur-3xl animate-float-slow" />
        <div class="relative">
          <div class="flex items-center gap-2 text-xs text-white/60 mb-4">
            <span class="inline-block h-2 w-2 rounded-full bg-primary animate-pulse" /> Live module
          </div>
          <ShoppingBag class="h-8 w-8 text-primary mb-4" />
          <h3 class="text-2xl md:text-3xl font-bold mb-2">POS Retail</h3>
          <p class="text-white/60 max-w-md">Cashier flow super cepat, support shift in/out, multi-payment, dan struk
            digital.</p>
          <div class="flex items-center gap-2 mt-6 text-sm text-primary group-hover:gap-3 transition-all">
            Explore POS
            <ArrowRight class="h-4 w-4" />
          </div>
        </div>
      </Link>

      <!-- Module 2: Dashboard Finance -->
      <Link href="/dashboard"
        class="md:col-span-2 group rounded-3xl border border-white/10 bg-white/[0.03] p-6 hover:border-primary/40 transition">
        <BarChart3 class="h-7 w-7 text-primary mb-3" />
        <h3 class="text-xl font-bold mb-1">Finance Dashboard</h3>
        <p class="text-white/50 text-sm">Cashflow, P&L, dan transaksi real-time.</p>
        <div class="mt-4 flex items-end gap-1 h-16">
          <div v-for="(h, i) in [40, 70, 50, 90, 60, 80]" :key="i" class="flex-1 rounded-t bg-primary/60"
            :style="{ height: h + '%' }" />
        </div>
      </Link>

      <!-- Module 3: POS Jasa -->
      <Link href="/service-orders/create"
        class="md:col-span-2 group rounded-3xl border border-white/10 bg-white/[0.03] p-6 hover:border-primary/40 transition">
        <Wrench class="h-7 w-7 text-primary mb-3" />
        <h3 class="text-xl font-bold mb-1">Service POS</h3>
        <p class="text-white/50 text-sm">Booking jadwal & order tracking pipeline.</p>
        <div class="mt-4 flex gap-1.5 flex-wrap">
          <span v-for="s in ['Queued', 'Process', 'Done']" :key="s"
            class="text-[10px] px-2 py-1 rounded-full bg-white/5 border border-white/10 text-white/70">{{ s }}</span>
        </div>
      </Link>

      <!-- Module 4: Dashboard Jasa -->
      <Link href="/service-orders/board"
        class="md:col-span-2 group rounded-3xl border border-white/10 bg-white/[0.03] p-6 hover:border-primary/40 transition">
        <LineChart class="h-7 w-7 text-primary mb-3" />
        <h3 class="text-xl font-bold mb-1">Service Hub</h3>
        <p class="text-white/50 text-sm">KPI booking, leaderboard staff, jadwal hari ini.</p>
      </Link>

      <!-- Module 5: SuperAdmin -->
      <Link href="/settings/users"
        class="md:col-span-2 group rounded-3xl border border-white/10 bg-white/[0.03] p-6 hover:border-primary/40 transition">
        <Shield class="h-7 w-7 text-primary mb-3" />
        <h3 class="text-xl font-bold mb-1">SuperAdmin</h3>
        <p class="text-white/50 text-sm">Datatable Notion-style, role & permission.</p>
      </Link>
    </div>
  </section>

  <!-- ===== WHY US ===== -->
  <section id="why" class="mx-auto max-w-[1400px] px-6 md:px-10 pt-16">
    <div class="grid md:grid-cols-2 gap-4">
      <div
        class="rounded-3xl border border-white/10 bg-gradient-to-br from-[hsl(220_18%_12%)] to-[hsl(220_18%_10%)] p-8">
        <h3 class="text-2xl font-bold mb-2 text-white">Kenapa pilih {{ $page.props.name }}?</h3>
        <ul class="space-y-3 mt-5 text-white/70">
          <li v-for="b in [
            'Setiap order ke-10 gratis biaya transaksi',
            'Free upgrade kalau omzet tembus Rp 100jt/bln',
            'Personal support 24/7 lewat chat & WA',
            'Backup data otomatis & enkripsi end-to-end',
          ]" :key="b" class="flex items-start gap-3">
            <Check class="h-5 w-5 text-primary mt-0.5 shrink-0" /> {{ b }}
          </li>
        </ul>
      </div>
      <div class="rounded-3xl border border-white/10 bg-white/[0.03] p-8 flex flex-col justify-between">
        <div>
          <h3 class="text-2xl font-bold mb-2 text-white">Trusted by growing teams</h3>
          <p class="text-white/50">Dari valee kopi sampai chain retail, semua scale di {{ $page.props.name }}.</p>
        </div>
        <div class="grid grid-cols-2 gap-3 mt-6">
          <div class="rounded-2xl bg-white/[0.03] border border-white/10 p-5">
            <div class="text-3xl font-bold text-primary">12K+</div>
            <div class="text-xs text-white/50 mt-1">Active merchants</div>
          </div>
          <div class="rounded-2xl bg-white/[0.03] border border-white/10 p-5">
            <div class="text-3xl font-bold text-primary">99.99%</div>
            <div class="text-xs text-white/50 mt-1">Uptime SLA</div>
          </div>
        </div>
      </div>
    </div>
  </section>

  <!-- ===== FINAL CTA ===== -->
  <section id="pricing" class="mx-auto max-w-[1400px] px-6 md:px-10 py-16">
    <div
      class="relative overflow-hidden rounded-[2rem] border border-white/10 bg-gradient-coral p-10 md:p-16 text-center">
      <div class="absolute inset-0 bg-[radial-gradient(circle_at_50%_0%,rgba(255,255,255,0.2),transparent_60%)]" />
      <div class="relative space-y-5">
        <h2 class="text-4xl md:text-6xl font-bold text-white tracking-tight">
          Ready to run smarter?
        </h2>
        <p class="text-white/80 max-w-xl mx-auto">
          Coba gratis 14 hari, tanpa kartu kredit. Setup &lt; 5 menit.
        </p>
        <!-- CTA final: mulai trial, redirect ke hub modul -->
        <Link :href="register()" class="inline-block pt-2">
          <Button size="lg" class="rounded-full bg-ink text-ink-foreground hover:bg-ink/90 h-12 px-8 shadow-2xl">
            Mulai sekarang
            <ArrowRight class="ml-2 h-4 w-4" />
          </Button>
        </Link>
      </div>
    </div>

    <footer class="mt-10 flex flex-wrap items-center justify-between gap-4 text-sm text-white/40">
      <div>© 2026 {{ $page.props.name }}. All rights reserved.</div>
      <div class="flex gap-6">
        <a href="#" class="hover:text-white">Privacy</a>
        <a href="#" class="hover:text-white">Terms</a>
        <a href="#" class="hover:text-white">Docs</a>
      </div>
    </footer>
  </section>
</div>
</template>

<style scoped>
@keyframes float {

  0%,
  100% {
    transform: translate(0, 0);
  }

  33% {
    transform: translate(30px, -20px);
  }

  66% {
    transform: translate(-20px, 30px);
  }
}

@keyframes float-reverse {

  0%,
  100% {
    transform: translate(0, 0);
  }

  33% {
    transform: translate(-30px, 20px);
  }

  66% {
    transform: translate(20px, -30px);
  }
}

.animate-float-slow {
  animation: float 18s ease-in-out infinite;
}

.animate-float-slower {
  animation: float-reverse 25s ease-in-out infinite;
}
</style>

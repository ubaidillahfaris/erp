<script setup lang="ts">
import { 
  Phone, MessageSquare, Search, Inbox, Filter, AlertCircle, 
  Sparkles, Play, Volume2, MoreHorizontal, UserPlus, 
  PhoneIncoming, PhoneOff, ArrowUpRight, Check 
} from 'lucide-vue-next';

const avatarMan = "https://images.unsplash.com/photo-1472099645785-5658abf4ff4e?q=80&w=250&h=250&auto=format&fit=crop";
const avatarGeorge = "https://images.unsplash.com/photo-1539571696357-5a69c17a67c6?q=80&w=250&h=250&auto=format&fit=crop";

const inboxItems = [
  { name: "Acme Group", time: "2 min", icon: Sparkles, iconProps: { class: "h-3.5 w-3.5 text-background" }, bg: "bg-foreground" },
  { name: "Nietzsche", time: "12 min", icon: Sparkles, iconProps: { class: "h-3.5 w-3.5 text-lavender-deep" }, bg: "bg-lavender-soft" },
  { name: "George Smith", time: "4 min", avatar: avatarGeorge },
  { name: "John Doe", time: "4 min", avatar: avatarMan },
];
</script>

<template>
  <div class="relative mt-10 h-[600px] hidden md:block select-none">
    <!-- Stat Card -->
    <div class="absolute left-0 top-0 bg-surface rounded-2xl shadow-card p-5 w-[230px] animate-float-slow transition-transform hover:scale-105 hover:z-50 duration-500">
      <div class="text-4xl font-semibold tracking-tight">42%</div>
      <div class="text-sm text-muted-foreground mt-1">Deflection Rate</div>
      <div class="mt-4 flex items-end gap-[3px] h-8">
        <div v-for="i in 28" :key="i"
          :style="{ height: (30 + ((i * 37) % 70)) + '%', animationDelay: (i * 0.1) + 's' }"
          :class="['w-1.5 rounded-full animate-pulse-gentle', i < 16 ? 'bg-lavender-deep' : 'bg-muted']"
        />
      </div>
      <div class="mt-3 inline-flex items-center gap-1 text-xs font-semibold text-primary">
        <span class="h-3.5 w-3.5 rounded-full bg-primary/15 flex items-center justify-center">
          <Check class="h-2 w-2 text-primary" />
        </span>
        8%
      </div>
    </div>

    <!-- Incoming Call -->
    <div class="absolute left-2 top-[280px] bg-surface rounded-2xl shadow-card p-5 w-[270px] animate-float-slower transition-transform hover:scale-105 hover:z-50 duration-500">
      <div class="flex items-center justify-between text-xs">
        <div class="flex items-center gap-2">
          <div class="h-7 w-7 rounded-lg bg-lavender-soft flex items-center justify-center relative">
            <PhoneIncoming class="h-3.5 w-3.5 text-lavender-deep" />
            <span class="absolute -top-0.5 -right-0.5 h-2 w-2 bg-primary rounded-full animate-ping" />
          </div>
          <span class="font-semibold">Customer Support</span>
        </div>
        <span class="text-muted-foreground animate-pulse">Incoming Call…</span>
      </div>
      <div class="mt-5 flex flex-col items-center">
        <img :src="avatarMan" alt="caller" class="h-[72px] w-[72px] rounded-full object-cover ring-4 ring-primary/5" />
        <div class="mt-3 inline-flex items-center gap-1 text-[11px] font-medium px-2 py-0.5 rounded-full border border-border">
          <span class="h-2 w-2 rounded-full bg-primary animate-pulse" /> TrueCallerID
        </div>
        <div class="mt-2 text-lg font-semibold tracking-tight">+1 (546) 123-4567</div>
        <div class="text-xs text-muted-foreground">About Customer Experience</div>
      </div>
      <div class="mt-4 grid grid-cols-2 gap-2">
        <button class="flex items-center justify-center gap-1.5 h-10 rounded-xl bg-peach-soft text-foreground text-sm font-semibold hover:bg-peach/20 transition-colors">
          <PhoneOff class="h-4 w-4" /> Decline
        </button>
        <button class="flex items-center justify-center gap-1.5 h-10 rounded-xl bg-primary text-primary-foreground text-sm font-semibold hover:opacity-90 transition-all hover:shadow-lg">
          <Phone class="h-4 w-4" /> Accept
        </button>
      </div>
    </div>

    <!-- Chat Panel -->
    <div class="absolute left-[280px] top-[140px] flex bg-surface rounded-[2rem] shadow-card overflow-hidden w-[780px] h-[440px] animate-float transition-transform hover:scale-[1.01] hover:z-50 duration-700">
      <!-- Sidebar Icons -->
      <div class="w-14 bg-muted/30 flex flex-col items-center py-5 gap-4 border-r border-border/50">
        <div class="h-10 w-10 rounded-xl bg-lavender-deep flex items-center justify-center text-primary-foreground text-xs font-semibold">V.</div>
        <button v-for="icon in [Search, Inbox, MessageSquare]" :key="icon.name" 
          :class="['h-9 w-9 rounded-xl flex items-center justify-center transition-all', icon === Inbox ? 'bg-primary text-white shadow-coral-sm' : 'text-muted-foreground hover:bg-muted']">
          <component :is="icon" class="h-5 w-5" />
        </button>
      </div>
      <!-- Inbox List -->
      <div class="w-[280px] border-r border-border/50 p-5">
        <div class="flex items-center justify-between">
          <h3 class="text-lg font-semibold tracking-tight">Inbox</h3>
          <button class="inline-flex items-center gap-1 text-xs text-muted-foreground border border-border rounded-lg px-2.5 py-1.5 hover:bg-muted transition-colors">
            <Filter class="h-3.5 w-3.5" /> Filter
          </button>
        </div>
        <div class="mt-5 grid grid-cols-2 gap-1 p-1 bg-muted/50 rounded-xl">
          <button class="flex items-center justify-center gap-2 text-xs font-semibold py-2 rounded-lg bg-surface shadow-sm transition-all">
            <Phone class="h-3.5 w-3.5" /> Calls
          </button>
          <button class="flex items-center justify-center gap-2 text-xs font-medium py-2 rounded-lg text-muted-foreground hover:text-foreground transition-all">
            <MessageSquare class="h-3.5 w-3.5" /> Messages
          </button>
        </div>
        <div class="mt-4 relative">
          <Search class="h-4 w-4 absolute left-3 top-1/2 -translate-y-1/2 text-muted-foreground/50" />
          <input placeholder="Search Contacts…" class="w-full text-xs pl-9 pr-3 py-2.5 rounded-xl border border-border/50 bg-white/50 focus:bg-white transition-all outline-none focus:ring-2 ring-primary/5" />
        </div>
        <div class="mt-4 space-y-1.5">
          <div v-for="(it, idx) in inboxItems" :key="it.name" 
            class="flex items-center gap-3 p-2.5 rounded-xl hover:bg-muted/60 transition-all cursor-pointer group"
            :style="{ animationDelay: (idx * 0.1) + 's' }">
            <div class="relative">
              <img v-if="it.avatar" :src="it.avatar" class="h-9 w-9 rounded-full object-cover grayscale-[0.2] group-hover:grayscale-0 transition-all" />
              <div v-else :class="['h-9 w-9 rounded-full flex items-center justify-center', it.bg]">
                 <component :is="it.icon" v-bind="it.iconProps" />
              </div>
              <span v-if="idx === 0" class="absolute bottom-0 right-0 h-2.5 w-2.5 bg-green-500 rounded-full border-2 border-surface" />
            </div>
            <div class="flex-1 min-w-0">
              <div class="text-xs font-semibold truncate group-hover:text-primary transition-colors">{{ it.name }}</div>
              <div class="text-[10px] text-muted-foreground/70">+1 (546) 123-4567</div>
            </div>
            <div class="text-[10px] text-muted-foreground/50 whitespace-nowrap">⏱ {{ it.time }}</div>
          </div>
        </div>
      </div>
      <!-- Message View -->
      <div class="flex-1 flex flex-col bg-surface/50">
        <div class="flex items-center justify-between p-5 border-b border-border/50">
          <div class="flex items-center gap-3">
            <div class="relative">
              <img :src="avatarGeorge" alt="George Smith" class="h-10 w-10 rounded-full object-cover" />
              <span class="absolute bottom-0 right-0 h-3 w-3 bg-green-500 rounded-full border-2 border-surface" />
            </div>
            <div>
              <div class="text-sm font-semibold tracking-tight">George Smith</div>
              <div class="text-[11px] text-muted-foreground flex items-center gap-1">
                <span class="h-1.5 w-1.5 rounded-full bg-green-500" /> Online
              </div>
            </div>
          </div>
          <div class="flex items-center gap-1.5 text-muted-foreground/70">
            <button v-for="icon in [Phone, MessageSquare, UserPlus, MoreHorizontal]" :key="icon.name" class="h-9 w-9 rounded-xl hover:bg-muted flex items-center justify-center transition-all hover:text-foreground">
              <component :is="icon" class="h-4.5 w-4.5" />
            </button>
          </div>
        </div>
        <div class="flex-1 p-6 space-y-5 overflow-hidden">
          <div class="flex items-end gap-2.5 max-w-[85%] animate-fade-in-left">
            <img :src="avatarGeorge" alt="" class="h-8 w-8 rounded-full object-cover shadow-sm" />
            <div>
              <div class="bg-muted/70 backdrop-blur-sm rounded-2xl rounded-bl-sm px-4 py-3 text-sm leading-relaxed">
                Hello👋, Thanks for your message! what can I help you with today?
              </div>
              <div class="text-[10px] text-muted-foreground/60 mt-1.5 ml-1">10:45 AM</div>
            </div>
          </div>
          <div class="flex justify-end animate-fade-in-right">
            <div class="max-w-[85%]">
              <div class="bg-lavender-soft/80 backdrop-blur-sm text-lavender-deep rounded-2xl rounded-br-sm px-4 py-3 text-sm leading-relaxed shadow-sm">
                Hi, I need to book a trip plan to Japan from 15th to 21st June 2025.
              </div>
              <div class="flex items-center justify-end gap-1.5 text-[10px] text-muted-foreground/60 mt-1.5 mr-1">
                10:45 AM <Check class="h-3.5 w-3.5 text-primary" />
              </div>
            </div>
          </div>
          <!-- Call Record -->
          <div class="rounded-2xl border border-border/50 bg-white/50 backdrop-blur-sm p-4 max-w-[320px] shadow-sm animate-fade-in-up">
            <div class="flex items-center gap-3">
              <div class="h-10 w-10 rounded-full bg-primary/10 flex items-center justify-center">
                <ArrowUpRight class="h-5 w-5 text-primary" />
              </div>
              <div>
                <div class="text-sm font-semibold tracking-tight">Call Ended</div>
                <div class="text-[11px] text-muted-foreground">You Called • 0.43 mins</div>
              </div>
            </div>
            <div class="mt-4 text-[10px] font-semibold uppercase tracking-widest text-muted-foreground/50">Recording</div>
            <div class="mt-2 flex items-center gap-3">
              <button class="h-8 w-8 rounded-full bg-foreground text-background flex items-center justify-center transition-transform hover:scale-110 active:scale-95 shadow-lg">
                <Play class="h-3.5 w-3.5 ml-0.5" />
              </button>
              <Volume2 class="h-4 w-4 text-muted-foreground/70" />
              <div class="flex-1 h-1.5 rounded-full bg-muted/50 overflow-hidden relative group cursor-pointer">
                <div class="absolute inset-0 bg-foreground/10 w-2/3 animate-pulse-gentle" />
                <div class="h-full w-2/3 bg-foreground relative z-10" />
              </div>
              <span class="text-[10px] font-medium text-muted-foreground/70 tracking-tighter">03:56</span>
              <MoreHorizontal class="h-4 w-4 text-muted-foreground/50 hover:text-foreground cursor-pointer" />
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Right Column Pills -->
    <div class="absolute right-0 top-[60px] bg-surface rounded-2xl shadow-card p-4 pr-5 w-[300px] flex items-center gap-4 animate-float-slow transition-transform hover:scale-105 hover:z-50 duration-500">
      <div class="h-10 w-10 rounded-xl bg-destructive/10 flex items-center justify-center">
        <AlertCircle class="h-6 w-6 text-destructive animate-pulse" />
      </div>
      <div class="flex-1 min-w-0">
        <div class="text-sm font-semibold tracking-tight truncate">Spam Caller</div>
        <div class="text-[11px] text-muted-foreground">+1 (546) 123-4567</div>
        <div class="mt-1.5 inline-flex items-center gap-1.5 text-[10px] font-semibold text-destructive bg-destructive/10 px-2 py-0.5 rounded-lg border border-destructive/10">
          ⚑ Flagged 32 times
        </div>
      </div>
      <div class="text-[10px] font-medium text-muted-foreground/40 whitespace-nowrap">TrueCallerID</div>
    </div>

    <div class="absolute right-0 top-[200px] bg-surface rounded-2xl shadow-card p-4 pr-5 w-[300px] flex items-center gap-4 animate-float transition-transform hover:scale-105 hover:z-50 duration-500">
      <div class="h-10 w-10 rounded-full bg-foreground flex items-center justify-center shadow-lg">
        <Sparkles class="h-5 w-5 text-background animate-pulse-gentle" />
      </div>
      <div class="flex-1 min-w-0">
        <div class="text-sm font-semibold tracking-tight truncate">Acme Group</div>
        <div class="text-[11px] text-muted-foreground">+1 (546) 123-4567</div>
      </div>
      <div class="text-[10px] font-medium text-muted-foreground/40 whitespace-nowrap">TrueCallerID</div>
    </div>

    <!-- Flagged Chart -->
    <div class="absolute right-0 top-[370px] bg-surface rounded-[2rem] shadow-card p-6 w-[290px] animate-float-slower transition-transform hover:scale-105 hover:z-50 duration-500">
      <div class="text-sm font-semibold tracking-tight">Flagged Numbers</div>
      <div class="mt-6 flex items-end gap-3.5 h-28">
        <div v-for="(c, i) in [
          { a: 80, b: 55, color: 'lavender' },
          { a: 60, b: 70, color: 'lavender' },
          { a: 95, b: 65, color: 'peach' },
          { a: 70, b: 50, color: 'peach' },
        ]" :key="i" class="flex-1 flex items-end justify-center gap-1.5">
          <div :class="[c.color === 'lavender' ? 'bg-lavender' : 'bg-peach', 'animate-grow-up']" 
            :style="{ height: c.a + '%', width: '14px', borderRadius: '4px', opacity: 0.7, animationDelay: (i * 0.1) + 's' }" />
          <div :class="[c.color === 'lavender' ? 'bg-lavender-deep' : 'bg-peach', 'animate-grow-up']" 
            :style="{ height: c.b + '%', width: '14px', borderRadius: '4px', animationDelay: (i * 0.15) + 's' }" />
        </div>
      </div>
      <div class="mt-4 flex justify-between text-[11px] font-medium text-muted-foreground/50 px-1">
        <span>AT&T</span><span>Beta</span>
      </div>
      <div class="mt-4 flex flex-col gap-2 pt-4 border-t border-border/50">
        <div class="flex items-center justify-between text-[10px] font-medium">
          <span class="flex items-center gap-2 text-muted-foreground/70"><span class="h-2 w-2 rounded-full bg-lavender-deep" /> This quarter</span>
          <span class="text-foreground">+12.5%</span>
        </div>
        <div class="flex items-center justify-between text-[10px] font-medium">
          <span class="flex items-center gap-2 text-muted-foreground/70"><span class="h-2 w-2 rounded-full bg-peach" /> Last quarter</span>
          <span class="text-foreground">-2.4%</span>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
@keyframes float {
  0%, 100% { transform: translateY(0); }
  50% { transform: translateY(-15px); }
}

@keyframes float-slow {
  0%, 100% { transform: translateY(0); }
  50% { transform: translateY(-10px); }
}

@keyframes float-slower {
  0%, 100% { transform: translateY(0); }
  50% { transform: translateY(-20px); }
}

@keyframes pulse-gentle {
  0%, 100% { opacity: 1; transform: scale(1); }
  50% { opacity: 0.8; transform: scale(0.98); }
}

@keyframes fade-in-left {
  from { opacity: 0; transform: translateX(-20px); }
  to { opacity: 1; transform: translateX(0); }
}

@keyframes fade-in-right {
  from { opacity: 0; transform: translateX(20px); }
  to { opacity: 1; transform: translateX(0); }
}

@keyframes fade-in-up {
  from { opacity: 0; transform: translateY(20px); }
  to { opacity: 1; transform: translateY(0); }
}

@keyframes grow-up {
  from { height: 0; }
}

.animate-float { animation: float 6s ease-in-out infinite; }
.animate-float-slow { animation: float-slow 8s ease-in-out infinite; }
.animate-float-slower { animation: float-slower 10s ease-in-out infinite; }
.animate-pulse-gentle { animation: pulse-gentle 3s ease-in-out infinite; }
.animate-fade-in-left { animation: fade-in-left 0.6s ease-out forwards; }
.animate-fade-in-right { animation: fade-in-right 0.6s ease-out forwards; }
.animate-fade-in-up { animation: fade-in-up 0.6s ease-out forwards; }
.animate-grow-up { animation: grow-up 1s cubic-bezier(0.17, 0.67, 0.83, 0.67) forwards; }
</style>

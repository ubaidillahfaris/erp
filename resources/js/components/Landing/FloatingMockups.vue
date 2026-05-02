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
  <div class="relative mt-10 h-[560px] hidden md:block">
    <!-- Stat Card -->
    <div class="absolute left-0 top-0 bg-surface rounded-2xl shadow-card p-5 w-[230px]">
      <div class="text-4xl font-bold tracking-tight">42%</div>
      <div class="text-sm text-muted-foreground mt-1">Deflection Rate</div>
      <div class="mt-4 flex items-end gap-[3px] h-8">
        <div v-for="i in 28" :key="i"
          :style="{ height: (30 + ((i * 37) % 70)) + '%' }"
          :class="['w-1.5 rounded-full', i < 16 ? 'bg-lavender-deep' : 'bg-muted']"
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
    <div class="absolute left-2 top-[260px] bg-surface rounded-2xl shadow-card p-5 w-[270px]">
      <div class="flex items-center justify-between text-xs">
        <div class="flex items-center gap-2">
          <div class="h-7 w-7 rounded-lg bg-lavender-soft flex items-center justify-center">
            <PhoneIncoming class="h-3.5 w-3.5 text-lavender-deep" />
          </div>
          <span class="font-semibold">Customer Support</span>
        </div>
        <span class="text-muted-foreground">Incoming Call…</span>
      </div>
      <div class="mt-5 flex flex-col items-center">
        <img :src="avatarMan" alt="caller" class="h-[72px] w-[72px] rounded-full object-cover" />
        <div class="mt-3 inline-flex items-center gap-1 text-[11px] font-medium px-2 py-0.5 rounded-full border border-border">
          <span class="h-2 w-2 rounded-full bg-primary" /> TrueCallerID
        </div>
        <div class="mt-2 text-lg font-bold">+1 (546) 123-4567</div>
        <div class="text-xs text-muted-foreground">About Customer Experience</div>
      </div>
      <div class="mt-4 grid grid-cols-2 gap-2">
        <button class="flex items-center justify-center gap-1.5 h-10 rounded-xl bg-peach-soft text-foreground text-sm font-semibold">
          <PhoneOff class="h-4 w-4" /> Decline
        </button>
        <button class="flex items-center justify-center gap-1.5 h-10 rounded-xl bg-primary text-primary-foreground text-sm font-semibold">
          <Phone class="h-4 w-4" /> Accept
        </button>
      </div>
    </div>

    <!-- Chat Panel -->
    <div class="absolute left-[280px] top-[120px] flex bg-surface rounded-2xl shadow-card overflow-hidden w-[760px] h-[420px]">
      <!-- Sidebar Icons -->
      <div class="w-12 bg-muted/40 flex flex-col items-center py-4 gap-3 border-r border-border">
        <div class="h-8 w-8 rounded-lg bg-lavender-deep flex items-center justify-center text-primary-foreground text-xs font-bold">P.</div>
        <button class="h-8 w-8 rounded-lg flex items-center justify-center text-muted-foreground hover:bg-muted">
          <Search class="h-4 w-4" />
        </button>
        <button class="h-8 w-8 rounded-lg bg-primary flex items-center justify-center text-primary-foreground">
          <Inbox class="h-4 w-4" />
        </button>
      </div>
      <!-- Inbox List -->
      <div class="w-[260px] border-r border-border p-4">
        <div class="flex items-center justify-between">
          <h3 class="font-bold">Inbox</h3>
          <button class="inline-flex items-center gap-1 text-xs text-muted-foreground border border-border rounded-md px-2 py-1">
            <Filter class="h-3 w-3" /> Filter
          </button>
        </div>
        <div class="mt-4 grid grid-cols-2 gap-1 p-1 bg-muted rounded-lg">
          <button class="flex items-center justify-center gap-1.5 text-xs font-semibold py-1.5 rounded-md bg-surface shadow-sm">
            <Phone class="h-3 w-3" /> Calls
          </button>
          <button class="flex items-center justify-center gap-1.5 text-xs font-medium py-1.5 rounded-md text-muted-foreground">
            <MessageSquare class="h-3 w-3" /> Messages
          </button>
        </div>
        <div class="mt-3 relative">
          <Search class="h-3.5 w-3.5 absolute left-2.5 top-1/2 -translate-y-1/2 text-muted-foreground" />
          <input placeholder="Search Contacts…" class="w-full text-xs pl-8 pr-2 py-2 rounded-lg border border-border bg-transparent" />
        </div>
        <div class="mt-3 space-y-1">
          <div v-for="it in inboxItems" :key="it.name" class="flex items-center gap-2.5 p-2 rounded-lg hover:bg-muted/60">
            <img v-if="it.avatar" :src="it.avatar" class="h-8 w-8 rounded-full object-cover" />
            <div v-else :class="['h-8 w-8 rounded-full flex items-center justify-center', it.bg]">
               <component :is="it.icon" v-bind="it.iconProps" />
            </div>
            <div class="flex-1 min-w-0">
              <div class="text-xs font-semibold truncate">{{ it.name }}</div>
              <div class="text-[10px] text-muted-foreground">+1 (546) 123-4567</div>
            </div>
            <div class="text-[10px] text-muted-foreground whitespace-nowrap">⏱ {{ it.time }}</div>
          </div>
        </div>
      </div>
      <!-- Message View -->
      <div class="flex-1 flex flex-col">
        <div class="flex items-center justify-between p-4 border-b border-border">
          <div class="flex items-center gap-2.5">
            <img :src="avatarGeorge" alt="George Smith" class="h-9 w-9 rounded-full object-cover" />
            <div>
              <div class="text-sm font-bold">George Smith</div>
              <div class="text-[11px] text-muted-foreground">+1 (546) 123-4567</div>
            </div>
          </div>
          <div class="flex items-center gap-1 text-muted-foreground">
            <button v-for="icon in [Phone, MessageSquare, UserPlus, MoreHorizontal]" :key="icon.name" class="h-8 w-8 rounded-md hover:bg-muted flex items-center justify-center">
              <component :is="icon" class="h-4 w-4" />
            </button>
          </div>
        </div>
        <div class="flex-1 p-5 space-y-4 overflow-hidden">
          <div class="flex items-end gap-2 max-w-[75%]">
            <img :src="avatarGeorge" alt="" class="h-7 w-7 rounded-full object-cover" />
            <div>
              <div class="bg-muted rounded-2xl rounded-bl-sm px-4 py-2.5 text-sm">
                Hello👋, Thanks for your message! what can I help you with today?
              </div>
              <div class="text-[10px] text-muted-foreground mt-1">10:45 AM</div>
            </div>
          </div>
          <div class="flex justify-end">
            <div class="max-w-[75%]">
              <div class="bg-lavender-soft rounded-2xl rounded-br-sm px-4 py-2.5 text-sm">
                Hi, I need to book a trip plan to Japan from 15th to 21st June 2025.
              </div>
              <div class="flex items-center justify-end gap-1 text-[10px] text-muted-foreground mt-1">
                10:45 AM <Check class="h-3 w-3 text-primary" />
              </div>
            </div>
          </div>
          <!-- Call Record -->
          <div class="rounded-xl border border-border p-3 max-w-[300px]">
            <div class="flex items-center gap-2">
              <div class="h-8 w-8 rounded-full bg-primary/10 flex items-center justify-center">
                <ArrowUpRight class="h-4 w-4 text-primary" />
              </div>
              <div>
                <div class="text-xs font-semibold">Call Ended</div>
                <div class="text-[10px] text-muted-foreground">You Called • 0.43 mins</div>
              </div>
            </div>
            <div class="mt-2 text-[10px] text-muted-foreground">Recording</div>
            <div class="mt-1 flex items-center gap-2">
              <button class="h-6 w-6 rounded-full bg-foreground text-background flex items-center justify-center">
                <Play class="h-3 w-3" />
              </button>
              <Volume2 class="h-3 w-3 text-muted-foreground" />
              <div class="flex-1 h-1 rounded-full bg-muted overflow-hidden">
                <div class="h-full w-2/3 bg-foreground" />
              </div>
              <span class="text-[10px] text-muted-foreground">03:56</span>
              <MoreHorizontal class="h-3 w-3 text-muted-foreground" />
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Right Column Pills -->
    <div class="absolute right-0 top-[40px] bg-surface rounded-2xl shadow-card p-3 pr-4 w-[280px] flex items-center gap-3">
      <div class="h-9 w-9 rounded-xl bg-destructive/10 flex items-center justify-center">
        <AlertCircle class="h-5 w-5 text-destructive" />
      </div>
      <div class="flex-1">
        <div class="text-sm font-bold">Spam Caller</div>
        <div class="text-[11px] text-muted-foreground">+1 (546) 123-4567</div>
        <div class="mt-1 inline-flex items-center gap-1 text-[10px] font-semibold text-destructive bg-destructive/10 px-1.5 py-0.5 rounded">
          ⚑ Flagged 32 times
        </div>
      </div>
      <div class="text-[10px] text-muted-foreground">TrueCallerID</div>
    </div>

    <div class="absolute right-0 top-[170px] bg-surface rounded-2xl shadow-card p-3 pr-4 w-[280px] flex items-center gap-3">
      <div class="h-9 w-9 rounded-full bg-foreground flex items-center justify-center">
        <Sparkles class="h-4 w-4 text-background" />
      </div>
      <div class="flex-1">
        <div class="text-sm font-bold">Acme Group</div>
        <div class="text-[11px] text-muted-foreground">+1 (546) 123-4567</div>
      </div>
      <div class="text-[10px] text-muted-foreground">TrueCallerID</div>
    </div>

    <!-- Flagged Chart -->
    <div class="absolute right-0 top-[330px] bg-surface rounded-2xl shadow-card p-5 w-[270px]">
      <div class="text-sm font-bold">Flagged Numbers</div>
      <div class="mt-5 flex items-end gap-3 h-24">
        <div v-for="(c, i) in [
          { a: 80, b: 55, color: 'lavender' },
          { a: 60, b: 70, color: 'lavender' },
          { a: 95, b: 65, color: 'peach' },
          { a: 70, b: 50, color: 'peach' },
        ]" :key="i" class="flex-1 flex items-end justify-center gap-1">
          <div :class="[c.color === 'lavender' ? 'bg-lavender' : 'bg-peach']" :style="{ height: c.a + '%', width: '14px', borderRadius: '4px', opacity: 0.7 }" />
          <div :class="[c.color === 'lavender' ? 'bg-lavender-deep' : 'bg-peach']" :style="{ height: c.b + '%', width: '14px', borderRadius: '4px' }" />
        </div>
      </div>
      <div class="mt-2 flex justify-between text-[10px] text-muted-foreground px-1">
        <span>AT&T</span><span>Beta</span>
      </div>
      <div class="mt-3 flex items-center gap-3 text-[10px] text-muted-foreground">
        <span class="flex items-center gap-1"><span class="h-1.5 w-1.5 rounded-full bg-lavender-deep" /> This quarter</span>
        <span class="flex items-center gap-1"><span class="h-1.5 w-1.5 rounded-full bg-peach" /> Last quarter</span>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue';
import Sidebar from '../components/Sidebar.vue';
import { Menu } from 'lucide-vue-next';

defineOptions({ name: 'DashboardLayout' });

const isSidebarOpen = ref(false);

const openSidebar = () => {
  isSidebarOpen.value = true;
};

const closeSidebar = () => {
  isSidebarOpen.value = false;
};
</script>

<template>
  <div class="min-h-screen bg-slate-950 text-white">
    <div class="grid min-h-screen md:grid-cols-[88px_1fr] lg:grid-cols-[260px_1fr]">
      <aside class="hidden md:block lg:hidden">
        <Sidebar compact />
      </aside>
      <aside class="hidden lg:block">
        <Sidebar />
      </aside>

      <div class="flex min-h-screen flex-col">
        <header class="flex items-center justify-between border-b border-white/10 bg-slate-950 px-6 py-4 lg:hidden">
          <div>
            <p class="text-xs font-semibold uppercase tracking-[0.32em] text-emerald-200/80">Vegro HR</p>
            <p class="text-sm text-slate-200/80">Dashboard</p>
          </div>
          <button
            class="inline-flex h-10 w-10 items-center justify-center rounded-xl border border-white/10 bg-white/5"
            type="button"
            @click="openSidebar"
          >
            <Menu class="h-5 w-5" />
          </button>
        </header>

        <main class="min-h-screen bg-slate-950">
          <router-view />
        </main>
      </div>
    </div>

    <transition name="fade">
      <div
        v-if="isSidebarOpen"
        class="fixed inset-0 z-40 bg-slate-950/80 backdrop-blur-sm lg:hidden"
        @click="closeSidebar"
      ></div>
    </transition>

    <transition name="slide">
      <div
        v-if="isSidebarOpen"
        class="fixed inset-y-0 left-0 z-50 w-72 max-w-full lg:hidden"
      >
        <Sidebar is-mobile @close="closeSidebar" />
      </div>
    </transition>
  </div>
</template>

<style scoped>
.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.2s ease;
}

.fade-enter-from,
.fade-leave-to {
  opacity: 0;
}

.slide-enter-active,
.slide-leave-active {
  transition: transform 0.25s ease;
}

.slide-enter-from,
.slide-leave-to {
  transform: translateX(-100%);
}
</style>

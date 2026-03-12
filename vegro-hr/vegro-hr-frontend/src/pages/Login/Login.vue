<!-- eslint-disable no-unused-vars -->
<script setup>
import { ref } from 'vue';
import { useRouter } from 'vue-router';
import AuthService from '../../services/AuthService';
import useAuth from '../../hooks/useAuth';

defineOptions({ name: 'LoginPage' });

const router = useRouter();
const { fetchUser, roleTitle, user, isAdmin, hasRole } = useAuth();

const email = ref('');
const password = ref('');
const errorMessage = ref('');
const isSubmitting = ref(false);

const handleSubmit = async () => {
  errorMessage.value = '';
  isSubmitting.value = true;

  try {
    await AuthService.login({
      email: email.value,
      password: password.value,
    });

    await fetchUser();

    if (isAdmin.value) {
      await router.push('/dashboard/home');
      return;
    }

    if (hasRole(['hr'])) {
      await router.push('/dashboard/hr');
      return;
    }

    if (hasRole(['finance'])) {
      await router.push('/dashboard/finance');
      return;
    }

    if (hasRole(['manager'])) {
      await router.push('/dashboard/manager');
      return;
    }

    if (hasRole(['director', 'md'])) {
      await router.push('/dashboard/director');
      return;
    }

    if (hasRole(['employee'])) {
      await router.push('/dashboard/employee');
      return;
    }

    await router.push('/dashboard/profile');
  } catch (error) {
    const apiMessage = error?.response?.data?.message;
    errorMessage.value = apiMessage || 'Login failed. Please check your credentials and try again.';
  } finally {
    isSubmitting.value = false;
  }
};
</script>

<template>
  <div class="relative min-h-screen overflow-hidden bg-slate-950 text-white">
    <div
      class="pointer-events-none absolute inset-0 bg-[radial-gradient(circle_at_top,_rgba(20,184,166,0.2),_transparent_45%),radial-gradient(circle_at_bottom,_rgba(59,130,246,0.2),_transparent_40%)]"
    ></div>
    <div class="absolute -left-24 top-24 h-72 w-72 rounded-full bg-emerald-400/20 blur-[120px]"></div>
    <div class="absolute -right-24 bottom-10 h-80 w-80 rounded-full bg-blue-500/20 blur-[140px]"></div>

    <div class="relative mx-auto flex min-h-screen w-full max-w-6xl items-center px-6 py-16">
      <div class="grid w-full gap-10 lg:grid-cols-[1.2fr_0.8fr]">
        <div class="flex flex-col justify-center gap-8">
          <div class="flex items-center gap-3">
            <div class="flex h-12 w-12 items-center justify-center rounded-2xl border border-white/10 bg-white/10 text-sm font-semibold">
              V
            </div>
            <div>
              <p class="text-xs font-semibold uppercase tracking-[0.32em] text-emerald-200/80">
                Vegro
              </p>
              <p class="text-sm text-slate-400">Enterprise access portal</p>
            </div>
          </div>

          <div class="space-y-4">
            <h1 class="text-4xl font-semibold leading-tight sm:text-5xl">
              Welcome back to the enterprise workforce cloud.
            </h1>
            <p class="max-w-xl text-base text-slate-200/80 sm:text-lg">
              Sign in to manage payroll, attendance, leave, and workforce operations with real-time
              analytics and custom reporting.
            </p>
          </div>

          <div class="grid gap-4 sm:grid-cols-2">
            <div class="rounded-2xl border border-white/10 bg-white/5 p-4">
              <p class="text-xs uppercase tracking-[0.24em] text-slate-400">Operational clarity</p>
              <p class="mt-2 text-sm text-slate-200">Track payroll, attendance, and leave in real time.</p>
            </div>
            <div class="rounded-2xl border border-white/10 bg-white/5 p-4">
              <p class="text-xs uppercase tracking-[0.24em] text-slate-400">Dynamic reporting</p>
              <p class="mt-2 text-sm text-slate-200">Build reports tailored to your enterprise.</p>
            </div>
          </div>
        </div>

        <form
          class="mx-auto flex w-full max-w-md flex-col gap-6 rounded-3xl border border-white/10 bg-white/10 p-8 shadow-[0_25px_80px_rgba(15,23,42,0.55)] backdrop-blur"
          @submit.prevent="handleSubmit"
        >
          <div class="flex flex-col gap-2">
            <h2 class="text-2xl font-semibold">Sign in</h2>
            <p class="text-sm text-slate-200/70">
              Use your enterprise credentials to continue.
            </p>
          </div>

          <label class="flex flex-col gap-2 text-sm text-slate-200/80">
            <span>Email</span>
            <input
              v-model="email"
              type="email"
              name="email"
              autocomplete="email"
              required
              class="h-12 rounded-xl border border-white/10 bg-slate-950/40 px-4 text-sm text-white outline-none transition focus:border-emerald-300/70 focus:ring-2 focus:ring-emerald-300/40"
              placeholder="you@company.com"
            />
          </label>

          <label class="flex flex-col gap-2 text-sm text-slate-200/80">
            <span>Password</span>
            <input
              v-model="password"
              type="password"
              name="password"
              autocomplete="current-password"
              required
              class="h-12 rounded-xl border border-white/10 bg-slate-950/40 px-4 text-sm text-white outline-none transition focus:border-emerald-300/70 focus:ring-2 focus:ring-emerald-300/40"
              placeholder="********"
            />
          </label>

          <p v-if="errorMessage" class="rounded-xl border border-red-500/30 bg-red-500/10 px-4 py-3 text-sm text-red-200">
            {{ errorMessage }}
          </p>

          <button
            class="inline-flex h-12 items-center justify-center rounded-xl bg-emerald-400 text-sm font-semibold text-slate-950 transition hover:bg-emerald-300 disabled:cursor-not-allowed disabled:opacity-70"
            type="submit"
            :disabled="isSubmitting"
          >
            {{ isSubmitting ? 'Signing in...' : 'Continue' }}
          </button>

          <div class="flex items-center justify-between text-xs text-slate-300/70">
            <span>Need access? Contact support.</span>
            <span class="rounded-full border border-white/10 bg-white/5 px-3 py-1">Secure Session</span>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

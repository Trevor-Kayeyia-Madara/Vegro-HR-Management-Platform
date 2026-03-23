<script setup>
import { ref } from 'vue';
import apiClient from '../../api/apiClient';

defineOptions({ name: 'ContactPage' });

const formState = ref({
  name: '',
  email: '',
  company: '',
  team_size: '',
  message: '',
});
const isSubmitting = ref(false);
const successMessage = ref('');
const errorMessage = ref('');

const submitDemoRequest = async () => {
  if (isSubmitting.value) return;
  successMessage.value = '';
  errorMessage.value = '';
  isSubmitting.value = true;

  const message = [
    formState.value.team_size ? `Team size: ${formState.value.team_size}` : null,
    formState.value.message || null,
  ]
    .filter(Boolean)
    .join('\n');

  try {
    await apiClient.post('/api/lead-capture', {
      name: formState.value.name,
      email: formState.value.email,
      company: formState.value.company,
      message,
      source: 'contact-page',
    });

    successMessage.value = 'Request received. We will contact you shortly.';
    formState.value = {
      name: '',
      email: '',
      company: '',
      team_size: '',
      message: '',
    };
  } catch (error) {
    errorMessage.value = error?.response?.data?.message || 'Unable to submit request. Please try again.';
  } finally {
    isSubmitting.value = false;
  }
};
</script>

<template>
  <div class="min-h-screen bg-slate-950 text-white">
    <div class="relative overflow-hidden">
      <div
        class="pointer-events-none absolute inset-0 bg-[radial-gradient(circle_at_top,_rgba(20,184,166,0.18),_transparent_50%),radial-gradient(circle_at_bottom,_rgba(59,130,246,0.18),_transparent_45%)]"
      ></div>
      <div class="absolute -left-16 top-16 h-64 w-64 rounded-full bg-emerald-400/20 blur-[120px]"></div>
      <div class="absolute -right-24 bottom-10 h-72 w-72 rounded-full bg-blue-500/20 blur-[140px]"></div>

      <header class="relative mx-auto flex w-full max-w-6xl items-center justify-between px-6 py-6">
        <div class="flex items-center gap-3">
          <div class="flex h-10 w-10 items-center justify-center rounded-2xl border border-white/10 bg-white/10 text-sm font-semibold">
            V
          </div>
          <div>
            <p class="text-xs font-semibold uppercase tracking-[0.28em] text-emerald-200/80">Vegro</p>
            <p class="text-sm text-slate-400">Contact the team</p>
          </div>
        </div>
        <RouterLink
          to="/"
          class="rounded-full border border-white/10 px-5 py-2 text-xs font-semibold uppercase tracking-[0.24em] text-slate-200 transition hover:bg-white/10"
        >
          Back to site
        </RouterLink>
      </header>

      <section class="relative mx-auto flex w-full max-w-6xl flex-col gap-10 px-6 pb-16 pt-6 lg:flex-row lg:items-start lg:gap-16">
        <div class="flex flex-1 flex-col gap-6">
          <p class="text-xs font-semibold uppercase tracking-[0.32em] text-emerald-200/80">Book a demo</p>
          <h1 class="text-4xl font-semibold leading-tight sm:text-5xl">
            Let's talk about your enterprise HR goals.
          </h1>
          <p class="max-w-xl text-base text-slate-200/80 sm:text-lg">
            Share your team size, rollout timeline, and reporting requirements. We'll tailor a Vegro HR
            plan to your organization.
          </p>

          <div class="grid gap-4 sm:grid-cols-2">
            <div class="rounded-2xl border border-white/10 bg-white/5 p-4">
              <p class="text-xs uppercase tracking-[0.24em] text-slate-400">Response time</p>
              <p class="mt-2 text-sm text-slate-200">Within 1 business day</p>
            </div>
            <div class="rounded-2xl border border-white/10 bg-white/5 p-4">
              <p class="text-xs uppercase tracking-[0.24em] text-slate-400">Demo format</p>
              <p class="mt-2 text-sm text-slate-200">Live walkthrough + Q&A</p>
            </div>
          </div>
        </div>

        <form class="w-full max-w-md rounded-3xl border border-white/10 bg-white/10 p-8 shadow-[0_25px_80px_rgba(15,23,42,0.55)] backdrop-blur" @submit.prevent="submitDemoRequest">
          <div class="flex flex-col gap-2">
            <h2 class="text-2xl font-semibold">Request a demo</h2>
            <p class="text-sm text-slate-200/70">Tell us about your team and we'll follow up.</p>
          </div>

          <div class="mt-6 flex flex-col gap-4 text-sm text-slate-200/80">
            <label class="flex flex-col gap-2">
              <span>Name</span>
              <input
                v-model="formState.name"
                type="text"
                required
                placeholder="Full name"
                class="h-11 rounded-xl border border-white/10 bg-slate-950/40 px-4 text-sm text-white outline-none transition focus:border-emerald-300/70 focus:ring-2 focus:ring-emerald-300/40"
              />
            </label>
            <label class="flex flex-col gap-2">
              <span>Work email</span>
              <input
                v-model="formState.email"
                type="email"
                required
                placeholder="you@company.com"
                class="h-11 rounded-xl border border-white/10 bg-slate-950/40 px-4 text-sm text-white outline-none transition focus:border-emerald-300/70 focus:ring-2 focus:ring-emerald-300/40"
              />
            </label>
            <label class="flex flex-col gap-2">
              <span>Company</span>
              <input
                v-model="formState.company"
                type="text"
                required
                placeholder="Company name"
                class="h-11 rounded-xl border border-white/10 bg-slate-950/40 px-4 text-sm text-white outline-none transition focus:border-emerald-300/70 focus:ring-2 focus:ring-emerald-300/40"
              />
            </label>
            <label class="flex flex-col gap-2">
              <span>Team size</span>
              <input
                v-model="formState.team_size"
                type="text"
                placeholder="e.g. 100-250"
                class="h-11 rounded-xl border border-white/10 bg-slate-950/40 px-4 text-sm text-white outline-none transition focus:border-emerald-300/70 focus:ring-2 focus:ring-emerald-300/40"
              />
            </label>
            <label class="flex flex-col gap-2">
              <span>Notes</span>
              <textarea
                v-model="formState.message"
                rows="4"
                placeholder="Tell us about your HR goals"
                class="rounded-xl border border-white/10 bg-slate-950/40 px-4 py-2 text-sm text-white outline-none transition focus:border-emerald-300/70 focus:ring-2 focus:ring-emerald-300/40"
              ></textarea>
            </label>
          </div>

          <button
            class="mt-6 inline-flex h-11 items-center justify-center rounded-xl bg-emerald-400 text-sm font-semibold text-slate-950 transition hover:bg-emerald-300 disabled:cursor-not-allowed disabled:opacity-60"
            type="submit"
            :disabled="isSubmitting"
          >
            {{ isSubmitting ? 'Submitting...' : 'Submit request' }}
          </button>
          <p v-if="successMessage" class="mt-3 text-xs text-emerald-200">{{ successMessage }}</p>
          <p v-else-if="errorMessage" class="mt-3 text-xs text-rose-200">{{ errorMessage }}</p>
        </form>
      </section>
    </div>
  </div>
</template>

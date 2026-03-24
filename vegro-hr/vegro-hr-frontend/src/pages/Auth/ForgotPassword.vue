<script setup>
import { ref } from 'vue';
import { RouterLink } from 'vue-router';
import authService from '../../services/AuthService';

defineOptions({ name: 'ForgotPasswordPage' });

const email = ref('');
const isSubmitting = ref(false);
const errorMessage = ref('');
const successMessage = ref('');

const submit = async () => {
  errorMessage.value = '';
  successMessage.value = '';
  isSubmitting.value = true;

  try {
    await authService.forgotPassword({ email: email.value });
    successMessage.value = 'Password reset link sent. Check your email inbox.';
  } catch (error) {
    errorMessage.value = error?.response?.data?.message || 'Unable to send reset link.';
  } finally {
    isSubmitting.value = false;
  }
};
</script>

<template>
  <div class="min-h-screen bg-slate-950 text-white">
    <div class="mx-auto flex min-h-screen w-full max-w-md items-center px-6 py-12">
      <form
        class="w-full rounded-3xl border border-white/10 bg-white/10 p-8 shadow-[0_25px_80px_rgba(15,23,42,0.55)] backdrop-blur"
        @submit.prevent="submit"
      >
        <p class="text-xs font-semibold uppercase tracking-[0.32em] text-emerald-200/80">Account Recovery</p>
        <h1 class="mt-3 text-2xl font-semibold">Forgot password</h1>
        <p class="mt-2 text-sm text-slate-200/80">
          Enter your email and we will send a password reset link.
        </p>

        <label class="mt-6 flex flex-col gap-2 text-sm text-slate-200/80">
          <span>Email</span>
          <input
            v-model="email"
            type="email"
            required
            class="h-12 rounded-xl border border-white/10 bg-slate-950/40 px-4 text-sm text-white outline-none transition focus:border-emerald-300/70 focus:ring-2 focus:ring-emerald-300/40"
            placeholder="you@company.com"
          />
        </label>

        <p
          v-if="errorMessage"
          class="mt-4 rounded-xl border border-rose-500/30 bg-rose-500/10 px-4 py-3 text-sm text-rose-100"
        >
          {{ errorMessage }}
        </p>
        <p
          v-if="successMessage"
          class="mt-4 rounded-xl border border-emerald-400/40 bg-emerald-400/10 px-4 py-3 text-sm text-emerald-100"
        >
          {{ successMessage }}
        </p>

        <button
          class="mt-6 inline-flex h-12 w-full items-center justify-center rounded-xl bg-emerald-400 text-sm font-semibold text-slate-950 transition hover:bg-emerald-300 disabled:cursor-not-allowed disabled:opacity-70"
          type="submit"
          :disabled="isSubmitting"
        >
          {{ isSubmitting ? 'Sending...' : 'Send reset link' }}
        </button>

        <RouterLink to="/login" class="mt-4 inline-flex text-sm text-emerald-200 hover:text-emerald-100">
          Back to login
        </RouterLink>
      </form>
    </div>
  </div>
</template>


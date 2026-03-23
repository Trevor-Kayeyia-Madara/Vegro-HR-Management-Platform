<script setup>
import { onMounted, ref } from 'vue';
import apiClient from '../../api/apiClient';

defineOptions({ name: 'PricingPage' });

const plans = ref([
  {
    name: 'Starter',
    slug: 'starter',
    monthly_display: '$2 / employee',
    monthly_note: 'Monthly, min $20',
    annual_note: 'Annual: $19 / employee, min $200',
    tagline: 'HR Essentials · up to 30 employees.',
    features: [
      'Core HR + payroll basic + leave + attendance',
      'Employee self-service + basic dashboards',
      'CSV import/export',
    ],
  },
  {
    name: 'Growth',
    slug: 'growth',
    monthly_display: '$4 / employee',
    monthly_note: 'Monthly, min $80',
    annual_note: 'Annual: $38 / employee, min $800',
    tagline: 'Operational Control · up to 150 employees.',
    features: [
      'ATS + document signing + compliance alerts',
      'Advanced audit logs + multi-manager org chart',
      'Limited report builder (3 saved, no export)',
    ],
  },
  {
    name: 'Pro',
    slug: 'pro',
    monthly_display: '$7 / employee',
    monthly_note: 'Monthly, min $200',
    annual_note: 'Annual: $70 / employee, min $2000',
    tagline: 'Data & Intelligence · up to 500 employees.',
    features: [
      'Full report builder + analytics dashboards',
      'Custom fields + advanced payroll rules',
      'API access + priority support',
    ],
  },
  {
    name: 'Enterprise',
    slug: 'enterprise',
    monthly_display: 'Custom',
    monthly_note: '$1000+/month typical',
    annual_note: '',
    tagline: 'Scale & Integration · unlimited employees.',
    features: [
      'Webhooks + external integrations',
      'Dedicated infrastructure + white-labeling',
      'SLA support + multi-environment controls',
    ],
  },
]);

const loadPlans = async () => {
  try {
    const response = await apiClient.get('/api/public/plans');
    const rows = response?.data?.data || [];
    if (!Array.isArray(rows) || !rows.length) return;

    plans.value = plans.value.map((plan) => {
      const live = rows.find((item) => String(item?.slug || '').toLowerCase() === plan.slug);
      if (!live) return plan;

      const monthlyValue = live?.price_monthly !== null && live?.price_monthly !== undefined
        ? `$${Number(live.price_monthly)} / employee`
        : plan.monthly_display;
      const annualValue = live?.price_annual !== null && live?.price_annual !== undefined
        ? `Annual: $${Number(live.price_annual)} / employee`
        : plan.annual_note;
      const limitLabel = live?.employee_limit
        ? `up to ${Number(live.employee_limit).toLocaleString()} employees.`
        : 'unlimited employees.';
      const taglinePrefix = String(plan.tagline).split('·')[0].trim();

      return {
        ...plan,
        name: live?.name || plan.name,
        monthly_display: monthlyValue,
        annual_note: annualValue,
        tagline: `${taglinePrefix} · ${limitLabel}`,
      };
    });
  } catch {
    // Keep fallback pricing content when API is unavailable.
  }
};

onMounted(() => {
  loadPlans();
});
</script>

<template>
  <div class="min-h-screen bg-slate-950 text-white">
    <div class="relative overflow-hidden">
      <div
        class="pointer-events-none absolute inset-0 bg-[radial-gradient(circle_at_top,_rgba(20,184,166,0.18),_transparent_50%),radial-gradient(circle_at_bottom,_rgba(59,130,246,0.18),_transparent_45%)]"
      ></div>
      <div class="absolute -left-20 top-10 h-64 w-64 rounded-full bg-emerald-400/20 blur-[120px]"></div>
      <div class="absolute -right-24 bottom-16 h-72 w-72 rounded-full bg-blue-500/20 blur-[140px]"></div>

      <header class="relative mx-auto flex w-full max-w-6xl items-center justify-between px-6 py-6">
        <div class="flex items-center gap-3">
          <div class="flex h-10 w-10 items-center justify-center rounded-2xl border border-white/10 bg-white/10 text-sm font-semibold">
            VH
          </div>
          <div>
            <p class="text-xs font-semibold uppercase tracking-[0.28em] text-emerald-200/80">Vegro HR</p>
            <p class="text-sm text-slate-400">Pricing overview</p>
          </div>
        </div>
        <RouterLink
          to="/"
          class="rounded-full border border-white/10 px-5 py-2 text-xs font-semibold uppercase tracking-[0.24em] text-slate-200 transition hover:bg-white/10"
        >
          Back to site
        </RouterLink>
      </header>

      <section class="relative mx-auto flex w-full max-w-6xl flex-col gap-10 px-6 pb-20 pt-8">
        <div class="text-center">
          <p class="text-xs font-semibold uppercase tracking-[0.32em] text-emerald-200/80">Pricing</p>
          <h1 class="mt-3 text-4xl font-semibold">Simple employee-based pricing.</h1>
          <p class="mx-auto mt-4 max-w-3xl text-sm text-slate-300/80">
            Start with HR essentials and scale into analytics, integrations, and enterprise controls.
          </p>
        </div>

        <div class="grid gap-6 lg:grid-cols-4">
          <div
            v-for="plan in plans"
            :key="plan.slug"
            class="rounded-3xl border p-6"
            :class="plan.slug === 'growth'
              ? 'border-emerald-400/30 bg-emerald-400/10'
              : 'border-white/10 bg-white/5'"
          >
            <p class="text-xs uppercase tracking-[0.24em]" :class="plan.slug === 'growth' ? 'text-emerald-200/80' : 'text-slate-400'">
              {{ plan.name }}
            </p>
            <h2 class="mt-3 text-2xl font-semibold" :class="plan.slug === 'growth' ? 'text-emerald-50' : ''">
              {{ plan.monthly_display }}
            </h2>
            <p class="mt-1 text-xs" :class="plan.slug === 'growth' ? 'text-emerald-100/80' : 'text-slate-400'">{{ plan.monthly_note }}</p>
            <p v-if="plan.annual_note" class="mt-1 text-xs" :class="plan.slug === 'growth' ? 'text-emerald-100/80' : 'text-slate-400'">{{ plan.annual_note }}</p>
            <p class="mt-2 text-sm" :class="plan.slug === 'growth' ? 'text-emerald-50/80' : 'text-slate-300/80'">{{ plan.tagline }}</p>
            <ul class="mt-4 space-y-2 text-xs" :class="plan.slug === 'growth' ? 'text-emerald-50/80' : 'text-slate-300/80'">
              <li v-for="item in plan.features" :key="`${plan.slug}-${item}`">{{ item }}</li>
            </ul>
          </div>
        </div>

        <div class="rounded-3xl border border-white/10 bg-white/5 p-8 text-center">
          <h2 class="text-2xl font-semibold">Need a quote now?</h2>
          <p class="mt-3 text-sm text-slate-300/80">
            Share your company size, rollout timeline, and required modules. We will send a tailored proposal.
          </p>
          <div class="mt-6 flex flex-wrap justify-center gap-4">
            <RouterLink
              to="/contact"
              class="rounded-full bg-emerald-400 px-6 py-3 text-xs font-semibold uppercase tracking-[0.24em] text-slate-950 transition hover:bg-emerald-300"
            >
              Request pricing
            </RouterLink>
            <RouterLink
              to="/"
              class="rounded-full border border-white/10 px-6 py-3 text-xs font-semibold uppercase tracking-[0.24em] text-slate-200 transition hover:bg-white/10"
            >
              Explore platform
            </RouterLink>
          </div>
        </div>
      </section>
    </div>
  </div>
</template>

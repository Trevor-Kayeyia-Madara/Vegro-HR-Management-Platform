<script setup>
import { onMounted, ref } from 'vue';
import apiClient from '../../api/apiClient';

defineOptions({ name: 'LandingPage' });

const formState = ref({
  name: '',
  email: '',
  company: '',
  message: '',
});
const isSubmitting = ref(false);
const successMessage = ref('');
const errorMessage = ref('');
const pricingPlans = ref([
  {
    name: 'Starter',
    slug: 'starter',
    monthly_display: '$2 / employee',
    monthly_note: 'Monthly, min $20',
    annual_note: 'Annual: $19 / employee, min $200',
    tagline: 'HR Essentials · up to 30 employees.',
    cta: 'Join the waitlist',
    features: [
      'Core HR, payroll basic, leave, attendance',
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
    cta: 'Join the waitlist',
    features: [
      'ATS + document signing + compliance alerts',
      'Advanced audit logs + multi-manager org',
      'Limited report builder (3 saved reports)',
    ],
  },
  {
    name: 'Pro',
    slug: 'pro',
    monthly_display: '$7 / employee',
    monthly_note: 'Monthly, min $200',
    annual_note: 'Annual: $70 / employee, min $2000',
    tagline: 'Data & Intelligence · up to 500 employees.',
    cta: 'Join the waitlist',
    features: [
      'Full report builder + analytics dashboards',
      'Advanced payroll rules + custom fields',
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
    cta: 'Request pricing',
    features: [
      'Webhooks + external integrations',
      'Dedicated infrastructure + white-labeling',
      'SLA support + multi-environment control',
    ],
  },
]);

const loadPublicPlans = async () => {
  try {
    const response = await apiClient.get('/api/public/plans');
    const rows = response?.data?.data || [];
    if (!Array.isArray(rows) || !rows.length) return;

    pricingPlans.value = pricingPlans.value.map((plan) => {
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

const submitLead = async () => {
  if (isSubmitting.value) return;
  successMessage.value = '';
  errorMessage.value = '';
  isSubmitting.value = true;
  try {
    await apiClient.post('/api/lead-capture', {
      name: formState.value.name,
      email: formState.value.email,
      company: formState.value.company,
      message: formState.value.message,
      source: 'landing-page',
    });
    successMessage.value = 'Thanks. We will contact you at ' + formState.value.email + '.';
    formState.value = { name: '', email: '', company: '', message: '' };
  } catch (error) {
    errorMessage.value = error?.response?.data?.message || 'Unable to submit. Please try again.';
  } finally {
    isSubmitting.value = false;
  }
};

onMounted(() => {
  loadPublicPlans();
});
</script>

<template>
  <div class="landing-root min-h-screen bg-slate-950 text-white">
    <div class="relative overflow-hidden">
      <div
        class="pointer-events-none absolute inset-0 bg-[radial-gradient(circle_at_top,_rgba(16,185,129,0.2),_transparent_50%),radial-gradient(circle_at_bottom,_rgba(59,130,246,0.18),_transparent_55%)]"
      ></div>
      <div class="absolute -left-24 top-12 h-72 w-72 rounded-full bg-emerald-400/20 blur-[120px]"></div>
      <div class="absolute -right-24 bottom-10 h-80 w-80 rounded-full bg-blue-500/20 blur-[140px]"></div>

      <header class="relative mx-auto flex w-full max-w-6xl items-center justify-between px-6 py-6">
        <div class="flex items-center gap-3">
          <div class="flex h-10 w-10 items-center justify-center rounded-2xl border border-white/10 bg-white/10 text-sm font-semibold">
            V
          </div>
          <div>
            <p class="text-xs font-semibold uppercase tracking-[0.28em] text-emerald-200/80">
              Vegro HR
            </p>
            <p class="text-sm text-slate-400">Workforce operations suite</p>
          </div>
        </div>
        <nav class="hidden items-center gap-6 text-xs uppercase tracking-[0.24em] text-slate-300/80 md:flex">
          <a href="#features" class="transition hover:text-emerald-200">Features</a>
          <a href="#pricing" class="transition hover:text-emerald-200">Pricing</a>
          <a href="#demo" class="transition hover:text-emerald-200">Demo</a>
          <a href="#contact" class="transition hover:text-emerald-200">Contact</a>
        </nav>
        <div class="flex items-center gap-3">
          <a
            href="#contact"
            class="rounded-full border border-white/10 px-5 py-2 text-xs font-semibold uppercase tracking-[0.24em] text-slate-200 transition hover:bg-white/10"
          >
            Join the demo waitlist
          </a>
          <a
            href="#pricing"
            class="rounded-full border border-emerald-300/40 bg-emerald-300/10 px-5 py-2 text-xs font-semibold uppercase tracking-[0.24em] text-emerald-200 transition hover:bg-emerald-300/20"
          >
            See plans
          </a>
        </div>
      </header>

      <section class="relative mx-auto flex w-full max-w-6xl flex-col gap-12 px-6 pb-12 pt-6 lg:flex-row lg:items-center lg:gap-16">
        <div class="flex flex-1 flex-col gap-6">
          <p class="text-xs font-semibold uppercase tracking-[0.32em] text-emerald-200/80">
            Built for modern HR teams
          </p>
          <h1 class="hero-title text-4xl font-semibold leading-tight sm:text-5xl">
            HR operations that feel premium, fast, and fully under control.
          </h1>
          <p class="max-w-xl text-base text-slate-200/80 sm:text-lg">
            Vegro HR brings payroll, attendance, leave, and employee records into one streamlined platform.
            Automate the busywork, keep data accurate, and give leadership real-time visibility.
          </p>
          <div class="flex flex-wrap gap-3 text-xs font-medium uppercase tracking-[0.24em] text-slate-300/70">
            <span class="rounded-full border border-white/10 bg-white/5 px-4 py-2">Payroll automation</span>
            <span class="rounded-full border border-white/10 bg-white/5 px-4 py-2">Dynamic reporting</span>
            <span class="rounded-full border border-white/10 bg-white/5 px-4 py-2">Audit-ready controls</span>
          </div>
          <div class="flex flex-wrap gap-4">
            <a
              href="#contact"
              class="rounded-full bg-emerald-400 px-6 py-3 text-xs font-semibold uppercase tracking-[0.24em] text-slate-950 transition hover:bg-emerald-300"
            >
              Join the demo waitlist
            </a>
            <a
              href="#pricing"
              class="rounded-full border border-white/10 px-6 py-3 text-xs font-semibold uppercase tracking-[0.24em] text-slate-200 transition hover:bg-white/10"
            >
              View pricing
            </a>
          </div>
        </div>

        <div class="w-full max-w-md rounded-3xl border border-white/10 bg-white/5 p-6 shadow-[0_30px_90px_rgba(15,23,42,0.65)] backdrop-blur">
          <p class="text-xs font-semibold uppercase tracking-[0.24em] text-slate-400">Live demo preview</p>
          <div class="mt-4 grid gap-4">
            <div class="rounded-2xl border border-white/10 bg-slate-950/60 p-4">
              <p class="text-sm font-semibold">Payroll command</p>
              <p class="mt-2 text-xs text-slate-400">Automated deductions, approvals, and audit trail.</p>
            </div>
            <div class="rounded-2xl border border-white/10 bg-slate-950/60 p-4">
              <p class="text-sm font-semibold">Attendance intelligence</p>
              <p class="mt-2 text-xs text-slate-400">Exceptions, overtime, and compliance visibility.</p>
            </div>
            <div class="rounded-2xl border border-white/10 bg-slate-950/60 p-4">
              <p class="text-sm font-semibold">Dynamic reporting</p>
              <p class="mt-2 text-xs text-slate-400">Build dashboards by company, team, or cycle.</p>
            </div>
          </div>
        </div>
      </section>
    </div>

    <section id="features" class="mx-auto w-full max-w-6xl px-6 pb-16">
      <div class="grid gap-6 lg:grid-cols-3">
        <div class="rounded-3xl border border-white/10 bg-white/5 p-6">
          <p class="text-xs uppercase tracking-[0.24em] text-slate-400">Core HR</p>
          <h2 class="mt-3 text-xl font-semibold">Employee and department control.</h2>
          <p class="mt-2 text-sm text-slate-300/80">
            Centralize people data, roles, and department hierarchies in one interface.
          </p>
        </div>
        <div class="rounded-3xl border border-white/10 bg-white/5 p-6">
          <p class="text-xs uppercase tracking-[0.24em] text-slate-400">Payroll</p>
          <h2 class="mt-3 text-xl font-semibold">Tax-aware payroll automation.</h2>
          <p class="mt-2 text-sm text-slate-300/80">
            Recurring cycles, tax profiles, and approvals with audit visibility.
          </p>
        </div>
        <div class="rounded-3xl border border-white/10 bg-white/5 p-6">
          <p class="text-xs uppercase tracking-[0.24em] text-slate-400">Attendance</p>
          <h2 class="mt-3 text-xl font-semibold">Attendance and leave in sync.</h2>
          <p class="mt-2 text-sm text-slate-300/80">
            Track attendance, leave, and overtime with export-ready reports.
          </p>
        </div>
      </div>

      <div class="mt-8 grid gap-6 lg:grid-cols-3">
        <div class="rounded-3xl border border-white/10 bg-white/5 p-6">
          <p class="text-xs uppercase tracking-[0.24em] text-slate-400">Reporting</p>
          <h2 class="mt-3 text-xl font-semibold">Dashboards your teams actually use.</h2>
          <p class="mt-2 text-sm text-slate-300/80">
            Build custom views with filters, exports, and live workforce metrics.
          </p>
        </div>
        <div class="rounded-3xl border border-white/10 bg-white/5 p-6">
          <p class="text-xs uppercase tracking-[0.24em] text-slate-400">Operations</p>
          <h2 class="mt-3 text-xl font-semibold">One flow from hiring to payroll.</h2>
          <p class="mt-2 text-sm text-slate-300/80">
            Connect employee data, attendance, leave, and payroll without spreadsheets.
          </p>
        </div>
        <div class="rounded-3xl border border-white/10 bg-white/5 p-6">
          <p class="text-xs uppercase tracking-[0.24em] text-slate-400">Security</p>
          <h2 class="mt-3 text-xl font-semibold">Audit-ready operations.</h2>
          <p class="mt-2 text-sm text-slate-300/80">
            Audit logs, role governance, and policy-driven access for teams.
          </p>
        </div>
      </div>
    </section>

    <section id="demo" class="mx-auto w-full max-w-6xl px-6 pb-16">
      <div class="rounded-3xl border border-white/10 bg-white/5 p-8">
        <div class="flex flex-col gap-6 lg:flex-row lg:items-center lg:justify-between">
          <div>
            <p class="text-xs font-semibold uppercase tracking-[0.24em] text-emerald-200/80">Demo environment</p>
            <h2 class="mt-2 text-3xl font-semibold">See Vegro HR in action.</h2>
            <p class="mt-3 max-w-2xl text-sm text-slate-300/80">
              Walk through payroll, attendance, and reporting workflows with realistic sample data.
              We will tailor the demo to your team and goals.
            </p>
          </div>
          <div class="flex flex-wrap gap-3 text-xs font-medium uppercase tracking-[0.24em] text-slate-300/70">
            <span class="rounded-full border border-white/10 bg-white/5 px-4 py-2">Guided tour</span>
            <span class="rounded-full border border-white/10 bg-white/5 px-4 py-2">Sample data</span>
            <span class="rounded-full border border-white/10 bg-white/5 px-4 py-2">Team-ready</span>
          </div>
        </div>
      </div>
    </section>

    <section class="mx-auto w-full max-w-6xl px-6 pb-16">
      <div class="rounded-3xl border border-white/10 bg-white/5 p-8">
        <div class="flex flex-col gap-6 lg:flex-row lg:items-start lg:justify-between">
          <div>
            <p class="text-xs font-semibold uppercase tracking-[0.24em] text-emerald-200/80">Data protection</p>
            <h2 class="mt-2 text-3xl font-semibold">Global IT protocols for cloud security.</h2>
            <p class="mt-3 max-w-2xl text-sm text-slate-300/80">
              Vegro HR is built with enterprise-grade protection for cloud hosting, ensuring data
              confidentiality, integrity, and availability across every workflow.
            </p>
          </div>
          <div class="flex flex-wrap gap-3 text-xs font-medium uppercase tracking-[0.24em] text-slate-300/70">
            <span class="rounded-full border border-white/10 bg-white/5 px-4 py-2">Encryption in transit</span>
            <span class="rounded-full border border-white/10 bg-white/5 px-4 py-2">Encryption at rest</span>
            <span class="rounded-full border border-white/10 bg-white/5 px-4 py-2">Role-based access</span>
          </div>
        </div>
        <div class="mt-6 grid gap-4 lg:grid-cols-3">
          <div class="rounded-2xl border border-white/10 bg-slate-950/70 p-5">
            <p class="text-xs uppercase tracking-[0.24em] text-slate-400">Access governance</p>
            <p class="mt-3 text-sm text-slate-300/80">
              Least-privilege roles, audit logs, and controlled admin access to safeguard HR data.
            </p>
          </div>
          <div class="rounded-2xl border border-white/10 bg-slate-950/70 p-5">
            <p class="text-xs uppercase tracking-[0.24em] text-slate-400">Data resilience</p>
            <p class="mt-3 text-sm text-slate-300/80">
              Automated backups, monitoring, and recovery workflows to maintain uptime and trust.
            </p>
          </div>
          <div class="rounded-2xl border border-white/10 bg-slate-950/70 p-5">
            <p class="text-xs uppercase tracking-[0.24em] text-slate-400">Cloud security</p>
            <p class="mt-3 text-sm text-slate-300/80">
              Secure infrastructure practices aligned with modern cloud hosting requirements.
            </p>
          </div>
        </div>
      </div>
    </section>

        <section id="pricing" class="mx-auto w-full max-w-6xl px-6 pb-16">
      <div class="flex flex-col gap-6">
        <div>
          <p class="text-xs font-semibold uppercase tracking-[0.24em] text-emerald-200/80">Pricing</p>
          <h2 class="mt-2 text-3xl font-semibold">Plans for every growth stage.</h2>
          <p class="mt-3 max-w-2xl text-sm text-slate-300/80">
            Transparent employee-based pricing with clear limits and upgrade paths.
          </p>
        </div>
        <div class="grid gap-6 lg:grid-cols-4">
          <div
            v-for="plan in pricingPlans"
            :key="plan.slug"
            class="rounded-3xl border p-6"
            :class="plan.slug === 'growth'
              ? 'border-emerald-400/40 bg-emerald-400/10'
              : 'border-white/10 bg-white/5'"
          >
            <p
              class="text-xs uppercase tracking-[0.24em]"
              :class="plan.slug === 'growth' ? 'text-emerald-200/80' : 'text-slate-400'"
            >
              {{ plan.name }}
            </p>
            <h3
              class="mt-3 text-2xl font-semibold"
              :class="plan.slug === 'growth' ? 'text-emerald-50' : ''"
            >
              {{ plan.monthly_display }}
            </h3>
            <p class="mt-1 text-xs" :class="plan.slug === 'growth' ? 'text-emerald-100/80' : 'text-slate-400'">
              {{ plan.monthly_note }}
            </p>
            <p
              v-if="plan.annual_note"
              class="mt-1 text-xs"
              :class="plan.slug === 'growth' ? 'text-emerald-100/80' : 'text-slate-400'"
            >
              {{ plan.annual_note }}
            </p>
            <p class="mt-2 text-sm" :class="plan.slug === 'growth' ? 'text-emerald-50/80' : 'text-slate-300/80'">
              {{ plan.tagline }}
            </p>
            <ul class="mt-4 space-y-2 text-sm" :class="plan.slug === 'growth' ? 'text-emerald-50/80' : 'text-slate-300/80'">
              <li v-for="item in plan.features" :key="`${plan.slug}-${item}`">{{ item }}</li>
            </ul>
            <a
              href="#contact"
              class="mt-6 inline-flex rounded-full px-4 py-2 text-xs font-semibold uppercase tracking-[0.24em]"
              :class="plan.slug === 'growth'
                ? 'bg-emerald-400 text-slate-950'
                : 'border border-white/10 text-slate-200 transition hover:bg-white/10'"
            >
              {{ plan.cta }}
            </a>
          </div>
        </div>
      </div>
    </section>

    <section id="contact" class="mx-auto w-full max-w-6xl px-6 pb-20">
      <div class="rounded-3xl border border-white/10 bg-gradient-to-br from-emerald-400/10 via-slate-950/80 to-blue-500/10 p-10">
        <div class="grid gap-8 lg:grid-cols-[1.2fr_0.8fr] lg:items-center">
          <div>
            <p class="text-xs font-semibold uppercase tracking-[0.28em] text-emerald-200/80">
              Demo waitlist
            </p>
            <h2 class="mt-3 text-3xl font-semibold">Join the demo waitlist.</h2>
            <p class="mt-3 text-sm text-slate-200/80">
              Share your details and we will reach out with access and a guided walkthrough.
            </p>
          </div>
          <form class="flex flex-col gap-3 rounded-2xl border border-white/10 bg-slate-950/60 p-6" @submit.prevent="submitLead">
            <input
              v-model="formState.name"
              class="h-10 rounded-xl border border-white/10 bg-slate-950/40 px-3 text-sm outline-none"
              placeholder="Full name"
              required
            />
            <input
              v-model="formState.email"
              class="h-10 rounded-xl border border-white/10 bg-slate-950/40 px-3 text-sm outline-none"
              placeholder="Work email"
              type="email"
              required
            />
            <input
              v-model="formState.company"
              class="h-10 rounded-xl border border-white/10 bg-slate-950/40 px-3 text-sm outline-none"
              placeholder="Company name"
            />
            <textarea
              rows="3"
              class="rounded-xl border border-white/10 bg-slate-950/40 px-3 py-2 text-sm outline-none"
              placeholder="What would you like to see in the demo?"
              v-model="formState.message"
            ></textarea>
            <button
              class="rounded-full bg-emerald-400 px-4 py-2 text-xs font-semibold uppercase tracking-[0.24em] text-slate-950 disabled:cursor-not-allowed disabled:opacity-60"
              type="submit"
              :disabled="isSubmitting"
            >
              {{ isSubmitting ? 'Submitting...' : 'Join waitlist' }}
            </button>
            <p v-if="successMessage" class="text-xs text-emerald-200">{{ successMessage }}</p>
            <p v-else-if="errorMessage" class="text-xs text-rose-200">{{ errorMessage }}</p>
            <p v-else class="text-xs text-slate-400">
              We will contact you by email with demo access details.
            </p>
          </form>
        </div>
      </div>
    </section>

    <footer class="border-t border-white/10 bg-slate-950/80">
      <div class="mx-auto flex w-full max-w-6xl flex-col gap-6 px-6 py-10 sm:flex-row sm:items-center sm:justify-between">
        <div>
          <p class="text-xs font-semibold uppercase tracking-[0.28em] text-emerald-200/80">Vegro HR</p>
          <p class="mt-2 text-sm text-slate-400">
            Executive workforce platform for modern teams.
          </p>
          <p class="mt-3 text-xs text-slate-500">Support: support@vegrohr.com</p>
          <a
            href="https://www.invodtechltd.com/"
            target="_blank"
            rel="noopener noreferrer"
            class="mt-3 inline-flex items-center gap-2 rounded-full border border-emerald-300/30 bg-emerald-300/10 px-4 py-2 text-[11px] font-semibold uppercase tracking-[0.24em] text-emerald-200 transition hover:bg-emerald-300/20"
          >
            Built by Invodtech Ltd
          </a>
        </div>
        <div class="flex flex-wrap gap-4 text-xs uppercase tracking-[0.24em] text-slate-400">
          <a href="#pricing" class="transition hover:text-emerald-200">Pricing</a>
          <a href="#features" class="transition hover:text-emerald-200">Features</a>
          <RouterLink to="/contact" class="transition hover:text-emerald-200">Contact</RouterLink>
        </div>
      </div>
    </footer>
  </div>
</template>

<style scoped>
@import url('https://fonts.googleapis.com/css2?family=Fraunces:wght@500;600;700&family=Space+Grotesk:wght@400;500;600;700&display=swap');

.landing-root {
  font-family: 'Space Grotesk', 'Manrope', 'Segoe UI', sans-serif;
}

.hero-title {
  font-family: 'Fraunces', 'Space Grotesk', serif;
}
</style>












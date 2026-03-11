<script setup>
defineOptions({ name: 'LandingPage' });
</script>

<template>
  <div class="min-h-screen bg-slate-950 text-white">
    <div class="relative overflow-hidden">
      <div
        class="pointer-events-none absolute inset-0 bg-[radial-gradient(circle_at_top,_rgba(20,184,166,0.2),_transparent_50%),radial-gradient(circle_at_bottom,_rgba(59,130,246,0.2),_transparent_45%)]"
      ></div>
      <div class="absolute -left-16 top-20 h-72 w-72 rounded-full bg-emerald-400/20 blur-[120px]"></div>
      <div class="absolute -right-24 bottom-10 h-80 w-80 rounded-full bg-blue-500/20 blur-[140px]"></div>

      <header class="relative mx-auto flex w-full max-w-6xl items-center justify-between px-6 py-6">
        <div class="flex items-center gap-3">
          <div class="flex h-10 w-10 items-center justify-center rounded-2xl border border-white/10 bg-white/10 text-sm font-semibold">
            V
          </div>
          <div>
            <p class="text-xs font-semibold uppercase tracking-[0.28em] text-emerald-200/80">
              Vegro
            </p>
            <p class="text-sm text-slate-400">Workforce cloud platform</p>
          </div>
        </div>
        <div class="flex items-center gap-3">
          <RouterLink
            to="/contact"
            class="rounded-full border border-white/10 px-5 py-2 text-xs font-semibold uppercase tracking-[0.24em] text-slate-200 transition hover:bg-white/10"
          >
            Book a demo
          </RouterLink>
          <RouterLink
            to="/pricing"
            class="rounded-full border border-emerald-300/40 bg-emerald-300/10 px-5 py-2 text-xs font-semibold uppercase tracking-[0.24em] text-emerald-200 transition hover:bg-emerald-300/20"
          >
            See plans
          </RouterLink>
        </div>
      </header>

      <section class="relative mx-auto flex w-full max-w-6xl flex-col gap-12 px-6 pb-12 pt-6 lg:flex-row lg:items-center lg:gap-16">
        <div class="flex flex-1 flex-col gap-6">
          <p class="text-xs font-semibold uppercase tracking-[0.32em] text-emerald-200/80">
            Built for enterprise teams
          </p>
          <h1 class="text-4xl font-semibold leading-tight sm:text-5xl">
            Run every HR workflow from one executive command center.
          </h1>
          <p class="max-w-xl text-base text-slate-200/80 sm:text-lg">
            Vegro centralizes payroll, attendance, leave, and employee operations while giving leadership
            instant insight through dynamic, customized reporting.
          </p>
          <div class="flex flex-wrap gap-3 text-xs font-medium uppercase tracking-[0.24em] text-slate-300/70">
            <span class="rounded-full border border-white/10 bg-white/5 px-4 py-2">Enterprise ready</span>
            <span class="rounded-full border border-white/10 bg-white/5 px-4 py-2">Custom reporting</span>
            <span class="rounded-full border border-white/10 bg-white/5 px-4 py-2">Enterprise-grade</span>
          </div>
        </div>

        <div class="w-full max-w-md rounded-3xl border border-white/10 bg-white/5 p-6 shadow-[0_30px_90px_rgba(15,23,42,0.65)] backdrop-blur">
          <p class="text-xs font-semibold uppercase tracking-[0.24em] text-slate-400">Executive view</p>
          <div class="mt-4 grid gap-4">
            <div class="rounded-2xl border border-white/10 bg-slate-950/60 p-4">
              <p class="text-sm font-semibold">Operations pulse</p>
              <p class="mt-2 text-xs text-slate-400">Live staffing, payroll, and attendance signals.</p>
            </div>
            <div class="rounded-2xl border border-white/10 bg-slate-950/60 p-4">
              <p class="text-sm font-semibold">Custom analytics</p>
              <p class="mt-2 text-xs text-slate-400">Report views per company, region, or team.</p>
            </div>
            <div class="rounded-2xl border border-white/10 bg-slate-950/60 p-4">
              <p class="text-sm font-semibold">Employee experience</p>
              <p class="mt-2 text-xs text-slate-400">Self-service payslips, leave, and profiles.</p>
            </div>
          </div>
        </div>
      </section>
    </div>

    <section class="mx-auto w-full max-w-6xl px-6 pb-16">
      <div class="grid gap-6 lg:grid-cols-3">
        <div class="rounded-3xl border border-white/10 bg-white/5 p-6">
          <p class="text-xs uppercase tracking-[0.24em] text-slate-400">Unified stack</p>
          <h2 class="mt-3 text-xl font-semibold">All core HR modules, connected.</h2>
          <p class="mt-2 text-sm text-slate-300/80">
            Employees, payroll, attendance, leave, and departments in one cohesive system.
          </p>
        </div>
        <div class="rounded-3xl border border-white/10 bg-white/5 p-6">
          <p class="text-xs uppercase tracking-[0.24em] text-slate-400">Dynamic analytics</p>
          <h2 class="mt-3 text-xl font-semibold">Reports built around your reality.</h2>
          <p class="mt-2 text-sm text-slate-300/80">
            Build reports by company, department, location, or payroll cycle ? no rigid templates.
          </p>
        </div>
        <div class="rounded-3xl border border-white/10 bg-white/5 p-6">
          <p class="text-xs uppercase tracking-[0.24em] text-slate-400">Leadership clarity</p>
          <h2 class="mt-3 text-xl font-semibold">Decisions backed by live data.</h2>
          <p class="mt-2 text-sm text-slate-300/80">
            Real-time dashboards surface trends, spend, and workforce health.
          </p>
        </div>
      </div>
    </section>

    <section class="mx-auto w-full max-w-6xl px-6 pb-16">
      <div class="rounded-3xl border border-white/10 bg-white/5 p-8">
        <div class="flex flex-col gap-6 lg:flex-row lg:items-center lg:justify-between">
          <div>
            <p class="text-xs font-semibold uppercase tracking-[0.24em] text-emerald-200/80">Reporting</p>
            <h2 class="mt-2 text-3xl font-semibold">Dynamic reports your teams actually use.</h2>
            <p class="mt-3 max-w-2xl text-sm text-slate-300/80">
              Build reports by company, business unit, region, or payroll cycle ? then slice deeper with
              custom filters. Every chart updates in real time.
            </p>
          </div>
          <div class="flex flex-wrap gap-3 text-xs font-medium uppercase tracking-[0.24em] text-slate-300/70">
            <span class="rounded-full border border-white/10 bg-white/5 px-4 py-2">Custom Filters</span>
            <span class="rounded-full border border-white/10 bg-white/5 px-4 py-2">Export-ready</span>
            <span class="rounded-full border border-white/10 bg-white/5 px-4 py-2">Live Trends</span>
          </div>
        </div>

        <div class="mt-8 grid gap-6 lg:grid-cols-3">
          <div class="rounded-2xl border border-white/10 bg-slate-950/70 p-5">
            <p class="text-xs uppercase tracking-[0.24em] text-slate-400">Workforce cost</p>
            <div class="mt-4 h-28 rounded-xl bg-gradient-to-r from-emerald-400/30 via-transparent to-blue-500/30"></div>
            <p class="mt-4 text-sm text-slate-300/80">
              Monitor payroll expense variance by department and payroll cycle.
            </p>
          </div>
          <div class="rounded-2xl border border-white/10 bg-slate-950/70 p-5">
            <p class="text-xs uppercase tracking-[0.24em] text-slate-400">Attendance pulse</p>
            <div class="mt-4 h-28 rounded-xl bg-gradient-to-r from-blue-500/30 via-transparent to-emerald-400/30"></div>
            <p class="mt-4 text-sm text-slate-300/80">
              Spot attendance anomalies and overtime hotspots instantly.
            </p>
          </div>
          <div class="rounded-2xl border border-white/10 bg-slate-950/70 p-5">
            <p class="text-xs uppercase tracking-[0.24em] text-slate-400">Leave forecast</p>
            <div class="mt-4 h-28 rounded-xl bg-gradient-to-r from-emerald-400/30 via-transparent to-blue-500/30"></div>
            <p class="mt-4 text-sm text-slate-300/80">
              Predict coverage gaps by combining leave trends and staffing ratios.
            </p>
          </div>
        </div>
      </div>
    </section>

    <section class="mx-auto w-full max-w-6xl px-6 pb-16">
      <div class="rounded-3xl border border-white/10 bg-white/5 p-8">
        <div class="flex flex-col gap-6 lg:flex-row lg:items-end lg:justify-between">
          <div>
            <p class="text-xs font-semibold uppercase tracking-[0.24em] text-emerald-200/80">
              Why Vegro
            </p>
            <h2 class="mt-2 text-3xl font-semibold">What legacy HR platforms still miss.</h2>
            <p class="mt-3 max-w-2xl text-sm text-slate-300/80">
              Most HR systems lock you into static reports and rigid workflows. Vegro is built as a
              enterprise SaaS with dynamic reporting, configurable workflows, and executive visibility
              from day one.
            </p>
          </div>
          <div class="flex flex-wrap gap-3 text-xs font-medium uppercase tracking-[0.24em] text-slate-300/70">
            <span class="rounded-full border border-white/10 bg-white/5 px-4 py-2">Dynamic reports</span>
            <span class="rounded-full border border-white/10 bg-white/5 px-4 py-2">SaaS first</span>
            <span class="rounded-full border border-white/10 bg-white/5 px-4 py-2">Custom workflows</span>
          </div>
        </div>

        <div class="mt-8 grid gap-4 lg:grid-cols-3">
          <div class="rounded-2xl border border-white/10 bg-slate-950/70 p-5">
            <p class="text-xs uppercase tracking-[0.24em] text-slate-400">Legacy HR</p>
            <ul class="mt-3 space-y-2 text-sm text-slate-300/80">
              <li>Static, prebuilt reports</li>
              <li>Limited enterprise scaling</li>
              <li>Slow customization cycles</li>
            </ul>
          </div>
          <div class="rounded-2xl border border-emerald-400/40 bg-emerald-400/10 p-5">
            <p class="text-xs uppercase tracking-[0.24em] text-emerald-200/80">Vegro</p>
            <ul class="mt-3 space-y-2 text-sm text-emerald-50/90">
              <li>Dynamic, custom reporting</li>
              <li>Enterprise SaaS foundation</li>
              <li>Configurable workflows & fields</li>
            </ul>
          </div>
          <div class="rounded-2xl border border-white/10 bg-slate-950/70 p-5">
            <p class="text-xs uppercase tracking-[0.24em] text-slate-400">Impact</p>
            <ul class="mt-3 space-y-2 text-sm text-slate-300/80">
              <li>Faster leadership decisions</li>
              <li>Scales with multiple companies</li>
              <li>Better employee experience</li>
            </ul>
          </div>
        </div>
      </div>
    </section>

    <section class="mx-auto w-full max-w-6xl px-6 pb-20">
      <div class="rounded-3xl border border-white/10 bg-gradient-to-br from-emerald-400/10 via-slate-950/80 to-blue-500/10 p-10">
        <div class="grid gap-8 lg:grid-cols-[1.4fr_0.6fr] lg:items-center">
          <div>
            <p class="text-xs font-semibold uppercase tracking-[0.28em] text-emerald-200/80">
              Ready to scale
            </p>
            <h2 class="mt-3 text-3xl font-semibold">Launch Vegro in your organization.</h2>
            <p class="mt-3 text-sm text-slate-200/80">
              Deploy a modern HR suite with executive visibility, scalable operations, and a premium
              employee experience. Pricing plans and billing automation are coming next.
            </p>
          </div>
          <div class="flex flex-col gap-3">
            <RouterLink
              to="/contact"
              class="rounded-full bg-emerald-400 px-6 py-3 text-xs font-semibold uppercase tracking-[0.24em] text-slate-950 transition hover:bg-emerald-300"
            >
              Request a private demo
            </RouterLink>
            <button
              class="rounded-full border border-white/10 px-6 py-3 text-xs font-semibold uppercase tracking-[0.24em] text-slate-200 transition hover:bg-white/10"
              type="button"
            >
              Get pricing updates
            </button>
          </div>
        </div>
      </div>
    </section>

    <footer class="border-t border-white/10 bg-slate-950/80">
      <div class="mx-auto flex w-full max-w-6xl flex-col gap-6 px-6 py-10 sm:flex-row sm:items-center sm:justify-between">
        <div>
          <p class="text-xs font-semibold uppercase tracking-[0.28em] text-emerald-200/80">Vegro</p>
          <p class="mt-2 text-sm text-slate-400">
            Executive workforce platform for enterprise teams.
          </p>
              <a
          href="https://www.invodtechltd.com/"
          target="_blank"
          rel="noopener noreferrer"
          class="inline-flex items-center gap-2 rounded-full border border-emerald-300/30 bg-emerald-300/10 px-4 py-2 text-[11px] font-semibold uppercase tracking-[0.24em] text-emerald-200 transition hover:bg-emerald-300/20"
        >
          Built by Invodtech Ltd
        </a>
</div>
        <div class="flex flex-wrap gap-4 text-xs uppercase tracking-[0.24em] text-slate-400">
          <RouterLink to="/pricing" class="transition hover:text-emerald-200">Pricing</RouterLink>
          <button class="transition hover:text-emerald-200" type="button">Security</button>
          <button class="transition hover:text-emerald-200" type="button">Docs</button>
          <RouterLink to="/contact" class="transition hover:text-emerald-200">Contact</RouterLink>
        </div>
      </div>
    </footer>
  </div>
</template>

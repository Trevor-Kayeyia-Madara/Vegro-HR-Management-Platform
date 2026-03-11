<script setup>
defineOptions({ name: 'PricingPage' });
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

      <section class="relative mx-auto flex w-full max-w-5xl flex-col gap-10 px-6 pb-20 pt-8">
        <div class="text-center">
          <p class="text-xs font-semibold uppercase tracking-[0.32em] text-emerald-200/80">Pricing</p>
          <h1 class="mt-3 text-4xl font-semibold">Enterprise pricing, tailored to your scale.</h1>
          <p class="mx-auto mt-4 max-w-2xl text-sm text-slate-300/80">
            Vegro HR is in active rollout. Pricing tiers and billing automation are coming soon. Until then,
            we provide custom quotes based on the companies, teams, and modules you need.
          </p>
        </div>

        <div class="grid gap-6 md:grid-cols-3">
          <div class="rounded-3xl border border-white/10 bg-white/5 p-6">
            <p class="text-xs uppercase tracking-[0.24em] text-slate-400">Starter</p>
            <h2 class="mt-3 text-2xl font-semibold">Launch teams</h2>
            <p class="mt-2 text-sm text-slate-300/80">
              Ideal for growing companies needing a unified HR workspace.
            </p>
            <ul class="mt-4 space-y-2 text-xs text-slate-300/80">
              <li>Core HR modules</li>
              <li>Role-based access</li>
              <li>Standard analytics</li>
            </ul>
          </div>
          <div class="rounded-3xl border border-emerald-400/30 bg-emerald-400/10 p-6">
            <p class="text-xs uppercase tracking-[0.24em] text-emerald-200/80">Growth</p>
            <h2 class="mt-3 text-2xl font-semibold">Multi-company</h2>
            <p class="mt-2 text-sm text-emerald-50/80">
              For organizations managing multiple entities and regional teams.
            </p>
            <ul class="mt-4 space-y-2 text-xs text-emerald-50/80">
              <li>Advanced access controls</li>
              <li>Multi-tenant dashboards</li>
              <li>Custom reporting</li>
            </ul>
          </div>
          <div class="rounded-3xl border border-white/10 bg-white/5 p-6">
            <p class="text-xs uppercase tracking-[0.24em] text-slate-400">Enterprise</p>
            <h2 class="mt-3 text-2xl font-semibold">Global HQ</h2>
            <p class="mt-2 text-sm text-slate-300/80">
              Built for enterprises with complex governance and compliance needs.
            </p>
            <ul class="mt-4 space-y-2 text-xs text-slate-300/80">
              <li>Dedicated success team</li>
              <li>Custom integrations</li>
              <li>Security & audit exports</li>
            </ul>
          </div>
        </div>

        <div class="rounded-3xl border border-white/10 bg-white/5 p-8 text-center">
          <h2 class="text-2xl font-semibold">Need a quote now?</h2>
          <p class="mt-3 text-sm text-slate-300/80">
            Share your company size, required modules, and rollout timeline. We’ll respond with a tailored
            proposal.
          </p>
          <div class="mt-6 flex flex-wrap justify-center gap-4">
            <button
              class="rounded-full bg-emerald-400 px-6 py-3 text-xs font-semibold uppercase tracking-[0.24em] text-slate-950 transition hover:bg-emerald-300"
              type="button"
            >
              Request pricing
            </button>
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

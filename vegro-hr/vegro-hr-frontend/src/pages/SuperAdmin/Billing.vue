<script setup>
import { computed, onMounted, ref } from 'vue';
import apiClient from '../../api/apiClient';
import { formatDate } from '../../utils/dateFormat';

defineOptions({ name: 'SuperAdminBillingPage' });

const isLoading = ref(true);
const errorMessage = ref('');
const plans = ref([]);
const subscriptions = ref([]);

const loadBilling = async () => {
  isLoading.value = true;
  errorMessage.value = '';
  try {
    const [plansResponse, subscriptionsResponse] = await Promise.all([
      apiClient.get('/api/plans'),
      apiClient.get('/api/subscriptions'),
    ]);
    plans.value = plansResponse?.data?.data || [];
    subscriptions.value = subscriptionsResponse?.data?.data || [];
  } catch (error) {
    errorMessage.value = error?.response?.data?.message || 'Unable to load billing data.';
  } finally {
    isLoading.value = false;
  }
};

const planLookup = computed(() => {
  const map = {};
  plans.value.forEach((plan) => {
    map[plan.id] = plan;
  });
  return map;
});

const activeSubscriptions = computed(() => subscriptions.value.filter((sub) => sub.status === 'active'));
const totalSubscribers = computed(() => activeSubscriptions.value.length);
const estimatedMrr = computed(() =>
  activeSubscriptions.value.reduce((sum, sub) => {
    const plan = sub.plan || planLookup.value[sub.plan_id];
    const price = Number(plan?.price || 0);
    return sum + price;
  }, 0),
);

const formatMoney = (value) => Number(value || 0).toLocaleString();

onMounted(loadBilling);
</script>

<template>
  <div class="min-h-full bg-slate-950 text-white">
    <div class="mx-auto flex w-full max-w-6xl flex-col gap-8 px-4 py-8 sm:px-6 lg:px-8">
      <section class="space-y-3">
        <p class="text-xs font-semibold uppercase tracking-[0.32em] text-emerald-200/80">
          Super Admin
        </p>
        <h1 class="text-3xl font-semibold sm:text-4xl">Billing</h1>
        <p class="max-w-2xl text-sm text-slate-300/70 sm:text-base">
          Track subscriptions, plan adoption, and revenue health across tenants.
        </p>
      </section>

      <p
        v-if="errorMessage"
        class="rounded-2xl border border-rose-500/30 bg-rose-500/10 px-4 py-3 text-sm text-rose-100"
      >
        {{ errorMessage }}
      </p>

      <section class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <article class="rounded-3xl border border-white/10 bg-white/5 p-6">
          <p class="text-xs uppercase tracking-[0.24em] text-slate-400">Plans</p>
          <p class="mt-3 text-3xl font-semibold">{{ plans.length }}</p>
        </article>
        <article class="rounded-3xl border border-white/10 bg-white/5 p-6">
          <p class="text-xs uppercase tracking-[0.24em] text-slate-400">Active subscriptions</p>
          <p class="mt-3 text-3xl font-semibold">{{ totalSubscribers }}</p>
        </article>
        <article class="rounded-3xl border border-white/10 bg-white/5 p-6">
          <p class="text-xs uppercase tracking-[0.24em] text-slate-400">Estimated MRR</p>
          <p class="mt-3 text-3xl font-semibold">${{ formatMoney(estimatedMrr) }}</p>
        </article>
        <article class="rounded-3xl border border-white/10 bg-white/5 p-6">
          <p class="text-xs uppercase tracking-[0.24em] text-slate-400">All subscriptions</p>
          <p class="mt-3 text-3xl font-semibold">{{ subscriptions.length }}</p>
        </article>
      </section>

      <section class="rounded-3xl border border-white/10 bg-white/5 p-6">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
          <div>
            <p class="text-xs uppercase tracking-[0.24em] text-slate-400">Subscriptions</p>
            <h2 class="mt-2 text-lg font-semibold">Active licenses</h2>
          </div>
          <button
            class="rounded-full border border-white/10 bg-white/5 px-3 py-1 text-xs transition hover:bg-white/10"
            type="button"
            @click="loadBilling"
          >
            Refresh
          </button>
        </div>
        <div class="mt-5 overflow-x-auto">
          <table class="min-w-[760px] text-left text-xs text-slate-200">
            <thead class="text-[11px] uppercase text-slate-400">
              <tr>
                <th class="px-3 py-2">Company</th>
                <th class="px-3 py-2">Plan</th>
                <th class="px-3 py-2">Price</th>
                <th class="px-3 py-2">Status</th>
                <th class="px-3 py-2">Starts</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="subscription in subscriptions" :key="subscription.id" class="border-t border-white/5">
                <td class="px-3 py-2">{{ subscription.company?.name || subscription.company_id }}</td>
                <td class="px-3 py-2">{{ subscription.plan?.name || planLookup[subscription.plan_id]?.name || '-' }}</td>
                <td class="px-3 py-2">
                  {{
                    subscription.plan?.price
                      ? `$${formatMoney(subscription.plan.price)}`
                      : planLookup[subscription.plan_id]?.price
                        ? `$${formatMoney(planLookup[subscription.plan_id]?.price)}`
                        : '-'
                  }}
                </td>
                <td class="px-3 py-2">{{ subscription.status }}</td>
                <td class="px-3 py-2">{{ formatDate(subscription.starts_at) }}</td>
              </tr>
              <tr v-if="!isLoading && !subscriptions.length">
                <td class="px-3 py-4 text-sm text-slate-400" colspan="5">
                  No subscriptions found.
                </td>
              </tr>
              <tr v-if="isLoading">
                <td class="px-3 py-4 text-sm text-slate-400" colspan="5">
                  Loading subscriptions...
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </section>
    </div>
  </div>
</template>



<script setup>
import { computed, onMounted, ref } from 'vue';
import apiClient from '../../api/apiClient';

defineOptions({ name: 'Access DirectoryPage' });

const users = ref([]);
const roles = ref([]);
const isLoading = ref(true);
const errorMessage = ref('');
const successMessage = ref('');

const isModalOpen = ref(false);
const isSubmitting = ref(false);
const searchQuery = ref('');
const pageSize = ref(8);
const currentPage = ref(1);
const pagination = ref({
  current_page: 1,
  last_page: 1,
  per_page: pageSize.value,
  total: 0,
});

const modalMode = ref('create');
const activeUser = ref(null);

const form = ref({
  name: '',
  email: '',
  password: '',
  role_id: '',
});

const adminGuard = (user) =>
  user?.id === 1 ||
  user?.role_id === 1 ||
  user?.name === 'Admin User' ||
  user?.email === 'admin@example.com';

const totalAdmins = computed(() =>
  users.value.filter((user) => user?.role_id === 1 || user?.role?.id === 1).length,
);

const unwrapList = (response) => {
  if (Array.isArray(response?.data)) return response.data;
  const data = response?.data?.data;
  if (Array.isArray(data)) return data;
  if (Array.isArray(data?.data)) return data.data;
  return [];
};

const parsePaginated = (response) => {
  const payload = response?.data?.data ?? response?.data;
  if (payload && Array.isArray(payload.data)) {
    const metaSource = payload.meta ?? payload;
    return {
      items: payload.data,
      meta: {
        current_page: metaSource.current_page ?? 1,
        last_page: metaSource.last_page ?? 1,
        per_page: metaSource.per_page ?? pageSize.value,
        total: metaSource.total ?? payload.data.length,
      },
    };
  }
  if (Array.isArray(payload)) {
    return {
      items: payload,
      meta: {
        current_page: 1,
        last_page: 1,
        per_page: payload.length || pageSize.value,
        total: payload.length,
      },
    };
  }
  return {
    items: [],
    meta: {
      current_page: 1,
      last_page: 1,
      per_page: pageSize.value,
      total: 0,
    },
  };
};

const loadUsers = async () => {
  isLoading.value = true;
  errorMessage.value = '';

  try {
    const [usersResponse, rolesResponse] = await Promise.all([
      apiClient.get('/api/users', {
        params: { page: currentPage.value, per_page: pageSize.value },
      }),
      apiClient.get('/api/roles'),
    ]);
    const parsed = parsePaginated(usersResponse);
    users.value = parsed.items;
    pagination.value = parsed.meta;
    currentPage.value = parsed.meta.current_page;
    roles.value = unwrapList(rolesResponse);
  } catch (error) {
    errorMessage.value = error?.response?.data?.message || 'Unable to load users.';
  } finally {
    isLoading.value = false;
  }
};

const openCreate = () => {
  modalMode.value = 'create';
  activeUser.value = null;
  form.value = { name: '', email: '', password: '', role_id: '' };
  errorMessage.value = '';
  successMessage.value = '';
  isModalOpen.value = true;
};

const openEdit = (user) => {
  modalMode.value = 'edit';
  activeUser.value = user;
  form.value = {
    name: user?.name || '',
    email: user?.email || '',
    password: '',
    role_id: user?.role_id || user?.role?.id || '',
  };
  errorMessage.value = '';
  successMessage.value = '';
  isModalOpen.value = true;
};

const closeModal = () => {
  isModalOpen.value = false;
};

const submitForm = async () => {
  isSubmitting.value = true;
  errorMessage.value = '';
  successMessage.value = '';

  try {
    if (modalMode.value === 'create') {
      await apiClient.post('/api/users', {
        name: form.value.name,
        email: form.value.email,
        password: form.value.password,
        role_id: Number(form.value.role_id),
      });
      successMessage.value = 'User created successfully.';
    } else if (activeUser.value) {
      const payload = {
        name: form.value.name,
        email: form.value.email,
        role_id: Number(form.value.role_id),
      };
      if (form.value.password) {
        payload.password = form.value.password;
      }
      await apiClient.put(`/api/users/${activeUser.value.id}`, payload);
      successMessage.value = 'User updated successfully.';
    }

    await loadUsers();
    closeModal();
  } catch (error) {
    errorMessage.value = error?.response?.data?.message || 'Unable to save user.';
  } finally {
    isSubmitting.value = false;
  }
};

const deleteUser = async (user) => {
  if (adminGuard(user)) {
    errorMessage.value = 'Admin User cannot be deleted.';
    return;
  }

  const confirmed = window.confirm(`Delete ${user?.name || 'this user'}?`);
  if (!confirmed) return;

  try {
    await apiClient.delete(`/api/users/${user.id}`);
    await loadUsers();
  } catch (error) {
    errorMessage.value = error?.response?.data?.message || 'Unable to delete user.';
  }
};

const filteredUsers = computed(() => {
  const query = searchQuery.value.trim().toLowerCase();
  if (!query) return users.value;
  return users.value.filter((user) => {
    const name = user?.name?.toLowerCase() || '';
    const email = user?.email?.toLowerCase() || '';
    const role = user?.role?.title?.toLowerCase() || user?.role?.name?.toLowerCase() || '';
    return name.includes(query) || email.includes(query) || role.includes(query);
  });
});

const totalPages = computed(() => pagination.value.last_page || 1);

const goToPage = (page) => {
  const nextPage = Math.min(Math.max(page, 1), totalPages.value);
  if (nextPage === currentPage.value) return;
  currentPage.value = nextPage;
  loadUsers();
};

onMounted(loadUsers);
</script>

<template>
  <div class="min-h-full bg-slate-950 text-white">
    <div class="mx-auto flex w-full max-w-7xl flex-col gap-8 px-4 sm:px-6 lg:px-8 py-6 sm:py-8 lg:py-10">
      <div class="flex flex-wrap items-center justify-between gap-4">
        <div>
          <p class="text-xs font-semibold uppercase tracking-[0.32em] text-emerald-200/80">Users</p>
          <h1 class="text-3xl font-semibold">Access Directory</h1>
          <p class="mt-2 text-sm text-slate-300/70">
            Create, assign, and protect access for every team member in your enterprise.
          </p>
        </div>
        <div class="flex flex-wrap items-center gap-3">
          <input
            v-model="searchQuery"
            type="search"
            placeholder="Search users..."
            class="h-10 rounded-full border border-white/10 bg-white/5 px-4 text-xs text-slate-200 outline-none transition focus:border-emerald-300/70 focus:ring-2 focus:ring-emerald-300/40"
          />
          <span class="rounded-full border border-white/10 bg-white/5 px-3 py-1 text-xs text-slate-200/80">
            {{ totalAdmins }} admins
          </span>
          <button
            class="rounded-full border border-emerald-300/40 bg-emerald-300/10 px-4 py-2 text-xs font-semibold uppercase tracking-[0.24em] text-emerald-200 transition hover:bg-emerald-300/20"
            type="button"
            @click="openCreate"
          >
            Add member
          </button>
        </div>
      </div>

      <p
        v-if="errorMessage"
        class="rounded-2xl border border-rose-500/30 bg-rose-500/10 px-4 py-3 text-sm text-rose-100"
      >
        {{ errorMessage }}
      </p>

      <div class="overflow-hidden rounded-3xl border border-white/10 bg-white/5">
        <div class="max-h-[72vh] overflow-auto">
          <div class="overflow-x-auto">
            <table class="min-w-[700px] w-full text-left text-xs sm:text-sm">
            <thead class="sticky top-0 bg-slate-950/90 text-xs uppercase tracking-[0.24em] text-slate-400">
              <tr>
                <th class="px-6 py-4 font-medium">Name</th>
                <th class="px-6 py-4 font-medium hidden md:table-cell">Email</th>
                <th class="px-6 py-4 font-medium hidden lg:table-cell">Role</th>
                <th class="px-6 py-4 font-medium hidden md:table-cell">Status</th>
                <th class="px-6 py-4 font-medium text-right">Actions</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-white/5">
              <tr v-if="isLoading">
                <td class="px-6 py-6 text-center text-slate-400" colspan="5">
                  Loading users...
                </td>
              </tr>
              <tr
                v-for="user in filteredUsers"
                :key="user.id"
                class="hover:bg-white/5"
              >
                <td class="px-6 py-4 font-medium text-slate-100">
                  {{ user.name || 'Unknown' }}
                </td>
                <td class="px-6 py-4 text-slate-300/80 hidden md:table-cell">{{ user.email }}</td>
                <td class="px-6 py-4 text-slate-300/80 hidden lg:table-cell">
                  {{ user.role?.title || user.role?.name || 'Unassigned' }}
                </td>
                <td class="px-6 py-4 hidden md:table-cell">
                  <span
                    class="rounded-full px-3 py-1 text-xs"
                    :class="user.email_verified_at ? 'bg-emerald-400/10 text-emerald-200' : 'bg-amber-400/10 text-amber-200'"
                  >
                    {{ user.email_verified_at ? 'Verified' : 'Pending' }}
                  </span>
                </td>
                <td class="px-6 py-4">
                  <div class="flex items-center justify-end gap-2">
                    <button
                      class="rounded-full border border-white/10 px-3 py-1 text-xs text-slate-200 transition hover:bg-white/10"
                      type="button"
                      @click="openEdit(user)"
                    >
                      Edit
                    </button>
                    <button
                      class="rounded-full border border-rose-500/30 bg-rose-500/10 px-3 py-1 text-xs text-rose-200 transition hover:bg-rose-500/20"
                      type="button"
                      :disabled="adminGuard(user)"
                      @click="deleteUser(user)"
                    >
                      Delete
                    </button>
                  </div>
                </td>
              </tr>
              <tr v-if="!isLoading && !filteredUsers.length">
                <td class="px-6 py-6 text-center text-slate-400" colspan="5">
                  No team members found yet.
                </td>
              </tr>
            </tbody>
          </table>
          </div>
        </div>
      </div>

      <div class="flex flex-wrap items-center justify-between gap-3 text-xs text-slate-300/70">
        <span>
          Showing
          {{ filteredUsers.length }}
          of
          {{ pagination.total }}
          users
        </span>
        <div class="flex items-center gap-2">
          <button
            class="rounded-full border border-white/10 px-3 py-1 transition hover:bg-white/10 disabled:opacity-50"
            type="button"
            :disabled="currentPage === 1"
            @click="goToPage(currentPage - 1)"
          >
            Prev
          </button>
          <span>Page {{ currentPage }} of {{ totalPages }}</span>
          <button
            class="rounded-full border border-white/10 px-3 py-1 transition hover:bg-white/10 disabled:opacity-50"
            type="button"
            :disabled="currentPage === totalPages"
            @click="goToPage(currentPage + 1)"
          >
            Next
          </button>
        </div>
      </div>
    </div>

    <transition name="fade">
      <div
        v-if="isModalOpen"
        class="vegro-modal-overlay"
        @click="closeModal"
      ></div>
    </transition>

    <transition name="slide-up">
      <div v-if="isModalOpen" class="vegro-modal-wrap">
        <div class="vegro-modal">
          <div class="vegro-modal-header">
            <div>
              <p class="vegro-modal-title">
                {{ modalMode === 'create' ? 'Create' : 'Edit' }} User
              </p>
              <h2 class="vegro-modal-subtitle">
                {{ modalMode === 'create' ? 'New Member' : 'Update Member' }}
              </h2>
            </div>
            <button class="vegro-modal-close" type="button" @click="closeModal">Close</button>
          </div>

          <form class="vegro-modal-body flex flex-col gap-4" @submit.prevent="submitForm">
            <label class="flex flex-col gap-2 text-sm text-slate-200/80">
              <span>Name</span>
              <input
                v-model="form.name"
                type="text"
                required
                class="h-11 rounded-xl border border-white/10 bg-slate-950/40 px-4 text-sm text-white outline-none transition focus:border-emerald-300/70 focus:ring-2 focus:ring-emerald-300/40"
              />
            </label>
            <label class="flex flex-col gap-2 text-sm text-slate-200/80">
              <span>Email</span>
              <input
                v-model="form.email"
                type="email"
                required
                class="h-11 rounded-xl border border-white/10 bg-slate-950/40 px-4 text-sm text-white outline-none transition focus:border-emerald-300/70 focus:ring-2 focus:ring-emerald-300/40"
              />
            </label>
            <label class="flex flex-col gap-2 text-sm text-slate-200/80">
              <span>Password</span>
              <input
                v-model="form.password"
                type="password"
                :required="modalMode === 'create'"
                class="h-11 rounded-xl border border-white/10 bg-slate-950/40 px-4 text-sm text-white outline-none transition focus:border-emerald-300/70 focus:ring-2 focus:ring-emerald-300/40"
                placeholder="Leave blank to keep current"
              />
            </label>
            <label class="flex flex-col gap-2 text-sm text-slate-200/80">
              <span>Role</span>
              <select
                v-model="form.role_id"
                required
                class="h-11 rounded-xl border border-white/10 bg-slate-950/40 px-4 text-sm text-white outline-none transition focus:border-emerald-300/70 focus:ring-2 focus:ring-emerald-300/40"
              >
                <option value="" disabled>Select role</option>
                <option v-for="role in roles" :key="role.id" :value="role.id">
                  {{ role.title || role.name }}
                </option>
              </select>
            </label>

            <p
              v-if="successMessage"
              class="rounded-xl border border-emerald-300/30 bg-emerald-300/10 px-4 py-2 text-xs text-emerald-200"
            >
              {{ successMessage }}
            </p>

            <button
              class="mt-2 inline-flex h-11 items-center justify-center rounded-xl bg-emerald-400 text-sm font-semibold text-slate-950 transition hover:bg-emerald-300 disabled:cursor-not-allowed disabled:opacity-70"
              type="submit"
              :disabled="isSubmitting"
            >
              {{ isSubmitting ? 'Saving...' : 'Save member' }}
            </button>
          </form>
        </div>
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

.slide-up-enter-active,
.slide-up-leave-active {
  transition: transform 0.25s ease, opacity 0.25s ease;
}

.slide-up-enter-from,
.slide-up-leave-to {
  transform: translateY(20px);
  opacity: 0;
}
</style>


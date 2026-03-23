<script setup>
import { computed, onMounted, ref } from 'vue';
import apiClient from '../../api/apiClient';
import permissionService from '../../services/permissionService';

defineOptions({ name: 'RolesPage' });

const roles = ref([]);
const isLoading = ref(true);
const errorMessage = ref('');
const isModalOpen = ref(false);
const modalMode = ref('create');
const activeRole = ref(null);
const isSubmitting = ref(false);
const searchQuery = ref('');
const permissions = ref([]);
const selectedPermissions = ref([]);
const isPermissionsLoading = ref(false);

const form = ref({
  title: '',
  description: '',
});

const unwrapList = (response) => {
  if (Array.isArray(response?.data)) return response.data;
  const data = response?.data?.data;
  if (Array.isArray(data)) return data;
  if (Array.isArray(data?.data)) return data.data;
  return [];
};

const loadRoles = async () => {
  isLoading.value = true;
  errorMessage.value = '';

  try {
    const response = await apiClient.get('/api/roles');
    roles.value = unwrapList(response);
  } catch (error) {
    errorMessage.value = error?.response?.data?.message || 'Unable to load roles.';
  } finally {
    isLoading.value = false;
  }
};

const loadPermissions = async () => {
  isPermissionsLoading.value = true;
  errorMessage.value = '';

  try {
    const response = await permissionService.getPermissions();
    permissions.value = response?.data?.data || response?.data || [];
  } catch (error) {
    errorMessage.value = error?.response?.data?.message || 'Unable to load permissions.';
  } finally {
    isPermissionsLoading.value = false;
  }
};

const openCreate = () => {
  modalMode.value = 'create';
  activeRole.value = null;
  form.value = { title: '', description: '' };
  selectedPermissions.value = [];
  isModalOpen.value = true;
};

const openEdit = (role) => {
  modalMode.value = 'edit';
  activeRole.value = role;
  form.value = {
    title: role?.title || '',
    description: role?.description || '',
  };
  selectedPermissions.value = (role?.permissions || []).map((permission) => permission.id);
  isModalOpen.value = true;
};

const closeModal = () => {
  isModalOpen.value = false;
};

const submitForm = async () => {
  isSubmitting.value = true;
  errorMessage.value = '';

  try {
    const payload = {
      name: form.value.title,
      description: form.value.description,
    };

    let roleId = activeRole.value?.id;
    if (modalMode.value === 'create') {
      const response = await apiClient.post('/api/roles', payload);
      roleId = response?.data?.id || response?.data?.data?.id || roleId;
    } else if (activeRole.value?.id) {
      const response = await apiClient.put(`/api/roles/${activeRole.value.id}`, payload);
      roleId = response?.data?.id || response?.data?.data?.id || roleId;
    }

    if (roleId) {
      await permissionService.updateRolePermissions(roleId, selectedPermissions.value);
    }

    await loadRoles();
    closeModal();
  } catch (error) {
    errorMessage.value = error?.response?.data?.message || 'Unable to save role.';
  } finally {
    isSubmitting.value = false;
  }
};

const deleteRole = async (role) => {
  const confirmed = window.confirm(`Delete ${role?.title || 'this role'}?`);
  if (!confirmed) return;

  try {
    await apiClient.delete(`/api/roles/${role.id}`);
    await loadRoles();
  } catch (error) {
    errorMessage.value = error?.response?.data?.message || 'Unable to delete role.';
  }
};

const filteredRoles = computed(() => {
  const query = searchQuery.value.trim().toLowerCase();
  if (!query) return roles.value;
  return roles.value.filter((role) => {
    const title = role?.title?.toLowerCase() || '';
    const description = role?.description?.toLowerCase() || '';
    const permissions = (role?.permissions || [])
      .map((permission) => permission?.label || permission?.key || '')
      .join(' ')
      .toLowerCase();
    return title.includes(query) || description.includes(query) || permissions.includes(query);
  });
});

const groupedPermissions = computed(() => {
  const groups = {};
  for (const permission of permissions.value) {
    const group = permission.group || 'General';
    if (!groups[group]) groups[group] = [];
    groups[group].push(permission);
  }
  return groups;
});

onMounted(async () => {
  await Promise.all([loadRoles(), loadPermissions()]);
});
</script>

<template>
  <div class="min-h-full bg-slate-950 text-white">
    <div class="mx-auto flex w-full max-w-7xl flex-col gap-8 px-4 sm:px-6 lg:px-8 py-6 sm:py-8 lg:py-10">
      <div class="flex flex-wrap items-center justify-between gap-4">
        <div>
          <p class="text-xs font-semibold uppercase tracking-[0.32em] text-emerald-200/80">Roles</p>
          <h1 class="text-3xl font-semibold">Role Governance</h1>
          <p class="mt-2 text-sm text-slate-300/70">
            Define responsibilities and access levels across your enterprise teams.
          </p>
        </div>
        <div class="flex flex-wrap items-center gap-3">
          <RouterLink
            to="/dashboard/role-matrix"
            class="rounded-full border border-white/10 px-4 py-2 text-xs font-semibold uppercase tracking-[0.24em] text-slate-200 transition hover:bg-white/10"
          >
            Access Matrix
          </RouterLink>
          <input
            v-model="searchQuery"
            type="search"
            placeholder="Search roles..."
            class="h-10 rounded-full border border-white/10 bg-white/5 px-4 text-xs text-slate-200 outline-none transition focus:border-emerald-300/70 focus:ring-2 focus:ring-emerald-300/40"
          />
          <button
            class="rounded-full border border-emerald-300/40 bg-emerald-300/10 px-4 py-2 text-xs font-semibold uppercase tracking-[0.24em] text-emerald-200 transition hover:bg-emerald-300/20"
            type="button"
            @click="openCreate"
          >
            Add role
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
                  <th class="px-6 py-4 font-medium">Role</th>
                  <th class="px-6 py-4 font-medium hidden lg:table-cell">Description</th>
                  <th class="px-6 py-4 font-medium">Has Access To</th>
                  <th class="px-6 py-4 font-medium text-right">Actions</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-white/5">
                <tr v-if="isLoading">
                  <td class="px-6 py-6 text-center text-slate-400" colspan="4">
                    Loading roles...
                  </td>
                </tr>
                <tr
                  v-for="role in filteredRoles"
                  :key="role.id"
                  class="hover:bg-white/5"
                >
                  <td class="px-6 py-4 font-medium text-slate-100">{{ role.title }}</td>
                  <td class="px-6 py-4 text-slate-300/80 hidden lg:table-cell">
                    {{ role.description || '?' }}
                  </td>
                  <td class="px-6 py-4">
                    <div class="flex flex-wrap gap-2">
                      <span
                        v-for="permission in role.permissions || []"
                        :key="permission.id"
                        class="rounded-full border border-white/10 bg-white/5 px-3 py-1 text-[11px] text-slate-200"
                      >
                        {{ permission.label || permission.key }}
                      </span>
                      <span
                        v-if="!role.permissions || !role.permissions.length"
                        class="text-xs text-slate-400"
                      >
                        No permissions assigned
                      </span>
                    </div>
                  </td>
                  <td class="px-6 py-4">
                    <div class="flex items-center justify-end gap-2">
                      <button
                        class="rounded-full border border-white/10 px-3 py-1 text-xs text-slate-200 transition hover:bg-white/10"
                        type="button"
                        @click="openEdit(role)"
                      >
                        Edit
                      </button>
                      <button
                        class="rounded-full border border-rose-500/30 bg-rose-500/10 px-3 py-1 text-xs text-rose-200 transition hover:bg-rose-500/20"
                        type="button"
                        @click="deleteRole(role)"
                      >
                        Delete
                      </button>
                    </div>
                  </td>
                </tr>
                <tr v-if="!isLoading && !filteredRoles.length">
                  <td class="px-6 py-6 text-center text-slate-400" colspan="4">
                    No roles found yet.
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
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
                {{ modalMode === 'create' ? 'Create' : 'Edit' }} Role
              </p>
              <h2 class="vegro-modal-subtitle">
                {{ modalMode === 'create' ? 'New Role' : 'Update Role' }}
              </h2>
            </div>
            <button class="vegro-modal-close" type="button" @click="closeModal">Close</button>
          </div>

          <form class="vegro-modal-body flex flex-col gap-4" @submit.prevent="submitForm">
            <label class="flex flex-col gap-2 text-sm text-slate-200/80">
              <span>Role title</span>
              <input
                v-model="form.title"
                type="text"
                required
                class="h-11 rounded-xl border border-white/10 bg-slate-950/40 px-4 text-sm text-white outline-none transition focus:border-emerald-300/70 focus:ring-2 focus:ring-emerald-300/40"
              />
            </label>
            <label class="flex flex-col gap-2 text-sm text-slate-200/80">
              <span>Description</span>
              <textarea
                v-model="form.description"
                rows="3"
                class="rounded-xl border border-white/10 bg-slate-950/40 px-4 py-2 text-sm text-white outline-none transition focus:border-emerald-300/70 focus:ring-2 focus:ring-emerald-300/40"
              ></textarea>
            </label>

            <div class="flex flex-col gap-3 text-sm text-slate-200/80">
              <span>Has access to</span>
              <div class="max-h-72 overflow-auto rounded-2xl border border-white/10 bg-white/5 p-4">
                <div v-if="isPermissionsLoading" class="text-xs text-slate-400">
                  Loading permissions...
                </div>
                <div v-else class="flex flex-col gap-4">
                  <div v-for="(groupPermissions, groupName) in groupedPermissions" :key="groupName">
                    <p class="text-[11px] uppercase tracking-[0.2em] text-slate-400">{{ groupName }}</p>
                    <div class="mt-2 flex flex-wrap gap-2">
                      <label
                        v-for="permission in groupPermissions"
                        :key="permission.id"
                        class="flex items-center gap-2 rounded-full border border-white/10 bg-white/5 px-3 py-2 text-xs text-slate-200"
                      >
                        <input
                          v-model="selectedPermissions"
                          type="checkbox"
                          class="h-3 w-3 accent-emerald-400"
                          :value="permission.id"
                        />
                        <span>{{ permission.label || permission.key }}</span>
                      </label>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <button
              class="mt-2 inline-flex h-11 items-center justify-center rounded-xl bg-emerald-400 text-sm font-semibold text-slate-950 transition hover:bg-emerald-300 disabled:cursor-not-allowed disabled:opacity-70"
              type="submit"
              :disabled="isSubmitting"
            >
              {{ isSubmitting ? 'Saving...' : 'Save role' }}
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

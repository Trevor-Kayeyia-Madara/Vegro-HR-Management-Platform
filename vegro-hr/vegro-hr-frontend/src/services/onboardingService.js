import apiClient from '../api/apiClient';

const unwrapPayload = (response) => response?.data?.data ?? response?.data ?? null;

const parsePaginated = (response) => {
  const payload = unwrapPayload(response);
  if (payload && Array.isArray(payload.data)) {
    const metaSource = payload.meta ?? payload;
    return {
      items: payload.data,
      meta: {
        current_page: metaSource.current_page ?? 1,
        last_page: metaSource.last_page ?? 1,
        per_page: metaSource.per_page ?? payload.data.length,
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
        per_page: payload.length,
        total: payload.length,
      },
    };
  }
  return {
    items: [],
    meta: { current_page: 1, last_page: 1, per_page: 10, total: 0 },
  };
};

const getTemplates = (params = {}) => apiClient.get('/api/onboarding/templates', { params });
const toFormData = (payload = {}) => {
  const formData = new FormData();
  Object.entries(payload).forEach(([key, value]) => {
    if (value === undefined || value === null || value === '') return;
    if (typeof value === 'boolean') {
      formData.append(key, value ? '1' : '0');
      return;
    }
    formData.append(key, value);
  });
  return formData;
};

const createTemplate = (payload) =>
  apiClient.post('/api/onboarding/templates', toFormData(payload), {
    headers: { 'Content-Type': 'multipart/form-data' },
  });
const updateTemplate = (id, payload) =>
  apiClient.put(`/api/onboarding/templates/${id}`, toFormData(payload), {
    headers: { 'Content-Type': 'multipart/form-data' },
  });
const deleteTemplate = (id) => apiClient.delete(`/api/onboarding/templates/${id}`);
const downloadTemplate = (id) => apiClient.get(`/api/onboarding/templates/${id}/download`, { responseType: 'blob' });
const getAssignments = (params = {}) => apiClient.get('/api/onboarding/assignments', { params });
const getMyAssignments = (params = {}) => apiClient.get('/api/onboarding/assignments/mine', { params });
const assignDocument = (payload) => apiClient.post('/api/onboarding/assignments', payload);
const signAssignment = (id, payload) => apiClient.post(`/api/onboarding/assignments/${id}/sign`, payload);

export default {
  unwrapPayload,
  parsePaginated,
  getTemplates,
  createTemplate,
  updateTemplate,
  deleteTemplate,
  downloadTemplate,
  getAssignments,
  getMyAssignments,
  assignDocument,
  signAssignment,
};

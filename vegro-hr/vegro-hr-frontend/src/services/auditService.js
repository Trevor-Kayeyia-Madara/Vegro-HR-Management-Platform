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
  return {
    items: Array.isArray(payload) ? payload : [],
    meta: { current_page: 1, last_page: 1, per_page: 50, total: Array.isArray(payload) ? payload.length : 0 },
  };
};

const getModelChanges = (params = {}) => apiClient.get('/api/audits/model-changes', { params });

export default {
  unwrapPayload,
  parsePaginated,
  getModelChanges,
};

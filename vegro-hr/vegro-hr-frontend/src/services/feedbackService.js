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

const getMine = (params = {}) => apiClient.get('/api/feedback/mine', { params });
const getAll = (params = {}) => apiClient.get('/api/feedback', { params });
const create = (payload) => apiClient.post('/api/feedback', payload);
const update = (id, payload) => apiClient.put(`/api/feedback/${id}`, payload);

export default {
  unwrapPayload,
  parsePaginated,
  getMine,
  getAll,
  create,
  update,
};

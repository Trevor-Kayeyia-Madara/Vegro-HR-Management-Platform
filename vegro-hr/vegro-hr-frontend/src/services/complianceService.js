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
      meta: { current_page: 1, last_page: 1, per_page: payload.length, total: payload.length },
    };
  }

  return {
    items: [],
    meta: { current_page: 1, last_page: 1, per_page: 20, total: 0 },
  };
};

const getAlerts = (params = {}) => apiClient.get('/api/compliance/alerts', { params });
const runScan = () => apiClient.post('/api/compliance/scan');
const acknowledge = (id) => apiClient.put(`/api/compliance/alerts/${id}/acknowledge`);

export default {
  unwrapPayload,
  parsePaginated,
  getAlerts,
  runScan,
  acknowledge,
};

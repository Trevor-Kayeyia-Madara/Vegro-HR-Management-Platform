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

const getJobs = (params = {}) => apiClient.get('/api/ats/jobs', { params });
const createJob = (payload) => apiClient.post('/api/ats/jobs', payload);
const updateJob = (id, payload) => apiClient.put(`/api/ats/jobs/${id}`, payload);
const deleteJob = (id) => apiClient.delete(`/api/ats/jobs/${id}`);

const getCandidates = (params = {}) => apiClient.get('/api/ats/candidates', { params });
const createCandidate = (payload) => apiClient.post('/api/ats/candidates', payload);
const updateCandidate = (id, payload) => apiClient.put(`/api/ats/candidates/${id}`, payload);
const deleteCandidate = (id) => apiClient.delete(`/api/ats/candidates/${id}`);

const getApplications = (params = {}) => apiClient.get('/api/ats/applications', { params });
const createApplication = (payload) => apiClient.post('/api/ats/applications', payload);
const updateApplication = (id, payload) => apiClient.put(`/api/ats/applications/${id}`, payload);
const deleteApplication = (id) => apiClient.delete(`/api/ats/applications/${id}`);
const addApplicationNote = (applicationId, payload) =>
  apiClient.post(`/api/ats/applications/${applicationId}/notes`, payload);

export default {
  unwrapPayload,
  parsePaginated,
  getJobs,
  createJob,
  updateJob,
  deleteJob,
  getCandidates,
  createCandidate,
  updateCandidate,
  deleteCandidate,
  getApplications,
  createApplication,
  updateApplication,
  deleteApplication,
  addApplicationNote,
};


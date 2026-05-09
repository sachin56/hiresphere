import { fetchAuthSession } from 'aws-amplify/auth';

const BASE_URL = import.meta.env.VITE_API_URL || '/api';

async function getAuthHeader() {
  try {
    const session = await fetchAuthSession();
    const token = session.tokens?.idToken?.toString();
    return token ? { Authorization: `Bearer ${token}` } : {};
  } catch {
    return {};
  }
}

async function request(method, path, body = null, isFormData = false) {
  const authHeader = await getAuthHeader();

  const headers = {
    ...authHeader,
    'Accept': 'application/json',
    ...(isFormData ? {} : { 'Content-Type': 'application/json' }),
  };

  const options = { method, headers };
  if (body) {
    options.body = isFormData ? body : JSON.stringify(body);
  }

  const res = await fetch(`${BASE_URL}${path}`, options);

  if (!res.ok) {
    const err = await res.json().catch(() => ({ message: res.statusText }));
    throw Object.assign(new Error(err.message || 'Request failed'), { status: res.status, data: err });
  }

  if (res.status === 204) return null;
  return res.json();
}

export const api = {
  get:    (path)              => request('GET',    path),
  post:   (path, body)        => request('POST',   path, body),
  put:    (path, body)        => request('PUT',    path, body),
  delete: (path)              => request('DELETE', path),
  upload: (path, formData)    => request('POST',   path, formData, true),
};

// ── Domain helpers ────────────────────────────────────────────────────────────

export const authApi = {
  me: ()           => api.get('/auth/me'),
  logout: ()       => api.post('/auth/logout'),
};

export const interviewerApi = {
  list:         (params) => api.get('/interviewers?' + new URLSearchParams(params).toString()),
  get:          (id)     => api.get(`/interviewers/${id}`),
  reviews:      (id)     => api.get(`/interviewers/${id}/reviews`),
  availability: (id)     => api.get(`/interviewers/${id}/availability`),
  update:       (id, data) => api.put(`/interviewers/${id}`, data),
};

export const messageApi = {
  conversations: ()              => api.get('/conversations'),
  messages:      (userId, params) => api.get(`/conversations/${userId}/messages` + (params ? '?' + new URLSearchParams(params) : '')),
  send:          (userId, content, type = 'text') => api.post(`/conversations/${userId}/messages`, { content, type }),
};

export const availabilityApi = {
  mySlots:    ()       => api.get('/interviewer/availability'),
  addSlots:   (slots)  => api.post('/interviewer/availability', { slots }),
  deleteSlot: (slotId) => api.delete(`/interviewer/availability/${slotId}`),
};

export const bookingApi = {
  list:   (params) => api.get('/bookings?' + new URLSearchParams(params || {}).toString()),
  get:    (id)     => api.get(`/bookings/${id}`),
  create: (data)   => api.post('/bookings', data),
  accept: (id)     => api.put(`/bookings/${id}/accept`),
  reject: (id)     => api.put(`/bookings/${id}/reject`),
  cancel: (id)     => api.put(`/bookings/${id}/cancel`),
  join:     (id)   => api.post(`/bookings/${id}/join`),
  complete: (id)   => api.put(`/bookings/${id}/complete`),
};

export const submissionApi = {
  list:     ()                   => api.get('/submissions'),
  get:      (id)                 => api.get(`/submissions/${id}`),
  create:   (formData)           => api.upload('/submissions', formData),
  annotate: (id, annotation)     => api.put(`/submissions/${id}/annotate`, { annotation }),
};

export const evaluationApi = {
  list:   ()       => api.get('/evaluations'),
  get:    (id)     => api.get(`/evaluations/${id}`),
  create: (data)   => api.post('/evaluations', data),
  review: (bookingId, data) => api.post(`/bookings/${bookingId}/review`, data),
};

export const paymentApi = {
  createCheckout: (bookingId)            => api.post(`/payments/checkout/${bookingId}`),
  verify:         (bookingId, sessionId) => api.get(`/payments/verify/${bookingId}?session_id=${sessionId}`),
};

export const adminApi = {
  pendingInterviewers: ()   => api.get('/admin/interviewers/pending'),
  approve:             (id) => api.put(`/admin/interviewers/${id}/approve`),
  reject:              (id) => api.put(`/admin/interviewers/${id}/reject`),
  users:               ()   => api.get('/admin/users'),
};

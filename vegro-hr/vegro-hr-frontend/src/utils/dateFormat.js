const isValidDate = (value) => {
  if (!value) return false;
  const date = new Date(value);
  return !Number.isNaN(date.getTime());
};

const pad = (value) => String(value).padStart(2, '0');

export const formatDate = (value, fallback = '-') => {
  if (!isValidDate(value)) return fallback;
  const date = new Date(value);
  const day = pad(date.getDate());
  const month = pad(date.getMonth() + 1);
  const year = date.getFullYear();
  return `${day}-${month}-${year}`;
};

export const formatDateTime = (value, fallback = '-') => {
  if (!isValidDate(value)) return fallback;
  const date = new Date(value);
  const day = pad(date.getDate());
  const month = pad(date.getMonth() + 1);
  const year = date.getFullYear();
  const hours = pad(date.getHours());
  const minutes = pad(date.getMinutes());
  return `${day}-${month}-${year} ${hours}:${minutes}`;
};

export const formatDateRange = (startDate, endDate, fallback = '-') => {
  const start = formatDate(startDate, '');
  const end = formatDate(endDate, '');
  if (!start && !end) return fallback;
  if (!start) return end;
  if (!end) return start;
  return `${start} â†’ ${end}`;
};

export const isDateLikeField = (key = '') => /(date|_at)$/i.test(String(key));

export const formatByField = (key, value) => {
  if (!isDateLikeField(key)) return value;
  if (value === null || value === undefined || value === '') return '-';
  return String(key).toLowerCase().endsWith('_at')
    ? formatDateTime(value)
    : formatDate(value);
};


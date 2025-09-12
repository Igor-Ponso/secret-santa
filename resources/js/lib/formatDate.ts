import { useI18n } from 'vue-i18n';

export function useDateFormat() {
  const { locale } = useI18n();
  function formatDate(date?: string | number | Date | null, opts: Intl.DateTimeFormatOptions = {}) {
    if (!date) return '';
    try {
      return new Intl.DateTimeFormat(locale.value.replace('_','-'), { dateStyle: 'medium', ...opts }).format(new Date(date));
    } catch {
      return new Date(date).toLocaleDateString();
    }
  }
  function formatDateTime(date?: string | number | Date | null, opts: Intl.DateTimeFormatOptions = {}) {
    if (!date) return '';
    try {
      return new Intl.DateTimeFormat(locale.value.replace('_','-'), { dateStyle: 'medium', timeStyle: 'short', ...opts }).format(new Date(date));
    } catch {
      return new Date(date).toLocaleString();
    }
  }
  return { formatDate, formatDateTime };
}

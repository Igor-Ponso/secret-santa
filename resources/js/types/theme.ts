import type { Appearance } from '@/composables/useAppearance';
import { Sun, Moon, MonitorSmartphone } from 'lucide-vue-next';

// Single source of truth for the theme mode definition
export interface ThemeModeDefinition {
  value: Appearance;
  label: string;
  // Icon is a functional component from lucide-vue-next
  icon: any; // If stricter typing needed: FunctionalComponent<LucideProps>
}

export const THEME_MODES: Readonly<ThemeModeDefinition[]> = Object.freeze([
  { value: 'light', label: 'Light', icon: Sun },
  { value: 'dark', label: 'Dark', icon: Moon },
  { value: 'system', label: 'System', icon: MonitorSmartphone },
]);

export function getThemeMode(value: Appearance): ThemeModeDefinition | undefined {
  return THEME_MODES.find(m => m.value === value);
}

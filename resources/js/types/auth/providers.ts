/**
 * Social login provider definitions.
 * Exposed no logic related to UI; pure data + helpers.
 * To conditionally expose providers on the client, prefer creating
 * public env vars prefixed with VITE_ (e.g. VITE_GOOGLE_CLIENT_ID) so they are available in import.meta.env.
 */

export interface SocialProviderDefinition {
  id: 'google' | 'github' | 'linkedin' | 'microsoft';
  label: string;
  /** Absolute (already resolved) asset URL for the icon */
  icon: string;
  /** Inline SVG markup (for when <img> loading fails or to inline directly) */
  svg: string;
  /** Optional public env variable (without VITE_ prefix) base name(s) required to consider it configured */
  publicEnvKeys?: string[]; // e.g. ['GOOGLE_CLIENT_ID'] means we look for VITE_GOOGLE_CLIENT_ID
}

// Static imports let Vite provide the final URL (more robust than new URL for some setups)
import googleIcon from '@assets/illustrations/google-icon.svg?url';
import githubIcon from '@assets/illustrations/github-icon.svg?url';
import linkedinIcon from '@assets/illustrations/linkedin-icon.svg?url';
import microsoftIcon from '@assets/illustrations/microsoft-icon.svg?url';

// Raw (inline) variants
import googleIconRaw from '@assets/illustrations/google-icon.svg?raw';
import githubIconRaw from '@assets/illustrations/github-icon.svg?raw';
import linkedinIconRaw from '@assets/illustrations/linkedin-icon.svg?raw';
import microsoftIconRaw from '@assets/illustrations/microsoft-icon.svg?raw';

const icons = {
  google: googleIcon,
  github: githubIcon,
  linkedin: linkedinIcon,
  microsoft: microsoftIcon,
};
const rawIcons = {
  google: googleIconRaw,
  github: githubIconRaw,
  linkedin: linkedinIconRaw,
  microsoft: microsoftIconRaw,
};

// Dev aid: warn if any icon failed to resolve to a string URL
if (import.meta.env.DEV) {
  for (const [k, v] of Object.entries(icons)) {
    if (typeof v !== 'string' || v.length === 0) {
      console.warn('[social][icon-missing]', k, v);
    }
  }
}

export const SOCIAL_PROVIDERS: Readonly<SocialProviderDefinition[]> = Object.freeze([
  { id: 'google', label: 'Google', icon: icons.google, svg: rawIcons.google, publicEnvKeys: ['GOOGLE_CLIENT_ID'] },
  { id: 'github', label: 'GitHub', icon: icons.github, svg: rawIcons.github, publicEnvKeys: ['GITHUB_CLIENT_ID'] },
  { id: 'linkedin', label: 'LinkedIn', icon: icons.linkedin, svg: rawIcons.linkedin, publicEnvKeys: ['LINKEDIN_CLIENT_ID'] },
  { id: 'microsoft', label: 'Microsoft', icon: icons.microsoft, svg: rawIcons.microsoft, publicEnvKeys: ['MICROSOFT_CLIENT_ID'] },
]);

/**
 * Determine if a provider should be shown on the client.
 * Rule: If a related VITE_ prefixed env var is defined (e.g. VITE_GOOGLE_CLIENT_ID), we require it.
 * If none are defined for that provider (common early dev scenario), we keep it visible (optimistic fallback).
 */
export function isProviderEnabled(def: SocialProviderDefinition): boolean {
  if (!def.publicEnvKeys || def.publicEnvKeys.length === 0) return true;
  // Check if at least one declared key has a corresponding *public* env var.
  const hasAnyPublic = def.publicEnvKeys.some(k => `VITE_${k}` in import.meta.env);
  if (!hasAnyPublic) {
    // No public vars present for this provider => do not block (fallback to visible)
    return true;
  }
  // If public vars exist, ensure they all have values
  return def.publicEnvKeys.every(k => (import.meta.env as Record<string, string | undefined>)[`VITE_${k}`]);
}

export function getActiveProviders(): SocialProviderDefinition[] {
  return SOCIAL_PROVIDERS.filter(isProviderEnabled);
}

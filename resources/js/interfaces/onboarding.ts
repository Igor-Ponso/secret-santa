// Shared interfaces for the Onboarding flow
// Keep this file lean; only types (no runtime logic)

export interface GroupRef {
    id: number;
    name: string;
}

export interface OnboardingDraftItem {
    item: string;
    note?: string;
    url?: string;
}

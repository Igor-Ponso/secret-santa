<script setup lang="ts">
import HeadingSmall from '@/components/HeadingSmall.vue';
import DeviceWithMapCard from '@/components/security/DeviceWithMapCard.vue';
import { Button } from '@/components/ui/button';
import { Separator } from '@/components/ui/separator';
import type { TrustedDevice } from '@/interfaces/security';
import AppLayout from '@/layouts/AppLayout.vue';
import SettingsLayout from '@/layouts/settings/Layout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, useForm } from '@inertiajs/vue3';
import { computed, onMounted, ref } from 'vue';
import { useI18n } from 'vue-i18n';

const props = defineProps<{
    two_factor_mode: string | null; // backend uses 'disabled' or 'email_on_new_device'
    devices: TrustedDevice[];
    current_device_id: number | null;
}>();

const passwordForm = useForm({ password: '' });
const revokeForm = useForm<{ id?: number }>({});
const logoutOthersForm = useForm({ password: '' });
const renameForm = useForm<{ name: string }>({ name: '' });

const enabling = ref(false);
const showPasswordDialog = ref(false);
const desired2faState = ref<'enable' | 'disable' | null>(null);
const editingDeviceId = ref<number | null>(null);
const revealedIps = ref<Record<number, boolean>>({});
const revealTimers: Record<number, any> = {};
// geo cache per IP
interface DeviceGeo {
    latitude?: number | null;
    longitude?: number | null;
}
const deviceGeo = ref<Record<string, DeviceGeo>>({});
const loadingGeo = ref(false);

function openToggle(state: 'enable' | 'disable') {
    desired2faState.value = state;
    passwordForm.reset('password');
    showPasswordDialog.value = true;
}

const { t } = useI18n();
const breadcrumbs: BreadcrumbItem[] = [{ title: t('security.breadcrumb', 'Segurança'), href: '/settings/security' }];

function submit2fa() {
    const active = props.two_factor_mode && props.two_factor_mode !== 'disabled';
    if (desired2faState.value === 'enable' && !active) {
        enabling.value = true;
        passwordForm.post(route('settings.security.2fa.enable'), {
            preserveScroll: true,
            onFinish: () => {
                enabling.value = false;
                showPasswordDialog.value = false;
            },
        });
    } else if (desired2faState.value === 'disable' && active) {
        passwordForm.delete(route('settings.security.2fa.disable'), {
            preserveScroll: true,
            onFinish: () => (showPasswordDialog.value = false),
        });
    } else {
        showPasswordDialog.value = false;
    }
}

function toggle2fa(val: boolean) {
    const active = props.two_factor_mode && props.two_factor_mode !== 'disabled';
    if (val && !active) {
        openToggle('enable');
    } else if (!val && active) {
        openToggle('disable');
    }
}

function revoke(id: number) {
    revokeForm.delete(route('settings.security.devices.destroy', id), {
        preserveScroll: true,
    });
}

function revokeAll() {
    revokeForm.delete(route('settings.security.devices.destroyAll'), {
        preserveScroll: true,
    });
}

function submitRename(d: TrustedDevice) {
    renameForm.patch(route('settings.security.devices.rename', d.id), {
        preserveScroll: true,
        onSuccess: () => (editingDeviceId.value = null),
    });
}

const revealIp = (d: TrustedDevice) => {
    if (revealedIps.value[d.id]) return;
    revealedIps.value[d.id] = true;
    if (revealTimers[d.id]) clearTimeout(revealTimers[d.id]);
    revealTimers[d.id] = setTimeout(() => {
        revealedIps.value[d.id] = false;
    }, 15000); // 15s
};

async function fetchGeoFor(ip: string) {
    if (!ip || deviceGeo.value[ip]) return;
    try {
        const resp = await fetch(`/geo/ip?ip=${encodeURIComponent(ip)}`);
        if (!resp.ok) return;
        const json = await resp.json();
        if (json?.data) {
            deviceGeo.value[ip] = { latitude: json.data.latitude, longitude: json.data.longitude };
        }
    } catch {
        // silent
    }
}

async function primeGeo() {
    loadingGeo.value = true;
    const ips = Array.from(new Set(props.devices.map((d) => d.ip_address).filter(Boolean) as string[])).slice(0, 15); // cap to 15 lookups
    await Promise.all(ips.map((ip) => fetchGeoFor(ip)));
    loadingGeo.value = false;
}

onMounted(() => {
    primeGeo();
});

const devicesWithGeo = computed(() => {
    return props.devices.map((d) => {
        const geo = d.ip_address ? deviceGeo.value[d.ip_address] : undefined;
        return { ...d, latitude: geo?.latitude, longitude: geo?.longitude, _revealed: revealedIps.value[d.id] } as any;
    });
});

function logoutOthers() {
    logoutOthersForm.post(route('settings.security.logoutOthers'), {
        preserveScroll: true,
        onSuccess: () => logoutOthersForm.reset('password'),
    });
}
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Head :title="t('security.title', 'Configurações de Segurança')" />
        <SettingsLayout>
            <div class="space-y-12">
                <div class="space-y-2">
                    <HeadingSmall
                        :title="t('security.breadcrumb', 'Segurança')"
                        :description="t('security.2fa.description', 'Gerencie autenticação em dois fatores e dispositivos confiáveis')"
                    />
                    <p class="text-xs text-muted-foreground" v-text="t('security.2fa.description')" />
                </div>

                <div class="space-y-4">
                    <div class="flex items-start justify-between gap-4 rounded-md border p-4">
                        <div class="space-y-1">
                            <h3 class="flex items-center gap-2 text-sm font-medium">
                                {{ t('security.2fa.title', 'Autenticação em Dois Fatores') }}
                                <span
                                    v-if="two_factor_mode === 'email_on_new_device'"
                                    class="rounded-full bg-green-100 px-2 py-0.5 text-[10px] font-medium text-green-700"
                                    >{{ t('security.2fa.enabled_badge', 'ATIVO') }}</span
                                >
                                <span v-else class="rounded-full bg-gray-100 px-2 py-0.5 text-[10px] font-medium text-gray-600">{{
                                    t('security.2fa.disabled_badge', 'DESATIVADO')
                                }}</span>
                            </h3>
                            <p class="max-w-md text-xs text-muted-foreground" v-text="t('security.2fa.description')" />
                            <div class="text-[10px] text-destructive" v-if="passwordForm.errors.password">{{ passwordForm.errors.password }}</div>
                            <p v-if="enabling" class="text-[10px] text-muted-foreground">Processando...</p>
                            <div class="flex items-center gap-2 pt-1">
                                <Button size="sm" variant="outline" @click="toggle2fa(!(two_factor_mode === 'email_on_new_device'))">
                                    {{
                                        two_factor_mode === 'email_on_new_device'
                                            ? t('security.2fa.disable', 'Desativar')
                                            : t('security.2fa.enable', 'Ativar')
                                    }}
                                </Button>
                            </div>
                        </div>
                    </div>
                </div>

                <Separator />

                <div class="space-y-4">
                    <div class="space-y-2">
                        <div class="flex items-start justify-between">
                            <div class="space-y-1">
                                <h3 class="text-sm font-medium">{{ t('security.devices.title', 'Dispositivos Confiáveis') }}</h3>
                                <p class="max-w-md text-xs text-muted-foreground" v-text="t('security.devices.description')" />
                            </div>
                            <div class="flex gap-2">
                                <Button size="sm" variant="outline" :disabled="!devices.length" @click="revokeAll">{{
                                    t('security.devices.revoke_all', 'Revogar todos')
                                }}</Button>
                            </div>
                        </div>
                        <div
                            v-if="!devices.length"
                            class="text-xs text-muted-foreground"
                            v-text="t('security.devices.none', 'Nenhum dispositivo confiável ainda.')"
                        />
                        <div v-else class="space-y-4">
                            <DeviceWithMapCard
                                v-for="d in devicesWithGeo"
                                :key="d.id"
                                :device="d"
                                :is-current="d.id === current_device_id"
                                @revoke="revoke"
                                @rename="
                                    (payload) => {
                                        editingDeviceId = d.id;
                                        renameForm.name = payload.name;
                                        submitRename(d);
                                    }
                                "
                                @reveal-ip="() => revealIp(d)"
                            />
                        </div>
                    </div>
                </div>

                <Separator />

                <div class="space-y-4">
                    <div class="flex items-start justify-between gap-4 rounded-md border p-4">
                        <div class="space-y-1">
                            <h3 class="text-sm font-medium">{{ t('security.sessions.title', 'Encerrar outras sessões') }}</h3>
                            <p
                                class="max-w-md text-xs text-muted-foreground"
                                v-text="t('security.sessions.description', 'Encerra sessões autenticadas em outros navegadores/dispositivos.')"
                            />
                            <form class="flex max-w-sm flex-col gap-2 pt-1" @submit.prevent="logoutOthers">
                                <div class="flex gap-2">
                                    <input
                                        type="password"
                                        v-model="logoutOthersForm.password"
                                        placeholder="Senha atual"
                                        class="w-full rounded border bg-background px-2 py-1 text-sm"
                                    />
                                    <Button size="sm" :disabled="!logoutOthersForm.password || logoutOthersForm.processing">{{
                                        t('security.sessions.logout', 'Logout')
                                    }}</Button>
                                </div>
                                <div class="text-[10px] text-destructive" v-if="logoutOthersForm.errors.password">
                                    {{ logoutOthersForm.errors.password }}
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Password Dialog -->
                <div v-if="showPasswordDialog" class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4">
                    <div class="w-full max-w-sm rounded-md border bg-background p-4 shadow-lg">
                        <h4 class="mb-2 text-sm font-medium">{{ t('security.2fa.confirm_password_title', 'Confirmar senha') }}</h4>
                        <p class="mb-3 text-xs text-muted-foreground">
                            {{
                                t('security.2fa.confirm_password_body', 'Digite sua senha para :action a autenticação em dois fatores.', {
                                    action:
                                        desired2faState === 'enable'
                                            ? t('security.2fa.action_enable', 'ativar')
                                            : t('security.2fa.action_disable', 'desativar'),
                                })
                            }}
                        </p>
                        <form @submit.prevent="submit2fa" class="space-y-3">
                            <input
                                type="password"
                                v-model="passwordForm.password"
                                placeholder="Senha atual"
                                class="w-full rounded border bg-background px-2 py-1 text-sm"
                                autofocus
                            />
                            <div class="flex justify-end gap-2">
                                <Button type="button" variant="ghost" size="sm" @click="showPasswordDialog = false">{{
                                    t('common.actions.cancel', 'Cancelar')
                                }}</Button>
                                <Button size="sm" :disabled="!passwordForm.password || passwordForm.processing">
                                    {{ desired2faState === 'enable' ? t('security.2fa.enable', 'Ativar') : t('security.2fa.disable', 'Desativar') }}
                                </Button>
                            </div>
                            <div class="text-[10px] text-destructive" v-if="passwordForm.errors.password">{{ passwordForm.errors.password }}</div>
                        </form>
                    </div>
                </div>
            </div>
        </SettingsLayout>
    </AppLayout>
</template>

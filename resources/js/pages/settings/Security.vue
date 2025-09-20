<script setup lang="ts">
import HeadingSmall from '@/components/HeadingSmall.vue';
import { Button } from '@/components/ui/button';
import { Separator } from '@/components/ui/separator';
import { Switch } from '@/components/ui/switch';
import AppLayout from '@/layouts/AppLayout.vue';
import SettingsLayout from '@/layouts/settings/Layout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

interface TrustedDevice {
    id: number;
    name: string | null;
    last_used_at: string;
    created_at: string;
    current?: boolean;
}

const props = defineProps<{
    two_factor_mode: string | null; // backend uses 'disabled' or 'email_on_new_device'
    devices: TrustedDevice[];
    current_device_id: number | null;
}>();

const enableForm = useForm({ password: '' });
const disableForm = useForm({ password: '' });
const revokeForm = useForm<{ id?: number }>({});
const logoutOthersForm = useForm({ password: '' });

const enabling = ref(false);

const breadcrumbs: BreadcrumbItem[] = [{ title: 'Security settings', href: '/settings/security' }];

function toggle2fa(val: boolean) {
    const active = props.two_factor_mode && props.two_factor_mode !== 'disabled';
    if (val && !active) {
        enabling.value = true;
        enableForm.post(route('settings.security.2fa.enable'), {
            preserveScroll: true,
            onFinish: () => (enabling.value = false),
        });
    } else if (!val && active) {
        disableForm.delete(route('settings.security.2fa.disable'), {
            preserveScroll: true,
        });
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

function logoutOthers() {
    logoutOthersForm.post(route('settings.security.logoutOthers'), {
        preserveScroll: true,
        onSuccess: () => logoutOthersForm.reset('password'),
    });
}
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Head title="Security settings" />
        <SettingsLayout>
            <div class="space-y-12">
                <div class="space-y-2">
                    <HeadingSmall title="Security" description="Manage two-factor authentication and trusted devices" />
                    <p class="text-xs text-muted-foreground">
                        Habilite 2FA para maior segurança. Novo dispositivos precisarão confirmar via código enviado ao seu email.
                    </p>
                </div>

                <div class="space-y-4">
                    <div class="flex items-center justify-between">
                        <div class="space-y-1">
                            <h3 class="text-sm font-medium">Two-Factor Authentication</h3>
                            <p class="text-xs text-muted-foreground">Requer código por email em novos dispositivos ou navegadores.</p>
                        </div>
                        <Switch :checked="!!two_factor_mode" @update:checked="toggle2fa" />
                    </div>
                    <div class="flex max-w-sm items-center gap-2">
                        <input
                            type="password"
                            v-model="enableForm.password"
                            placeholder="Senha atual"
                            class="w-full rounded border bg-background px-2 py-1 text-xs"
                        />
                        <Button size="sm" variant="outline" @click="toggle2fa(two_factor_mode !== 'email_on_new_device')">
                            {{ two_factor_mode === 'email_on_new_device' ? 'Desativar' : 'Ativar' }}
                        </Button>
                    </div>
                    <div class="text-[10px] text-destructive" v-if="enableForm.errors.password || disableForm.errors.password">
                        {{ enableForm.errors.password || disableForm.errors.password }}
                    </div>
                    <p v-if="enabling" class="text-xs text-muted-foreground">Ativando...</p>
                    <p v-if="two_factor_mode === 'email_on_new_device'" class="text-xs text-green-600">Ativo (modo: {{ two_factor_mode }})</p>
                </div>

                <Separator />

                <div class="space-y-4">
                    <div class="flex items-center justify-between">
                        <div class="space-y-1">
                            <h3 class="text-sm font-medium">Trusted Devices</h3>
                            <p class="text-xs text-muted-foreground">Dispositivos que não exigirão novo código até expirar.</p>
                        </div>
                        <div class="flex gap-2">
                            <Button size="sm" variant="outline" :disabled="!devices.length" @click="revokeAll">Revoke all</Button>
                        </div>
                    </div>
                    <div v-if="!devices.length" class="text-xs text-muted-foreground">Nenhum dispositivo confiável ainda.</div>
                    <ul v-else class="space-y-2">
                        <li v-for="d in devices" :key="d.id" class="flex items-center justify-between rounded border p-2 text-xs">
                            <div class="flex flex-col">
                                <span
                                    >{{ d.name || 'Device #' + d.id }} <span v-if="d.current" class="text-[10px] text-blue-600">(current)</span></span
                                >
                                <span class="text-muted-foreground">Last used: {{ d.last_used_at }}</span>
                            </div>
                            <div class="flex gap-2">
                                <Button size="sm" variant="outline" @click="revoke(d.id)" :disabled="d.current">Revoke</Button>
                            </div>
                        </li>
                    </ul>
                </div>

                <Separator />

                <div class="space-y-4">
                    <div class="space-y-1">
                        <h3 class="text-sm font-medium">Logout other sessions</h3>
                        <p class="text-xs text-muted-foreground">
                            Se você acha que sua conta foi acessada indevidamente, desconecte outras sessões ativas. Requer sua senha atual.
                        </p>
                    </div>
                    <form class="flex max-w-sm flex-col gap-2" @submit.prevent="logoutOthers">
                        <input
                            type="password"
                            v-model="logoutOthersForm.password"
                            placeholder="Current password"
                            class="rounded border bg-background px-2 py-1 text-sm"
                        />
                        <div class="flex gap-2">
                            <Button size="sm" :disabled="!logoutOthersForm.password">Logout others</Button>
                        </div>
                    </form>
                </div>
            </div>
        </SettingsLayout>
    </AppLayout>
</template>

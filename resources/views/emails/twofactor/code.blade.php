@php($ttlMinutes = (int) (config('twofactor.code_ttl', 300) / 60))
<!DOCTYPE html>
<html lang="pt-BR">
<head>
	<meta charset="UTF-8" />
	<meta name="viewport" content="width=device-width,initial-scale=1" />
	<title>Código de Segurança - {{ config('app.name') }}</title>
	<style>
		:root { color-scheme: light dark; }
		body { font-family: system-ui,-apple-system,'Segoe UI',Roboto,Helvetica,Arial,sans-serif; background:#f5f7fa; color:#1f2937; padding:32px 20px; }
		.card { background:#ffffff; border-radius:12px; padding:32px 36px; max-width:560px; margin:0 auto; box-shadow:0 4px 18px -4px rgba(0,0,0,0.08),0 2px 4px rgba(0,0,0,0.04); position:relative; overflow:hidden; }
		.card:before { content:""; position:absolute; inset:0; background:radial-gradient(circle at 85% 15%,rgba(99,102,241,0.10),transparent 60%); pointer-events:none; }
		h1 { margin:0 0 16px; font-size:20px; line-height:1.2; font-weight:600; letter-spacing:0.5px; }
		p { line-height:1.55; font-size:15px; margin:0 0 18px; }
		.code-wrapper { text-align:center; margin:28px 0 24px; }
		.code { font-size:34px; font-weight:700; letter-spacing:10px; display:inline-block; padding:14px 24px; background:#111827; color:#fff; border-radius:10px; font-family:'SF Mono','Segoe UI Mono','Roboto Mono',monospace; box-shadow:0 2px 6px rgba(0,0,0,0.25); }
		.meta { font-size:12px; color:#4b5563; margin-top:6px; }
		.divider { height:1px; background:linear-gradient(to right,#e5e7eb,transparent); margin:28px 0 24px; border:0; }
		.brand { font-size:13px; font-weight:600; letter-spacing:0.5px; color:#6b7280; margin-top:32px; }
		.tip { background:#f3f4f6; padding:12px 14px; font-size:12px; border-radius:8px; line-height:1.45; color:#374151; }
		a.btn { display:inline-block; background:#2563eb; color:#fff !important; text-decoration:none; font-size:13px; font-weight:600; padding:10px 18px; border-radius:8px; letter-spacing:0.3px; box-shadow:0 2px 4px rgba(0,0,0,0.12); }
		small { font-size:12px; color:#6b7280; }
		@media (max-width:640px) { body { padding:20px 14px; } .card { padding:28px 24px; } .code { font-size:30px; letter-spacing:8px; padding:12px 20px; } }
		@media (prefers-color-scheme: dark) {
			body { background:#0f172a; color:#e2e8f0; }
			.card { background:#1e293b; box-shadow:0 4px 22px -4px rgba(0,0,0,0.55),0 2px 4px rgba(0,0,0,0.4); }
			.code { background:#000; }
			.meta, small, .brand { color:#94a3b8; }
			.tip { background:#334155; color:#e2e8f0; }
			.divider { background:#334155; }
		}
	</style>
</head>
<body>
	<div class="card" role="article" aria-label="Código de verificação">
		<h1>Verificação de Segurança</h1>
		<p>Olá {{ $user->name }},</p>
		<p>Use o código abaixo para continuar seu acesso em um novo dispositivo ou navegador. Se não foi você, ignore este e-mail.</p>
		<p style="font-size:13px; margin-top:-4px;">Este código expira em {{ $ttlMinutes }} minuto{{ $ttlMinutes === 1 ? '' : 's' }}.</p>
		<hr style="border:none; border-top:1px solid #eee; margin:16px 0;" />
		<p style="font-size:13px; color:#444;">English: This code expires in {{ $ttlMinutes }} minute{{ $ttlMinutes === 1 ? '' : 's' }}.</p>
		<p style="font-size:13px; color:#444;">Français : Ce code expire dans {{ $ttlMinutes }} minute{{ $ttlMinutes === 1 ? '' : 's' }}.</p>

		<div class="code-wrapper">
			<span class="code" aria-label="Código de verificação">{{ $code }}</span>
			<div class="meta">Expira em {{ $ttlMinutes }} minuto{{ $ttlMinutes === 1 ? '' : 's' }}.</div>
		</div>

		<div class="tip">
			Nunca compartilhe este código. Ele só deve ser digitado diretamente no site oficial (<strong>{{ parse_url(config('app.url'), PHP_URL_HOST) }}</strong>).
		</div>

		<hr class="divider" />
		<p style="margin:0 0 14px; font-size:13px;">Se você habilitou a lista de dispositivos confiáveis, o aparelho ficará salvo após validar este código (caso tenha marcado a opção de confiar).</p>
		<small>Recomenda-se revisar periodicamente seus dispositivos em Configurações &gt; Segurança.</small>

		<div class="brand">— {{ config('app.name') }}</div>
	</div>
</body>
</html>
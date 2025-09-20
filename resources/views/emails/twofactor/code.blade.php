<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8" />
	<title>Your Security Code</title>
	<style>
		body { font-family: system-ui,-apple-system,'Segoe UI',Roboto,Helvetica,Arial,sans-serif; background:#f6f6f8; color:#222; padding:24px; }
		.card { background:#fff; border-radius:8px; padding:28px 32px; max-width:520px; margin:0 auto; box-shadow:0 2px 6px rgba(0,0,0,0.06); }
		.code { font-size:32px; font-weight:700; letter-spacing:8px; margin:24px 0; display:inline-block; padding:12px 20px; background:#111; color:#fff; border-radius:8px; }
		p { line-height:1.5; font-size:14px; margin:0 0 14px; }
		.muted { color:#555; font-size:12px; }
		.brand { font-size:13px; font-weight:600; letter-spacing:0.5px; color:#555; margin-top:32px; }
	</style>
</head>
<body>
	<div class="card">
		<h1 style="margin:0 0 12px;font-size:20px;">Security verification</h1>
		<p>Hello {{ $user->name }},</p>
		<p>Use the code below to continuar seu login em um novo dispositivo / navegador:</p>
		<span class="code">{{ $code }}</span>
		<p>O código expira em {{ (int) (config('twofactor.code_ttl', 300) / 60) }} minutos. Não compartilhe este código.</p>
		<p class="muted">Se você não tentou acessar sua conta, pode ignorar este e-mail. Recomendamos revisar dispositivos confiáveis em Configurações &gt; Security.</p>
		<div class="brand">— {{ config('app.name') }}</div>
	</div>
</body>
</html>
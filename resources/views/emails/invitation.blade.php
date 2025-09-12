@php($acceptUrl = route('invites.accept', $plainToken))
@php($declineUrl = route('invites.decline', $plainToken))
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8" />
    <title>Convite - {{ $group->name }}</title>
    <style>
        body { font-family: system-ui,-apple-system,'Segoe UI',Roboto,Helvetica,Arial,sans-serif; background:#f6f6f8; color:#222; padding:24px; }
        .card { background:#fff; border-radius:8px; padding:24px; max-width:560px; margin:0 auto; box-shadow:0 2px 6px rgba(0,0,0,0.06); }
        a.button { display:inline-block; padding:10px 16px; border-radius:6px; text-decoration:none; font-size:14px; font-weight:600; }
        a.primary { background:#2563eb; color:#fff; }
        a.secondary { background:#e5e7eb; color:#111; }
        p { line-height:1.5; }
        small { color:#555; font-size:12px; }
    </style>
</head>
<body>
    <div class="card">
        <h1 style="margin:0 0 12px;font-size:20px;">Você foi convidado para participar de {{ $group->name }}</h1>
        @if($group->description)
            <p style="margin:0 0 16px;">{{ $group->description }}</p>
        @endif
        <p style="margin:0 0 16px;">Clique em aceitar para participar do Amigo Secreto. Caso não queira, pode recusar.</p>
        <p style="margin:0 0 20px;">
            <a href="{{ $acceptUrl }}" class="button primary" target="_blank" rel="noopener">Aceitar Convite</a>
            <a href="{{ $declineUrl }}" class="button secondary" target="_blank" rel="noopener" style="margin-left:8px;">Recusar</a>
        </p>
        <small>Se você não esperava este e-mail, pode ignorá-lo.</small>
    </div>
</body>
</html>

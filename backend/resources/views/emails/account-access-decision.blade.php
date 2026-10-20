<!doctype html>
<html lang="pt-BR">
<head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Solicitação de acesso</title></head>
<body style="margin:0;background:#f3f7f8;color:#183238;font-family:Arial,sans-serif;line-height:1.6;padding:32px 16px">
  <main style="max-width:560px;margin:0 auto;background:#fff;border:1px solid #dce8e9;border-radius:16px;padding:32px">
    <p style="color:#0b7773;font-size:12px;font-weight:bold;letter-spacing:1.4px">CONTROLE DE FORNECEDORES</p>
    <h1 style="font-size:24px;margin-bottom:8px">Olá, {{ $accessRequest->name }}.</h1>
    @if ($setupUrl)
      <p>Sua solicitação de acesso foi aprovada. Abra o link para entrar no seu perfil e definir sua senha.</p>
      <p style="margin:28px 0"><a href="{{ $setupUrl }}" style="display:inline-block;background:#087f78;border-radius:9px;color:#fff;font-weight:bold;padding:13px 20px;text-decoration:none">Entrar e definir minha senha</a></p>
      <p>Este link expira em 48 horas. Se não solicitou acesso, ignore esta mensagem.</p>
    @else
      <p>Sua solicitação de acesso foi recusada pelo administrador.</p>
      @if ($accessRequest->response_message)
        <p><strong>Mensagem do administrador:</strong><br>{{ $accessRequest->response_message }}</p>
      @endif
      <p>Se precisar de mais informações, entre em contato com o administrador do sistema.</p>
    @endif
  </main>
</body>
</html>

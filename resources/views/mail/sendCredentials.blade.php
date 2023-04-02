<!doctype html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport"
          content="width=device-width, user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Credenciais TTE</title>
</head>
<body>
    <h3>Credenciais de Acesso:</h3>
    <p><strong>Email:</strong> {{ $email }}</p>
    <p><strong>Senha:</strong> {{ $password }}</p>

    <br>

    <a href="{{ env('APP_FRONTEND_URL') }}">Entrar na plataforma</a>

    <p>Aconselhamos a atualizar a sua senha assim que iniciar a sessão pela primeira vez na plataforma.</p>
</body>
</html>

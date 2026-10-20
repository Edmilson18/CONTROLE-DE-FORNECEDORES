# Controle de Fornecedores

Sistema web para acompanhar fornecedores, compras, pagamentos e vencimentos. A interface usa Angular e Tailwind CSS; a API usa Laravel e Sanctum; os dados ficam em MariaDB, executado com Docker Compose.

## O que o sistema oferece

- Cadastro e consulta de fornecedores, com validação de CNPJ numérico ou alfanumérico e máscaras de CNPJ e telefone.
- Compras com vencimento, pesquisa, filtros e paginação.
- Pagamentos parciais, controle de saldo e estornos com motivo e registro de auditoria.
- Painel com totais, compras recentes e vencimentos.
- Relatórios com filtros e exportação em CSV e PDF.
- Notificações internas de vencimentos próximos e atrasados.
- Perfis de consulta, operador e administrador, com permissões diferentes.
- Solicitação de acesso analisada pelo administrador, com resposta por e-mail e ativação segura no próprio perfil.
- Histórico de alterações com usuário responsável e valores anteriores e atuais.

## Tecnologias e requisitos

- PHP 8.3 ou superior e Composer 2.
- Extensões PHP PDO MySQL e SQLite (SQLite é usado nos testes).
- Node.js 24.15 ou superior e npm.
- Docker Desktop com Docker Compose.

No XAMPP deste projeto, o PHP 8.3 está em `C:\xampp\php83`. Use essa versão no terminal de instalação para que o Composer e o Laravel utilizem o PHP correto.

## Instalação local no Windows

### 1. Configure o banco

Na raiz do projeto, crie o arquivo local de configuração e substitua os dois valores por senhas diferentes:

```powershell
Copy-Item .env.example .env
```

Inicie o MariaDB:

```powershell
docker compose up -d banco
```

O banco fica disponível somente na máquina local, na porta `3307`. O volume `dados_mariadb` mantém os dados entre reinicializações do Docker.

### 2. Configure a API Laravel

```powershell
Copy-Item backend\.env.example backend\.env
```

Edite `backend\.env` e configure:

- `APP_URL=http://127.0.0.1:8000`.
- `FRONTEND_URL=http://127.0.0.1:4200`.
- `DB_PASSWORD` com o mesmo valor de `DB_PASSWORD` do arquivo `.env` da raiz.
- `SANCTUM_STATEFUL_DOMAINS` incluindo `127.0.0.1:4200` e `127.0.0.1:8000`.
- Para enviar as decisões por e-mail, configure `MAIL_MAILER`, `MAIL_SCHEME`, `MAIL_HOST`, `MAIL_PORT`, `MAIL_USERNAME`, `MAIL_PASSWORD` e `MAIL_FROM_ADDRESS` com os dados do seu provedor SMTP. Mantenha senhas e chaves apenas em `backend/.env`.

Instale as dependências e prepare o Laravel:

```powershell
$env:Path = "C:\xampp\php83;$env:Path"
php -v
Set-Location backend
composer install
php artisan key:generate
php artisan migrate --force
php artisan app:admin administrador@exemplo.com --name="Administrador"
```

O comando `app:admin` solicita a senha sem exibi-la. Crie uma senha exclusiva com pelo menos 8 caracteres, incluindo letras maiúsculas e minúsculas e números. As migrações criam as tabelas de negócio e de suporte quando necessário; não é preciso importar o arquivo SQL para uma instalação nova.

### 3. Inicie a API e a interface

Em um terminal, na pasta `backend`, execute:

```powershell
C:\xampp\php83\php.exe artisan serve --host=127.0.0.1 --port=8000
```

Em outro terminal, na pasta `frontend`, instale as dependências e inicie o Angular:

```powershell
npm ci
npm start
```

Abra [http://127.0.0.1:4200](http://127.0.0.1:4200). Mantenha o mesmo endereço `127.0.0.1` nos dois serviços para que os cookies de sessão e a proteção CSRF funcionem corretamente. O proxy do Angular encaminha as chamadas de `/api` e `/sanctum` para a API Laravel.

O `index.php` e as URLs PHP antigas redirecionam para a interface Angular. Para esses redirecionamentos funcionarem, mantenha o servidor Angular iniciado.

O sistema usa o fuso horário `America/Sao_Paulo`. A API recebe e devolve datas no formato `AAAA-MM-DD`; a interface apresenta datas e valores no padrão brasileiro.

## Banco de dados e migração de dados

O arquivo [`Schema Controle de Fornecedores.sql`](Schema%20Controle%20de%20Fornecedores.sql) contém apenas a estrutura inicial das tabelas; não contém registros de fornecedores, compras, pagamentos, senhas ou credenciais. Para uma instalação nova, use as migrações Laravel descritas acima.

Se já houver dados em outro banco, faça e verifique um backup antes de importar ou executar migrações. Planeje a transferência para o MariaDB e valide os registros importados antes de voltar a usar o sistema. As migrações são aditivas e preservam as tabelas de negócio existentes. Não use `php artisan migrate:fresh` em um banco com dados.

Compras antigas marcadas como pagas sem registros correspondentes em `pagamentos` continuam tratadas como quitadas; o sistema não inventa pagamentos. O histórico permanece disponível e impede novos pagamentos ou exclusões indevidas. Evite `docker compose down -v`, pois esse comando remove o volume do banco.

## Testes e compilação

Execute os testes e a verificação de estilo da API na pasta `backend`:

```powershell
C:\xampp\php83\php.exe -d extension=pdo_sqlite vendor\bin\phpunit
C:\xampp\php83\php.exe vendor\bin\pint --test
```

Execute os testes e compile a interface na pasta `frontend`:

```powershell
npm test -- --watch=false
npm run build
```

Os testes do backend usam SQLite em memória e não alteram o banco de desenvolvimento. A compilação Angular gera os arquivos em `frontend/dist/frontend`.

## Perfis de acesso

| Perfil | Permissões principais |
| --- | --- |
| Consulta | Consultar fornecedores, compras, relatórios e notificações |
| Operador | Consultar e gerenciar fornecedores e compras, registrar pagamentos e alterar a própria senha |
| Administrador | Todas as operações, incluindo usuários, auditoria, exclusões e estornos |

Qualquer pessoa pode enviar um pedido pelo link da tela de login, com e-mail obrigatório e justificativa de pelo menos 100 caracteres. O pedido aparece para o administrador em **Solicitações de acesso**. Cada conta precisa ser aprovada; não há criação automática. Usuários desativados deixam de acessar o sistema.

Ao aprovar, a pessoa recebe um link com validade de 48 horas. A conta aprovada recebe o perfil **Operador**, que permite cadastrar fornecedores e compras sem acesso à administração de usuários. O link abre a sessão e leva diretamente a **Minha conta**, onde ela define a primeira senha sem informar uma senha anterior nem repetir a nova. Até concluir a etapa, as outras áreas ficam bloqueadas. Nas trocas seguintes, o perfil exige a senha atual e a confirmação da nova. Links de ativação expirados podem ser reenviados pelo administrador.

A primeira senha precisa ter pelo menos 8 caracteres, incluindo maiúsculas, minúsculas e números; caractere especial não é obrigatório. Na atualização da conta, esta migração também pede que administradores já existentes definam uma senha no perfil na próxima sessão. O acesso normal exigirá a senha atual e a confirmação da nova nas trocas posteriores.

Solicitações aprovadas ou recusadas recebem uma resposta por e-mail. O envio real exige SMTP válido. Com `MAIL_MAILER=log`, o sistema bloqueia a decisão para não indicar que o e-mail foi entregue quando não foi; pedidos recebidos continuam visíveis para o administrador até o SMTP ser configurado.

## API

Base local: `http://127.0.0.1:8000/api`. A autenticação usa sessão Sanctum e cookies protegidos por CSRF. Exceto o login, os endpoints exigem autenticação.

| Método e rota | Ação | Acesso |
| --- | --- | --- |
| `POST /api/auth/login` | Iniciar sessão (`email`, `password`) | Público, com limite de tentativas |
| `POST /api/solicitacoes-acesso` | Solicitar uma conta (`name`, `email`, `message` de pelo menos 100 caracteres) | Público, com limite de tentativas |
| `POST /api/acessos/ativar` | Abrir sessão de ativação pelo token do e-mail | Público, token temporário |
| `GET /api/auth/me` | Consultar a sessão | Autenticado |
| `POST /api/auth/logout` | Encerrar sessão | Autenticado |
| `PUT /api/auth/password` | Alterar senha | Autenticado |
| `GET /api/dashboard` | Consultar indicadores e compras recentes | Todos os perfis |
| `GET, POST /api/fornecedores` | Listar e cadastrar fornecedores | Autenticado; alterações conforme perfil |
| `GET, PUT, DELETE /api/fornecedores/{id}` | Consultar, editar ou arquivar fornecedor | Autenticado; alterações conforme perfil |
| `GET, POST /api/compras` | Listar e cadastrar compras | Autenticado; alterações conforme perfil |
| `GET, PUT, DELETE /api/compras/{id}` | Consultar, editar ou excluir compra | Autenticado; alterações conforme perfil |
| `POST /api/compras/{id}/pagamentos` | Registrar pagamento parcial ou final | Operador |
| `POST /api/pagamentos/{id}/estorno` | Estornar pagamento com motivo | Administrador |
| `GET /api/relatorios` | Consultar relatório | Todos os perfis |
| `GET /api/relatorios/exportar?formato=csv\|pdf` | Exportar relatório | Todos os perfis |
| `GET /api/notificacoes` | Listar notificações | Autenticado |
| `PATCH /api/notificacoes/{id}/ler` | Marcar notificação como lida | Autenticado |
| `GET, POST /api/usuarios`; `PUT /api/usuarios/{id}` | Gerenciar usuários e perfis | Administrador |
| `GET /api/auditoria` | Consultar alterações | Administrador |
| `GET /api/solicitacoes-acesso`; `PATCH /api/solicitacoes-acesso/{id}` | Analisar e responder pedidos de conta | Administrador |
| `POST /api/solicitacoes-acesso/{id}/reenviar` | Reenviar link de ativação | Administrador |

Compras e relatórios aceitam filtros `q`, `status`, `fornecedor_id`, `de`, `ate`, `page` e `per_page` (máximo de 100 itens). Datas usam `AAAA-MM-DD`; valores enviados à API usam ponto decimal e até duas casas. As respostas paginadas incluem `data`, `links` e `meta`.

## Notificações de vencimento

As notificações internas são geradas uma vez por usuário, compra e dia. Para executar manualmente, use `php artisan pagamentos:notificar`. Durante o desenvolvimento, `php artisan schedule:work` mantém o agendador ativo. Em produção, configure o servidor para executar `php artisan schedule:run` a cada minuto. O projeto não envia e-mails de notificação.

## Estrutura do projeto

```text
backend/       API Laravel, migrações, regras de negócio e testes
frontend/      Interface Angular e Tailwind CSS
compose.yaml   Serviço MariaDB e volume persistente
config/        Compatibilidade com o projeto PHP anterior
```

Não versione arquivos `.env`, credenciais, backups, `backend/vendor` ou `frontend/node_modules`. Esses itens são ignorados pelo Git. O arquivo SQL versionado contém o esquema sem dados pessoais.

# API WhatsApp SaaS

Repositorio: https://github.com/oswaldopaulo/apiwhatsapp/

API SaaS multi-tenant para WhatsApp, construida com Laravel 12. A arquitetura foi preparada para alta escalabilidade, isolamento por tenant, filas obrigatorias para envio de mensagens e operacao em VPS Linux com Supervisor.

## Stack

- Laravel 12
- Laravel Passport
- Laravel Reverb
- Laravel Queue
- MongoDB com `mongodb/laravel-mongodb`
- Banco relacional para tenants, usuarios, OAuth2, permissoes e configuracoes
- Redis com `phpredis/ext-redis` em producao
- Database/cache como fallback quando configurado
- PHPUnit
- Spatie Laravel Permission
- Spatie Laravel Data
- Spatie Activity Log
- Spatie Rate Limited Job Middleware
- Supervisor para workers, Reverb e scheduler

## Principios

- API multi-tenant.
- Toda mensagem passa por fila.
- Controllers nao enviam mensagens diretamente.
- MongoDB armazena mensagens, logs, eventos, webhooks e estatisticas.
- Banco relacional armazena tenants, usuarios, permissoes e configuracoes.
- Redis em producao para filas, locks, cache, rate limit e controle anti-ban.
- Reverb para eventos em tempo real.

## Documentacao

- Pagina inicial local: `/`
- Producao VPS: [`public/docs/production.md`](public/docs/production.md)
- Checklist de deploy: [`public/docs/deploy-checklist.md`](public/docs/deploy-checklist.md)
- Comandos uteis: [`public/docs/operations.md`](public/docs/operations.md)
- Swagger/OpenAPI: [`public/docs/api.html`](public/docs/api.html)
- Nginx: [`public/docs/nginx/apiwhatsapp.conf`](public/docs/nginx/apiwhatsapp.conf)
- Supervisor workers:
  - [`whatsapp-worker.conf`](public/docs/supervisor/whatsapp-worker.conf)
  - [`webhooks-worker.conf`](public/docs/supervisor/webhooks-worker.conf)
  - [`default-worker.conf`](public/docs/supervisor/default-worker.conf)
  - [`reverb.conf`](public/docs/supervisor/reverb.conf)
  - [`scheduler.conf`](public/docs/supervisor/scheduler.conf)
- Exemplos de consumo:
  - [`laravel-client.md`](public/docs/examples/laravel-client.md)
  - [`node-client.js`](public/docs/examples/node-client.js)
  - [`browser-client.js`](public/docs/examples/browser-client.js)
- Testes: [`tests/README.md`](tests/README.md)

## Instalar localmente

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate
php artisan passport:keys
php artisan serve
```

No Windows local, use `database`/`array` como fallback para cache e filas quando Redis nao estiver disponivel.

## Testes

```bash
php artisan test
php artisan test --testsuite=Unit
php artisan test --testsuite=Feature
```

A suite usa SQLite em memoria, `Queue::fake()`, `Event::fake()` e fakes em memoria para evitar chamadas reais a Redis, MongoDB Atlas e providers externos.

## Deploy resumido

```bash
cd /var/www/apiwhatsapp
git pull
composer install --no-dev --optimize-autoloader
php artisan migrate --force
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache
php artisan queue:restart
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl restart apiwhatsapp-whatsapp-worker:*
sudo supervisorctl restart apiwhatsapp-webhooks-worker:*
sudo supervisorctl restart apiwhatsapp-default-worker:*
sudo supervisorctl restart apiwhatsapp-reverb
```

Veja o guia completo em [`public/docs/production.md`](public/docs/production.md).

## Health checks e operacao

```bash
curl -fsS https://api.example.com/up
php artisan queue:failed
sudo supervisorctl status
tail -f storage/logs/laravel.log
```

## Seguranca

- Nao versionar `.env` real.
- Usar `APP_DEBUG=false` em producao.
- Usar TLS no Nginx.
- Usar `REDIS_CLIENT=phpredis`.
- Configurar scopes OAuth2 por consumidor.
- Nunca expor `webhook_secret`, tokens OAuth2 ou credenciais de sessao.
- Rotacionar logs e segredos periodicamente.

## Creditos

Ferramentas e projetos usados: Laravel, Laravel Passport, Laravel Reverb, Laravel Queue, PHPUnit, MongoDB Laravel, MongoDB Atlas, Redis, phpredis/ext-redis, Nginx, PHP-FPM, Supervisor, Composer, Swagger UI, OpenAPI e pacotes Spatie.

Parte da documentacao e da organizacao arquitetural foi preparada com apoio de IA generativa via OpenAI Codex, revisada no contexto deste repositorio.

## Licenca

Defina a licenca do projeto antes de publicacao comercial. Dependencias de terceiros mantem suas respectivas licencas.

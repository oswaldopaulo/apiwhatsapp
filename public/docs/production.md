# Guia de producao - API WhatsApp SaaS

Repositorio: https://github.com/oswaldopaulo/apiwhatsapp/

Este guia assume uma VPS Linux com Nginx, PHP-FPM, Redis com ext-redis/phpredis, MongoDB Atlas, Supervisor, Laravel Queue e Laravel Reverb.

## Premissas

- Branch de deploy: `main`.
- Caminho sugerido: `/var/www/apiwhatsapp`.
- Usuario do servico web: `www-data`.
- PHP: versao compativel com Laravel 12.
- Banco relacional: MySQL, MariaDB ou PostgreSQL.
- MongoDB: Atlas com TLS habilitado.
- Redis: local na VPS ou gerenciado, usando `REDIS_CLIENT=phpredis`.
- Workers gerenciados por Supervisor.

## Exemplo de .env de producao

Nao use estes valores literalmente. Troque dominios, chaves e senhas no servidor.

```dotenv
APP_NAME="API WhatsApp"
APP_ENV=production
APP_KEY=base64:CHANGE_ME
APP_DEBUG=false
APP_URL=https://api.example.com

LOG_CHANNEL=stack
LOG_LEVEL=warning

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=apiwhatsapp
DB_USERNAME=apiwhatsapp
DB_PASSWORD=CHANGE_ME

MONGODB_URI="mongodb+srv://USER:PASSWORD@cluster.example.mongodb.net/?retryWrites=true&w=majority"
MONGODB_DATABASE=apiwhatsapp

REDIS_CLIENT=phpredis
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379

CACHE_STORE=redis
QUEUE_CONNECTION=redis
SESSION_DRIVER=database
BROADCAST_CONNECTION=reverb

WHATSAPP_QUEUE_DRIVER=redis
WHATSAPP_DEFAULT_DELAY_MIN=3
WHATSAPP_DEFAULT_DELAY_MAX=12
WHATSAPP_MAX_MESSAGES_PER_MINUTE=20
WHATSAPP_ANTI_BAN_ENABLED=true
WHATSAPP_WEBHOOK_SECRET=CHANGE_ME_LONG_RANDOM_SECRET

API_RATE_LIMIT_STORE=redis
API_SECURITY_LOCK_STORE=redis
API_REQUIRE_JSON_ACCEPT=true
API_REQUIRE_JSON_CONTENT_TYPE=true

PASSPORT_ACCESS_TOKEN_MINUTES=15
PASSPORT_REFRESH_TOKEN_DAYS=30
PASSPORT_PERSONAL_ACCESS_TOKEN_DAYS=7

REVERB_APP_ID=CHANGE_ME
REVERB_APP_KEY=CHANGE_ME
REVERB_APP_SECRET=CHANGE_ME
REVERB_HOST=0.0.0.0
REVERB_PORT=8080
REVERB_SCHEME=http
VITE_REVERB_APP_KEY="${REVERB_APP_KEY}"
VITE_REVERB_HOST=api.example.com
VITE_REVERB_PORT=443
VITE_REVERB_SCHEME=https
```

## Redis com phpredis

Instale a extensao no servidor conforme a distribuicao:

```bash
sudo apt update
sudo apt install php-redis redis-server
php -m | grep redis
```

Variaveis esperadas:

```dotenv
REDIS_CLIENT=phpredis
CACHE_STORE=redis
QUEUE_CONNECTION=redis
API_RATE_LIMIT_STORE=redis
API_SECURITY_LOCK_STORE=redis
```

Se Redis estiver indisponivel no ambiente local, use `database` ou `array` nos testes. Em producao, prefira Redis para filas, locks, cache e rate limit.

## Deploy inicial

```bash
sudo mkdir -p /var/www/apiwhatsapp
sudo chown -R www-data:www-data /var/www/apiwhatsapp
cd /var/www/apiwhatsapp

sudo -u www-data git clone https://github.com/oswaldopaulo/apiwhatsapp/ .
sudo -u www-data composer install --no-dev --optimize-autoloader
sudo -u www-data cp .env.example .env
sudo -u www-data php artisan key:generate
sudo -u www-data php artisan passport:keys
sudo -u www-data php artisan migrate --force
```

Depois edite `.env` com os segredos reais.

## Cache de producao

Rode apos validar `.env`, migrations e permissoes:

```bash
sudo -u www-data php artisan optimize:clear
sudo -u www-data php artisan config:cache
sudo -u www-data php artisan route:cache
sudo -u www-data php artisan view:cache
sudo -u www-data php artisan event:cache
```

Ao mudar `.env` ou arquivos de rota/configuracao:

```bash
sudo -u www-data php artisan optimize:clear
sudo -u www-data php artisan config:cache
sudo -u www-data php artisan route:cache
sudo -u www-data php artisan view:cache
```

## Permissoes de storage

```bash
sudo chown -R www-data:www-data /var/www/apiwhatsapp
sudo find /var/www/apiwhatsapp/storage -type d -exec chmod 775 {} \;
sudo find /var/www/apiwhatsapp/bootstrap/cache -type d -exec chmod 775 {} \;
sudo find /var/www/apiwhatsapp/storage -type f -exec chmod 664 {} \;
```

## Nginx

Exemplo em `public/docs/nginx/apiwhatsapp.conf`.

Pontos importantes:

- `root` deve apontar para `/var/www/apiwhatsapp/public`.
- Proteger arquivos ocultos e `.env`.
- Encaminhar PHP para PHP-FPM.
- Configurar proxy para Reverb em rota propria, por exemplo `/app`.
- Usar TLS com certificado valido.

## Supervisor

Copie os exemplos de `public/docs/supervisor/*.conf` para `/etc/supervisor/conf.d/` e ajuste:

- `command`
- `directory`
- `user`
- caminhos de log
- numero de processos

Aplicar:

```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl status
```

Filas sugeridas:

- `whatsapp`
- `webhooks`
- `outgoing-webhooks`
- `stats`
- `default`

## Restart seguro dos workers

Quando houver deploy:

```bash
sudo -u www-data php artisan queue:restart
sudo supervisorctl restart apiwhatsapp-whatsapp-worker:*
sudo supervisorctl restart apiwhatsapp-webhooks-worker:*
sudo supervisorctl restart apiwhatsapp-default-worker:*
```

`queue:restart` sinaliza para workers terminarem o job atual antes de reiniciar.

## Reverb

Supervisor recomendado: `public/docs/supervisor/reverb.conf`.

Comando base:

```bash
php artisan reverb:start --host=0.0.0.0 --port=8080
```

No Nginx, use proxy websocket com `Upgrade` e `Connection`. Veja `public/docs/nginx/apiwhatsapp.conf`.

## Scheduler

Escolha apenas uma estrategia.

Opcao A: cron do sistema:

```cron
* * * * * cd /var/www/apiwhatsapp && php artisan schedule:run >> /dev/null 2>&1
```

Opcao B: Supervisor com `schedule:work`, exemplo em `public/docs/supervisor/scheduler.conf`.

## Logs

Arquivos principais:

```bash
tail -f /var/www/apiwhatsapp/storage/logs/laravel.log
sudo tail -f /var/log/nginx/apiwhatsapp-error.log
sudo tail -f /var/log/supervisor/apiwhatsapp-whatsapp-worker.log
sudo tail -f /var/log/supervisor/apiwhatsapp-reverb.log
```

Recomendacoes:

- Nunca logar tokens, senhas, segredos de webhook ou credenciais de sessao.
- Usar `LOG_LEVEL=warning` em producao.
- Configurar rotacao de logs via logrotate.
- Monitorar crescimento de `storage/logs`.

## Health checks

HTTP basico:

```bash
curl -fsS https://api.example.com/up
```

Aplicacao autenticada:

```bash
curl -fsS https://api.example.com/api/me \
  -H "Accept: application/json" \
  -H "Authorization: Bearer ACCESS_TOKEN" \
  -H "X-Tenant-ID: TENANT_PUBLIC_ID"
```

Supervisor:

```bash
sudo supervisorctl status
```

Fila:

```bash
sudo -u www-data php artisan queue:failed
sudo -u www-data php artisan queue:retry all
```

Redis:

```bash
redis-cli ping
redis-cli info memory
redis-cli llen queues:whatsapp
```

## Monitoramento basico sem painel dedicado

- `supervisorctl status` para processos.
- `queue:failed` para jobs com falha.
- Logs do Laravel, Supervisor, Nginx e PHP-FPM.
- Endpoint `/up`.
- Alertas externos por HTTP health check.
- Monitoramento de CPU, memoria, disco e conexoes Redis/MongoDB.
- Alertar se filas ficarem crescendo por muito tempo.

## Checklist de deploy

- [ ] `.env` configurado com `APP_DEBUG=false`.
- [ ] `APP_KEY` gerada.
- [ ] Passport keys geradas.
- [ ] Banco relacional migrado com `--force`.
- [ ] MongoDB Atlas acessivel pela VPS.
- [ ] Redis respondendo com phpredis.
- [ ] `CACHE_STORE=redis` e `QUEUE_CONNECTION=redis` em producao.
- [ ] `storage` e `bootstrap/cache` gravaveis por `www-data`.
- [ ] Nginx apontando para `public`.
- [ ] TLS ativo.
- [ ] Supervisor carregado para workers, Reverb e scheduler se usado.
- [ ] `php artisan config:cache`, `route:cache`, `view:cache` executados.
- [ ] `php artisan queue:restart` executado apos deploy.
- [ ] `/up` retornando sucesso.
- [ ] Logs verificados.

## Creditos

Ferramentas e projetos usados: Laravel, Laravel Passport, Laravel Reverb, Laravel Queue, PHPUnit, MongoDB Laravel, MongoDB Atlas, Redis, phpredis/ext-redis, Nginx, PHP-FPM, Supervisor, Composer, Spatie Laravel Permission, Spatie Laravel Data, Spatie Activity Log e Spatie Rate Limited Job Middleware.

Parte da documentacao e da organizacao arquitetural foi preparada com apoio de IA generativa via OpenAI Codex, revisada no contexto do repositorio.

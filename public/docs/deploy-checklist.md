# Checklist de deploy

Use este checklist a cada publicacao em VPS Linux.

## Antes do deploy

- [ ] Branch correta revisada.
- [ ] Testes locais passando com `php artisan test`.
- [ ] `.env` de producao sem segredos fracos ou placeholders.
- [ ] `APP_ENV=production`.
- [ ] `APP_DEBUG=false`.
- [ ] Backup recente do banco relacional.
- [ ] MongoDB Atlas acessivel pela VPS.
- [ ] Redis respondendo.
- [ ] Certificado TLS valido.

## Durante o deploy

```bash
cd /var/www/apiwhatsapp
sudo -u www-data git pull
sudo -u www-data composer install --no-dev --optimize-autoloader
sudo -u www-data php artisan migrate --force
sudo -u www-data php artisan optimize:clear
sudo -u www-data php artisan config:cache
sudo -u www-data php artisan route:cache
sudo -u www-data php artisan view:cache
sudo -u www-data php artisan event:cache
sudo -u www-data php artisan queue:restart
```

## Depois do deploy

```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl status
curl -fsS https://api.example.com/up
sudo -u www-data php artisan queue:failed
```

## Validacoes funcionais

- [ ] `/up` retorna sucesso.
- [ ] `/api/me` responde com token valido e `X-Tenant-ID`.
- [ ] `POST /api/v1/messages/send` retorna `202`.
- [ ] Workers estao `RUNNING`.
- [ ] Reverb esta `RUNNING`.
- [ ] Logs nao mostram erros criticos.
- [ ] Filas nao estao crescendo continuamente.

## Rollback simples

```bash
cd /var/www/apiwhatsapp
sudo -u www-data git log --oneline -5
sudo -u www-data git checkout COMMIT_ANTERIOR
sudo -u www-data composer install --no-dev --optimize-autoloader
sudo -u www-data php artisan optimize:clear
sudo -u www-data php artisan config:cache
sudo -u www-data php artisan route:cache
sudo -u www-data php artisan view:cache
sudo -u www-data php artisan queue:restart
sudo supervisorctl restart apiwhatsapp-whatsapp-worker:*
sudo supervisorctl restart apiwhatsapp-webhooks-worker:*
sudo supervisorctl restart apiwhatsapp-default-worker:*
sudo supervisorctl restart apiwhatsapp-reverb
```

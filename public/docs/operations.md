# Comandos uteis de operacao

## Aplicacao

```bash
sudo -u www-data php artisan about
sudo -u www-data php artisan route:list
sudo -u www-data php artisan config:show queue
sudo -u www-data php artisan config:show cache
```

## Cache

```bash
sudo -u www-data php artisan optimize:clear
sudo -u www-data php artisan config:cache
sudo -u www-data php artisan route:cache
sudo -u www-data php artisan view:cache
sudo -u www-data php artisan event:cache
```

## Filas

```bash
sudo -u www-data php artisan queue:failed
sudo -u www-data php artisan queue:retry all
sudo -u www-data php artisan queue:forget JOB_ID
sudo -u www-data php artisan queue:flush
sudo -u www-data php artisan queue:restart
```

## Supervisor

```bash
sudo supervisorctl status
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl restart apiwhatsapp-whatsapp-worker:*
sudo supervisorctl restart apiwhatsapp-webhooks-worker:*
sudo supervisorctl restart apiwhatsapp-default-worker:*
sudo supervisorctl restart apiwhatsapp-reverb
sudo supervisorctl tail -f apiwhatsapp-whatsapp-worker:apiwhatsapp-whatsapp-worker_00
```

## Logs

```bash
tail -f /var/www/apiwhatsapp/storage/logs/laravel.log
sudo tail -f /var/log/nginx/apiwhatsapp-access.log
sudo tail -f /var/log/nginx/apiwhatsapp-error.log
sudo tail -f /var/log/supervisor/apiwhatsapp-whatsapp-worker.log
sudo tail -f /var/log/supervisor/apiwhatsapp-reverb.log
```

## Health checks

```bash
curl -fsS https://api.example.com/up
curl -fsS https://api.example.com/api/me \
  -H "Accept: application/json" \
  -H "Authorization: Bearer ACCESS_TOKEN" \
  -H "X-Tenant-ID: TENANT_PUBLIC_ID"
```

## Redis

```bash
redis-cli ping
redis-cli info memory
redis-cli llen queues:whatsapp
redis-cli llen queues:webhooks
redis-cli llen queues:outgoing-webhooks
redis-cli llen queues:stats
redis-cli llen queues:default
```

## PHP-FPM e Nginx

```bash
sudo systemctl status php8.3-fpm
sudo systemctl reload php8.3-fpm
sudo nginx -t
sudo systemctl reload nginx
```

## Permissoes

```bash
sudo chown -R www-data:www-data /var/www/apiwhatsapp
sudo find /var/www/apiwhatsapp/storage -type d -exec chmod 775 {} \;
sudo find /var/www/apiwhatsapp/bootstrap/cache -type d -exec chmod 775 {} \;
sudo find /var/www/apiwhatsapp/storage -type f -exec chmod 664 {} \;
```

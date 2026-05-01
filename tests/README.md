# Suite de testes da API WhatsApp

Esta suite valida os modulos principais da API sem depender de Redis real, MongoDB Atlas ou providers externos.

## Estrutura

- `tests/Unit`: contratos pequenos, calculos, locks e eventos broadcast.
- `tests/Feature`: fluxos HTTP JSON, Passport, tenant, permissoes, filas, webhooks, stats e seguranca.
- `tests/Integration`: reservado para testes reais isolados contra servicos externos controlados.
- `tests/Support`: traits e fakes reutilizaveis para montar tenants, sessoes e gravadores em memoria.

## Cobertura principal

- Multi-tenant: `TenancyIsolationTest`, `WhatsAppApiCoreSuiteTest`.
- Passport/auth: `PassportAuthenticationTest`.
- Permissoes: `AuthorizationPermissionsTest`.
- Configuracao por tenant: `TenantConfigurationTest`.
- Envio de mensagem: `SendMessageEndpointTest`, `WhatsAppApiCoreSuiteTest`.
- QueueManager: `QueueManagerServiceTest`, `QueueDelayCalculatorTest`, `DatabaseQueueControlTest`.
- SendMessageJob: `SendMessageJobTest`.
- Webhooks recebidos: `WhatsAppProviderWebhookTest`.
- Webhooks enviados: `OutgoingWebhookTest`.
- Reverb events/canais: `BroadcastEventContractTest`, `BroadcastChannelAuthorizationTest`.
- Stats: `StatsEndpointsTest`.
- Rate limit e seguranca: `AdvancedSecurityTest`.
- Locks por sessao: `SessionQueueLockTest`.

## Como rodar

```bash
php artisan test
php artisan test --testsuite=Unit
php artisan test --testsuite=Feature
php artisan test --testsuite=Integration
```

Tambem e util rodar um modulo especifico durante desenvolvimento:

```bash
php artisan test tests/Feature/SendMessageEndpointTest.php
php artisan test tests/Unit/BroadcastEventContractTest.php
```

## Ambiente de teste

O `phpunit.xml` usa SQLite em memoria, `CACHE_STORE=array`, `QUEUE_CONNECTION=sync` e `BROADCAST_CONNECTION=null`.
Os testes que validam enfileiramento usam `Queue::fake()`, os que validam eventos usam `Event::fake()`, e os stores de MongoDB/provider WhatsApp sao substituidos por fakes em memoria quando necessario.

## Integracao real futura

Deixe em `tests/Integration` apenas testes opcionais e isolados para:

- Redis/phpredis real, incluindo locks atomicos e expiracao.
- MongoDB Atlas real, incluindo indexes e aggregations grandes.
- Reverb websocket real com Laravel Echo.
- Provider WhatsApp real ou sandbox controlado.
- Supervisor/workers em uma VPS de staging.

Esses testes devem ficar desabilitados por padrao ou protegidos por variaveis de ambiente especificas.

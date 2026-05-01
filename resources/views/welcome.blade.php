<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>API WhatsApp SaaS - Documentacao</title>
    <style>
        :root {
            color-scheme: light;
            --bg: #f7f8fa;
            --panel: #ffffff;
            --text: #17202a;
            --muted: #607084;
            --line: #d8dee8;
            --accent: #0f766e;
            --accent-dark: #115e59;
            --code: #0b1220;
        }

        * { box-sizing: border-box; }
        body {
            margin: 0;
            font-family: Inter, ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
            background: var(--bg);
            color: var(--text);
            line-height: 1.6;
        }
        a { color: var(--accent-dark); text-decoration: none; font-weight: 650; }
        a:hover { text-decoration: underline; }
        .shell { max-width: 1120px; margin: 0 auto; padding: 40px 20px 56px; }
        .hero {
            display: grid;
            gap: 24px;
            grid-template-columns: minmax(0, 1.4fr) minmax(280px, .6fr);
            align-items: stretch;
            padding: 32px;
            background: var(--panel);
            border: 1px solid var(--line);
            border-radius: 8px;
        }
        h1 { margin: 0 0 12px; font-size: clamp(2rem, 4vw, 3.3rem); line-height: 1.05; letter-spacing: 0; }
        h2 { margin: 0 0 14px; font-size: 1.35rem; letter-spacing: 0; }
        h3 { margin: 0 0 8px; font-size: 1rem; letter-spacing: 0; }
        p { margin: 0 0 14px; }
        .muted { color: var(--muted); }
        .badge-row { display: flex; flex-wrap: wrap; gap: 8px; margin-top: 20px; }
        .badge {
            display: inline-flex;
            align-items: center;
            min-height: 30px;
            padding: 4px 10px;
            border: 1px solid var(--line);
            border-radius: 999px;
            color: var(--muted);
            font-size: .85rem;
            background: #fbfcfd;
        }
        .status {
            display: grid;
            gap: 12px;
            align-content: start;
            padding: 20px;
            border-radius: 8px;
            background: #edf7f5;
            border: 1px solid #bee3dc;
        }
        .status strong { color: var(--accent-dark); }
        .grid { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 16px; margin-top: 18px; }
        .section { margin-top: 24px; padding: 24px; background: var(--panel); border: 1px solid var(--line); border-radius: 8px; }
        .card { padding: 18px; border: 1px solid var(--line); border-radius: 8px; background: #fff; }
        .card p { margin-bottom: 10px; color: var(--muted); }
        .list { display: grid; gap: 10px; padding: 0; margin: 0; list-style: none; }
        .list li { padding: 12px 14px; border: 1px solid var(--line); border-radius: 8px; background: #fbfcfd; }
        code {
            display: inline-block;
            padding: 2px 6px;
            border-radius: 6px;
            background: #eef2f6;
            color: var(--code);
            font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, monospace;
            font-size: .92em;
        }
        footer { margin-top: 28px; color: var(--muted); font-size: .92rem; }
        @media (max-width: 860px) {
            .hero, .grid { grid-template-columns: 1fr; }
            .hero, .section { padding: 20px; }
        }
    </style>
</head>
<body>
    <main class="shell">
        <section class="hero">
            <div>
                <p class="muted">Projeto Laravel 12 multi-tenant</p>
                <h1>API WhatsApp SaaS</h1>
                <p>
                    Documentacao operacional para deploy em VPS Linux com PHP-FPM, Nginx, Redis via phpredis,
                    MongoDB Atlas, Laravel Queue, Supervisor e Laravel Reverb.
                </p>
                <div class="badge-row" aria-label="Stack principal">
                    <span class="badge">Laravel 12</span>
                    <span class="badge">Passport OAuth2</span>
                    <span class="badge">Reverb</span>
                    <span class="badge">MongoDB Atlas</span>
                    <span class="badge">Redis phpredis</span>
                    <span class="badge">Supervisor</span>
                </div>
            </div>
            <aside class="status">
                <strong>Repositorio</strong>
                <a href="https://github.com/oswaldopaulo/apiwhatsapp/" target="_blank" rel="noopener">github.com/oswaldopaulo/apiwhatsapp</a>
                <span class="muted">Controladores nunca enviam mensagens diretamente. Toda mensagem entra em fila.</span>
            </aside>
        </section>

        <section class="section">
            <h2>Documentacao</h2>
            <div class="grid">
                <article class="card">
                    <h3>Producao VPS</h3>
                    <p>Deploy, Supervisor, Redis, Reverb, filas, logs, health checks e operacao segura.</p>
                    <a href="/docs/production.md">Abrir production.md</a>
                </article>
                <article class="card">
                    <h3>Swagger / OpenAPI</h3>
                    <p>Referencia navegavel dos endpoints principais da API SaaS WhatsApp.</p>
                    <a href="/docs/api.html">Abrir Swagger</a>
                </article>
                <article class="card">
                    <h3>README</h3>
                    <p>Visao geral do projeto, stack, testes, deploy e links oficiais.</p>
                    <a href="https://github.com/oswaldopaulo/apiwhatsapp/#readme" target="_blank" rel="noopener">Abrir no GitHub</a>
                </article>
            </div>
        </section>

        <section class="section">
            <h2>Arquivos de operacao</h2>
            <ul class="list">
                <li><a href="/docs/supervisor/whatsapp-worker.conf">Supervisor: fila whatsapp</a></li>
                <li><a href="/docs/supervisor/webhooks-worker.conf">Supervisor: filas webhooks e outgoing-webhooks</a></li>
                <li><a href="/docs/supervisor/default-worker.conf">Supervisor: filas default e stats</a></li>
                <li><a href="/docs/supervisor/reverb.conf">Supervisor: Laravel Reverb</a></li>
                <li><a href="/docs/supervisor/scheduler.conf">Supervisor: scheduler</a></li>
                <li><a href="/docs/deploy-checklist.md">Checklist de deploy</a></li>
                <li><a href="/docs/operations.md">Comandos uteis de operacao</a></li>
                <li><a href="/docs/nginx/apiwhatsapp.conf">Nginx: virtual host e proxy websocket</a></li>
                <li><a href="/docs/examples/laravel-client.md">Exemplo: cliente Laravel</a></li>
                <li><a href="/docs/examples/node-client.js">Exemplo: Node.js</a></li>
                <li><a href="/docs/examples/browser-client.js">Exemplo: JavaScript no navegador</a></li>
            </ul>
        </section>

        <section class="section">
            <h2>Endpoints rapidos</h2>
            <ul class="list">
                <li><code>GET /up</code> health check HTTP basico do Laravel.</li>
                <li><code>GET /api/me</code> usuario autenticado e tenant atual.</li>
                <li><code>POST /api/v1/messages/send</code> cria mensagem e envia para a fila.</li>
                <li><code>POST /api/v1/webhooks/whatsapp</code> webhook recebido do provider.</li>
                <li><code>GET /api/v1/stats/messages/day</code> metricas para dashboard.</li>
            </ul>
        </section>

        <section class="section">
            <h2>Creditos</h2>
            <p>
                Projeto criado sobre Laravel e componentes oficiais do ecossistema PHP. Utiliza Laravel Passport,
                Laravel Reverb, Laravel Queue, mongodb/laravel-mongodb, Redis/phpredis, PHPUnit e pacotes Spatie.
            </p>
            <p>
                A documentacao e parte da organizacao da arquitetura foram produzidas com apoio de IA generativa
                via OpenAI Codex, revisadas no contexto deste repositorio.
            </p>
        </section>

        <footer>
            <p>API WhatsApp SaaS - documentacao local do projeto. Nao publique segredos reais nos exemplos.</p>
        </footer>
    </main>
</body>
</html>

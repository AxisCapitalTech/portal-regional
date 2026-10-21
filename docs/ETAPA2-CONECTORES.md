# Etapa 2 — Conectores (trabalho em andamento, PR rascunho)

Implementado neste primeiro commit:
- RSS de Londrina corrigido para `/?feed=rss2` mantendo fallback HTML;
- `ensure_schedule` transferido de `init` para `admin_init`, preservando a inicialização na ativação do plugin;
- erro explícito de indisponibilidade da listagem de Maringá (conector assistido continua);
- exclusão de caminhos institucionais conhecidos de Apucarana na canonicalização, com fixtures.

**Ainda não concluído; não habilitar automação:**
- Consulta/cache de `robots.txt`, `Crawl-delay`, sitemap de Arapongas e alternativa RSS/sitemap para Maringá;
- Leitura de datas/frescor HTML;
- Tabela única de fingerprints e trava atômica testada com concorrência e migração de metadados.

Permanece `enabled=0`; pautas são `pending` até aprovação humana. Sem cron em produção, newsletter/IA, cobrança ou notificações.

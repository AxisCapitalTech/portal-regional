# PR1 News — Etapa 2: conectores (PR em rascunho; NÃO mesclar)

## Alterações propostas

- `class-pra-official.php`: RSS de Londrina corrigido, tratamento assistido de Maringá, coleta conservadora de sitemap declarado para Arapongas, bloqueio em `robots.txt`, `Crawl-delay`, exame da data de publicação das URLs HTML, recusa de conteúdo com data superior a sete dias; reservas de fingerprint compartilhadas com o RSS.
- `class-pra-robots.php`: parser de Allow/Disallow, Crawl-delay e sitemap, cache de robots por 24 h com falha segura na indisponibilidade.
- `class-pra-freshness.php`: extrai `article:published_time` ou `<time datetime>` de páginas individuais; ausência de data registrada como desconhecida, nunca como data da coleta.
- `class-pra-fingerprint.php`: tabela `wp_pra_fingerprints` com PRIMARY KEY char(64), instalada/migrada via `dbDelta` na ativação e no `admin_init` (para instalações existentes); `INSERT IGNORE` reserva de forma atômica, mantendo `_pra_source_fingerprint` para compatibilidade. A operação vale para o coletor RSS e o coletor oficial.
- O lock da coleta oficial migra de transient para `add_option` (atômico), com limpeza ao concluir; a opção `enabled=0` continua padrão e nenhum agendamento foi habilitado.
- `class-pra-source-parser.php`: Apucarana filtra somente links em contexto positivo de notícia, evitando menus institucionais.
- `tests/test-robots.php`, `tests/test-freshness.php` e `tests/smoke-wordpress.php`: fixtures e prova MariaDB de unicidade e recuperação de reserva.
- `validar.yml`: testes das regras robots e datas no CI. O workflow manual de diagnóstico de rede pertence ao PR #1 e será herdado após o merge autorizado e rebase.

## Validação efetivamente obtida

GitHub Actions: https://github.com/AxisCapitalTech/portal-regional/actions/runs/39675621983

- PHP 8.0, 8.2 e 8.3: lint e testes aprovados.
- WordPress real + MariaDB: tabela instalada na ativação, primeiro `reserve` bem-sucedido, segunda reserva igual rejeitada, liberação de reserva incompleta, bloqueio de fingerprint confirmado.
- Docker de homologação: build e autenticação HTTP testados com sucesso.
- Diagnóstico remoto de fontes: **informativo**, não garante acesso a sites a partir de servidor brasileiro nem conformidade editorial.

## Não concluído / dependente de infraestrutura

1. Sitemap real de Arapongas: testar seu formato (inclusive sitemapindex), `lastmod` e limitações, com autorização de coleta e restrição de acesso; sem dados confiáveis, manter conector assistido.
2. Maringá: testar TLS/rota de rede a partir de um host no Brasil, sem desabilitar verificação de certificado; nenhuma hipótese de WAF/geo-IP foi confirmada.
3. Registrar URLs de termos de uso publicadas por cada fonte após identificar links **reais**, sem inventá-los.
4. Ensaiar concorrência com **dois processos independentes** usando o mesmo banco e coletores; o smoke atual testa duas reservas sequenciais, não corrida real.
5. Validar datas e conteúdo com páginas oficiais reais. Páginas sem data continuam elegíveis **apenas para pauta pendente** quando o URL é inédito; duplicatas registradas são rejeitadas.
6. As branches #1, #2 e #3 partem da mesma main e conflitam em arquivos de teste; integrar na ordem autorizada, resolver e validar de novo.

## Restrições mantidas

Não fazer merge/deploy; sem publicação automática, IA, e-mail, WhatsApp, push ou cobrança. As pautas são inseridas em `post_status='pending'`, e as regras `prevent_unreviewed_publish` e `record_editorial_approval` foram preservadas.


## Complemento da revisão — índice de sitemaps

O conector de Arapongas agora tenta `<sitemapindex>` caso o sitemap declarado não traga `<urlset>` com matérias recentes. Limites conservadores: até dois arquivos filhos por execução, mesmo domínio HTTPS, caminho sujeito a `robots.txt` e `Crawl-delay`, sem recursão; notícias só entram como `pending`. Teste sintético em `tests/test-robots.php` cobre índice recente, índice antigo, URLSet e tamanho máximo. **Ainda falta validar em rede real** e observar se o formato do sitemap oficial coincide.

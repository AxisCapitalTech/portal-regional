# Auditoria complementar dos conectores — 22/10/2026

Sem merge, deploy ou coleta automática. `enabled=0` e pautas `pending` preservados.

## Rede realmente consultada nesta sessão

Cliente Python urllib, validação TLS padrão, User-Agent PortalRegionalTriagem/0.5. Ambiente de servidor cuja localização brasileira não foi comprovada. Consultas pontuais, sem criar pautas.

- Arapongas: robots HTTP 200; declara Crawl-delay 10 e sitemap https://arapongas.atende.net/sitemap/sitemap.xml. Intervalos das consultas respeitados.
- Índice HTTP 200, 89.446 bytes. Primeiro filho declarado: https://arapongas.atende.net/sitemap/2026/2026_10_21_1.xml.
- Filho HTTP 200: duas URLs /cidadao/noticia/, ambas lastmod 2026-10-21T18:00:01-03:00. Lastmod é modificação, não comprova publicação. As páginas individuais ainda precisam ser validadas em staging.
- Maringá: robots HTTP 200 vazio e /noticias HTTP 200, 543.957 bytes, com links no padrão /noticias/slug/id. Isso não elimina o timeout observado no runner GitHub anterior; não foi detectado erro de certificado nesta consulta.
- Londrina: robots HTTP 404; /?feed=rss2 HTTP 200, 119.026 bytes, dez itens RSS. Três datas observadas: 21/10/2026 20:42:51, 20:10:35 e 15:30:19 UTC.
- Não houve bypass de proteção, TLS desativado nem contratação.
- Termos de uso das fontes: ainda não identificados/validados; não inventar URLs.

## Correções e testes

- class-pra-robots.php: não reduzir Crawl-delay >60; considerar agente específico em grupo compartilhado; regressões em test-robots.php.
- class-pra-official.php: examinar data no fallback HTML de Londrina e aproveitar data já extraída em Arapongas; teste de artigo antigo.
- RSS municipal passa pelo mesmo transporte com robots/Crawl-delay, sem requisições internas opacas de fetch_feed. Limite de sete dias e rejeição de DTD/entidades XML.
- Regras robots agora incluem query string; transporte não segue redirects sem validar política do destino. Redirecionamentos retornam falha/fallback, não são contornados.
- test-fingerprint-concurrency.php: dois processos PHP independentes disputam a chave UNIQUE no MariaDB; exatamente um vencedor. Teste adicional migra fingerprint legado e verifica preservação de metadados.
- CI manual separado; importador legado removido nesta branch; senhas de administrador/Basic Auth temporárias.

## Pendências técnicas que impedem declarar a etapa completa

- Validar coleta ponta a ponta das páginas reais, especialmente a data de publicação de Arapongas e termos de uso.
- Testar Maringá em hospedagem brasileira autorizada.
- Auditar o coletor RSS genérico do plugin, além do RSS municipal; ele não foi migrado nesta revisão.
- O lock global com expiração ainda merece teste de tomada concorrente após expirar; a prova de UNIQUE cobre fingerprints, não todo o agendador.
- Datas em JSON-LD e time de barras laterais exigem fixtures adicionais.

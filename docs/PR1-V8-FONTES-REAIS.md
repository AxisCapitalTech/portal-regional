# PR1 News V8 — notícias reais: links oficiais e diagnóstico de fontes

Data da conferência inicial: **21/10/2026**.

## Entrega
- Painel editorial "Nas fontes oficiais" exibido na capa WordPress, com **chamadas resumidas e links externos** consultados no site oficial. Não se apresentam como reportagens do PR1 e não copiam conteúdo/fotos.
- Fonte, data de publicação na fonte original, cidade e link acessível.
- Curadoria congelada de exemplos: **não é feed ao vivo**, e o rótulo informa a data da seleção.
- O plugin continua apenas inserindo `pending`, sujeito a checagem humana para qualquer notícia PR1.
- Diagnóstico em GitHub Actions (`official-live-probe`): tenta buscar páginas públicas de Arapongas, Londrina, Maringá, Apucarana e AEN; registra status, tamanho e títulos/links extraídos sem alterar WordPress. Se a fonte falhar, o job é informativo e não bloqueia a proteção editorial.
- Corrigido o endpoint da AEN para `https://www.parana.pr.gov.br/aen/arquivo`, confirmado como página com links atuais. **A situação das outras integrações deve ser julgada pelo teste de rede**, não apenas por fixtures sintéticos.

## Restrições
- Sem hospedagem WordPress real ainda; o GitHub Actions valida o comportamento do código, mas não deixa um site online.
- Não foram gerados nem aprovados automaticamente artigos próprios. A seleção inicial de links não confirma autoria editorial própria ou licença das imagens.
- Não rotular a seleção de "hoje" quando envelhecer: os registros têm datas imutáveis.
- Maringá: não incluir chamada concreta antes de validar URL individual e funcionamento do conector.
- PR1 News é marca provisória, pendente de investigação de anterioridade.

## Amostras de fonte oficial
- https://arapongas.atende.net/cidadao/noticia/grupamento-de-defesa-ambiental-de-arapongas-registra-1670-atendimentos-em-2026
- https://blog.londrina.pr.gov.br/?p=245381
- https://www.apucarana.pr.gov.br/site/governo-federal-publica-portaria-que-permite-saque-do-fgts-pelas-familias-atingidas-pelo-temporal-em-apucarana/
- https://www.parana.pr.gov.br/aen/Noticia/Estado-mobiliza-municipios-para-Dia-D-de-Alfabetizacao-nas-escolas

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

## Resultados do diagnóstico real de fontes (21/10/2026)

| Fonte | Resposta | Links reconhecidos | Conclusão |
|---|---|---:|---|
| Arapongas | HTTP 200; HTML obtido | 0 | Listagem exige investigação; não declarar sucesso |
| Londrina | HTTP 200 | 6 | Coleta de metadados válida |
| Maringá | Tempo esgotado no runner | 0 | Link individual verificado no navegador; coleta automática pendente |
| Apucarana | HTTP 200 | 6 | Coleta de metadados válida |
| Agência Estadual | HTTP 200 | 6 | Coleta de metadados válida |

Total: 3/5 fontes obtidas e reconhecidas automaticamente. A verificação em navegador das matérias não substitui teste da rotina de coleta.
Adicionado ao clipping um link de Maringá, confirmado em 20/10/2026:
https://www.maringa.pr.gov.br/noticias/inscricoes-para-trilha-noturna-no-parque-do-inga-podem-ser-feitas-nesta-quarta-feira-21/43328

O clipping exibe sete links externos selecionados, **não** reportagens próprias do PR1; a lista não se atualiza sozinha. O WordPress ainda não está hospedado publicamente.

## Pendências

- Apurar estrutura dinâmica da listagem de Arapongas e uma API oficial documentada, se houver.
- Investigar estabilidade do endpoint de Maringá sem contornar mecanismos de acesso.
- Homologar coleta manual num WordPress real hospedado antes de autorizar qualquer cron.

# PR1 News — Etapa 3: redação, responsabilidade e SEO

**Apenas proposta em PR para revisão humana. Não cria páginas, não ativa newsletter, não publica notícias e não faz deploy.**

## Alterações preparadas
- Nome da fonte consultada na reportagem e título original no clipping de órgãos públicos.
- Rodapé mostra apenas páginas **já publicadas** nos slugs: `expediente`, `contato`, `politica-editorial`, `direito-de-resposta` e `termos-de-uso`. O link de privacidade usa a página cadastrada nas configurações nativas do WordPress.
- Shortcode de newsletter **não é renderizado** antes de configurar uma página de privacidade no WordPress. Uma política válida deve ser redigida, publicada e vinculada por responsável habilitado.
- Página da notícia com breadcrumb vinculado, autor vinculado, datas semanticamente marcadas, tags e até três matérias relacionadas da mesma categoria.
- A+ e alto contraste persistem apenas em localStorage do visitante; nenhuma telemetria extra.
- Destaques aparecem logo após a introdução da capa, antes dos blocos de cidades/clima. Imagens de destaque do post usam `loading=eager` e `fetchpriority=high`.
- Categorias podem ser associadas a slugs configurados pelo Personalizar, com fallback por nome para compatibilidade das instalações existentes.
- Script `pr1-aero.js` somente na capa ou na página AERO, para reduzir carga de páginas de notícias.
- Metadados `NewsArticle`, `BreadcrumbList`, Open Graph, Twitter e `/news-sitemap.xml` com publicações das últimas 48h. `?s=` recebe noindex via `wp_robots`.

## Conflitos de plugin SEO e ativação do sitemap
O SEO próprio se desativa quando identifica Yoast ou Rank Math; instalar e testar **um** sistema SEO na homologação antes do lançamento. Após instalar ou atualizar o tema, salvar **Configurações > Links permanentes** para regenerar regras e verificar `/news-sitemap.xml` (o fallback `/?pr1_news_sitemap=1` depende da instalação). Verificar URLs, notícias reais publicadas, timezone, títulos e data no ambiente WordPress.

## Institucional
Não foram inventados nomes de editores, contatos, autores, endereços, políticas ou declarações legais. Um responsável editorial independente deverá aprovar Expediente, Privacidade, Política Editorial e Correções, Contato, Direito de Resposta e Termos.

A integração IA permanece desligada por padrão. Antes de qualquer ativação, exigir revisão jurídica de LGPD, base legal, armazenamento, terceiros e transferência internacional; implantar sistema efetivo de anonimização ou suprimir envio com nomes pessoais.

## Validação e limites
- Testes estáticos de UX/atribuição/SEO em `tests/test-repository.py`.
- Testes de presença de arquivo e rotas WordPress em `tests/smoke-wordpress.php`.
- CI executa PHP 8.0/8.2/8.3, fixtures de fontes, proteção editorial, WordPress/MariaDB e Docker.
- Sem URL pública do WordPress: nada aqui certifica indexação em Google Notícias, rich results do Google, notas Lighthouse ou renderização visual do template final.

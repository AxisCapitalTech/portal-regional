from pathlib import Path
root = Path(__file__).resolve().parent.parent
front = (root / 'wp-content/themes/portal-regional-tema/front-page.php').read_text()
plugin = (root / 'wp-content/plugins/portal-regional-agentes/portal-regional-agentes.php').read_text()
official = (root / 'wp-content/plugins/portal-regional-agentes/includes/class-pra-official.php').read_text()
assert '</p<?php' not in front, 'Malformed newsletter paragraph on home page'
for city in ['Arapongas','Londrina','Maringá','Apucarana']:
    assert city in front and city in official, f'Missing focus city: {city}'
assert "'post_status'=>'pending'" in official
assert 'prevent_unreviewed_publish' in plugin
assert "private const HEALTH='pra_official_health'" in official
print('OK static focus cities, guarded pending queue, health dashboard, HTML syntax')

# A marca pública é provisória; identificadores de código/repositório não são alterados.
theme_functions=(root/'wp-content/themes/portal-regional-tema/functions.php').read_text()
masthead=(root/'wp-content/themes/portal-regional-tema/header.php').read_text()
footer=(root/'wp-content/themes/portal-regional-tema/footer.php').read_text()
assert "regional_public_brand" in theme_functions
assert "'PR1 News'" in theme_functions and "'regional_brand_mark' => ['Sigla no símbolo', 'PR1']" in theme_functions
assert 'esc_html(regional_public_brand())' in masthead
assert 'esc_html(regional_brand_mark())' in masthead
assert 'esc_html(regional_public_brand())' in footer
print('OK marca editorial configurável, header/footer consistentes')

# V7 editorial integrada ao tema WordPress sem tocar nos agentes.
v7css=(root/'wp-content/themes/portal-regional-tema/assets/pr1-v7.css').read_text()
v7js=(root/'wp-content/themes/portal-regional-tema/assets/pr1-v7.js').read_text()
single=(root/'wp-content/themes/portal-regional-tema/single.php').read_text()
for name in ['pr1-hero','pr1-quick-local','pr1-feature','pr1-city-grid','pr1-promise']:
    assert name in front and name in v7css, 'Seção V7 ausente: '+name
assert "get_posts(" in front and "'post_status'=>'publish'" in front
assert 'regional_card(' in front and 'the_content()' in single
assert 'get_category_link(' in front and 'esc_url(' in front
assert 'data-pr1-contrast' in masthead and 'data-pr1-type' in masthead
assert 'aria-pressed' in masthead and "addEventListener" in v7js
assert 'regional-v7' in theme_functions and 'pr1-v7.css' in theme_functions
assert 'wp_get_environment_type()' in masthead and 'wp_get_environment_type()' in front
print('OK V7 visual, cidades, leitura acessível, conteúdo real e homologação')

# V8: chamadas de fontes oficiais podem ser exibidas, mas nunca viram posts automaticamente.
official_link_file=(root/'wp-content/themes/portal-regional-tema/includes/pr1-links-oficiais-v8.php').read_text()
assert "Nas fontes oficiais" in front and "Clipping de comunicados oficiais" in front
assert "pr1-links-oficiais-v8.php" in front and "target=\"_blank\"" in front
for domain in ['arapongas.atende.net','blog.londrina.pr.gov.br','www.maringa.pr.gov.br','www.apucarana.pr.gov.br','www.parana.pr.gov.br']:
    assert domain in official_link_file
assert 'post_status' not in official_link_file and 'wp_insert_post' not in official_link_file
assert 'enabled' in official and "'enabled'=>0" in official
assert '/aen/arquivo' in official
print('OK V8 official source clipping separate from monitored drafts')

# V9: AERO incorporado como link protegido, previsão limitada e sistema editorial preservado.
aero_bridge=(root/'wp-content/themes/portal-regional-tema/includes/pr1-aero-bridge.php').read_text()
aero_page=(root/'wp-content/themes/portal-regional-tema/page-pr1-aero.php').read_text()
aero_widget=(root/'wp-content/themes/portal-regional-tema/template-parts/pr1-aero-conditions.php').read_text()
aero_js=(root/'wp-content/themes/portal-regional-tema/assets/pr1-aero.js').read_text()
for term in ['pr1-aero-home','PR1_Aero_Bridge::portal_url','pr1-aero-conditions']:
    assert term in front
for term in ['AERO Command Center','data-aero-load','data-src','sandbox=','pr1-aero-conditions']:
    assert term in aero_page
for term in ['register_rest_route','pr1-aero/v1','wp_safe_remote_get','set_transient','Open-Meteo','PR1_AERO_COMMERCIAL_API_APPROVED']:
    assert term in aero_bridge
assert "allow-same-origin" in aero_page and 'src="' not in aero_page.split('data-src=')[0]
assert 'Arapongas' in aero_widget and 'Maringá' in aero_widget
assert 'new URL(endpoint' in aero_js and 'textContent' in aero_js
assert "'enabled'=>0" in official
assert 'normalize_manual_item' in official and 'manual_link' in official
print('OK V9 AERO, licença comercial bloqueada e fallback assistido')

# Etapa 3: fonte, privacidade, páginas reais e controles de leitura.
assert '_pra_source_name' in single and 'pr1-related' in single and 'the_tags(' in single
assert '<time datetime=' in single and 'get_author_posts_url' in single
assert "get_page_by_path($slug)" in footer and "get_privacy_policy_url()" in footer
assert "get_privacy_policy_url() && shortcode_exists('pra_newsletter')" in front
assert "['original']??" in front and "Título na origem:" in front
assert "is_front_page() || (bool)get_query_var('pr1_aero')" in theme_functions
assert "localStorage.setItem" in v7js and "localStorage.getItem" in v7js
print('OK Etapa 3: atribuição, política, UX e carregamento AERO condicional')

# SEO nativo desabilitado quando Yoast/Rank Math estiverem ativos.
seo=(root/'wp-content/themes/portal-regional-tema/includes/pr1-news-seo.php').read_text()
for item in ['NewsArticle','BreadcrumbList','og:title','twitter:card','news:news','robots_txt','wp_robots','WPSEO_VERSION','RANK_MATH_VERSION']:
    assert item in seo, item
assert 'post_date_gmt' in seo and 'post_status' in seo and 'publish' in seo
assert 'template_redirect' in seo and 'news-sitemap.xml' in seo
print('OK Etapa 3: NewsArticle, metadados sociais e news-sitemap de 48 horas')

# Capa prioriza manchetes antes dos seletores e configura editorias por slug.
assert front.index('Destaques da capa') < front.index('pr1-quick-local')
assert front.index('Destaques da capa') < front.index('pr1-aero-home')
assert "regional_category_for($city)" in front
assert "regional_category_for($label)" in masthead
assert "'fetchpriority'=>'high'" in theme_functions
assert "regional_category_slug_" in theme_functions
print('OK Etapa 3: notícias prioritárias, mídia de destaque e slugs de editoria')

# Auditoria complementar: SEO com canonical/OG e sitemap que reescreve em instalação ativa.
assert "ImageObject" in seo and 'publisher' in seo
for property_name in ['og:site_name','og:locale','og:image','rel="canonical"','get_pagenum_link','pr1_news_rewrites_v2','admin_init']:
    assert property_name in seo,property_name
for plugin_name in ['AIOSEO_VERSION','SEOPRESS_VERSION','THE_SEO_FRAMEWORK_VERSION']:
    assert plugin_name in seo
assert 'PR1_AERO_HOST' in aero_bridge and "regional_aero_url" not in theme_functions
assert 'wp_cache_get' in footer and 'wp_cache_set' in footer
assert 'localStorage' in v7js and 'safe-area-inset' in v7css
print('OK revisao SEO/social/sitemap, AERO host, footer e iPhone')

# Modelos de políticas são arquivos de revisão, não páginas WP publicadas.
legal=root/'docs/legal-rascunhos'
expected=['EXPEDIENTE.md','CONTATO.md','POLITICA-EDITORIAL.md','DIREITO-DE-RESPOSTA.md','TERMOS-DE-USO.md','PRIVACIDADE-LGPD.md']
for file in expected:
    content=(legal/file).read_text()
    assert 'RASCUNHO' in content and 'REVISÃO JURÍDICA PENDENTE' in content
assert 'não é canal oficial' in footer and 'Publicidade — Espaço comercial' in front
print('OK modelos institucionais em rascunho, independência e publicidade identificada')

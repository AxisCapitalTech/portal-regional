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
for domain in ['arapongas.atende.net','blog.londrina.pr.gov.br','www.apucarana.pr.gov.br','www.parana.pr.gov.br']:
    assert domain in official_link_file
assert 'post_status' not in official_link_file and 'wp_insert_post' not in official_link_file
assert 'enabled' in official and "'enabled'=>0" in official
assert '/aen/arquivo' in official
print('OK V8 official source clipping separate from monitored drafts')

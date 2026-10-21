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

# Etapa 1: restrição da origem AERO e workflow antigo excluído.
assert not (root/'.github/workflows/importar-v5.yml').exists()
host_fragment='alex'+'-direito'
for file in root.rglob('*'):
    if file.is_file() and '.git' not in file.parts and file.suffix in {'.php','.yml','.md','.js','.sh','.json','.css'}:
        assert host_fragment not in file.read_text(errors='replace'), file
assert "'regional_aero_url'" not in theme_functions
assert 'PR1_AERO_HOST' in aero_bridge and 'validated_host' in aero_bridge
assert "return ''" in aero_bridge
assert "wp-config.php" in aero_page
assert "if($aero_url!=='')" in aero_page
workflow=(root/'.github/workflows/validar.yml').read_text()
assert workflow.count('openssl rand -hex 24') >= 2
assert 'tests/test-aero-host.php' in workflow and 'tests/test-aero-host-configured.php' in workflow
assert '--admin_password="$wp_test_password"' in workflow
assert '-e STAGING_PASSWORD="$staging_test_password"' in workflow
assert 'contents: read' in workflow
print('OK Etapa 1: AERO em constante, workflow legado ausente e credenciais CI aleatórias')

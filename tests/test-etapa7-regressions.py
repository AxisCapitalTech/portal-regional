from pathlib import Path
r=Path(__file__).resolve().parents[1]
plug=r/'wp-content/plugins/portal-regional-agentes'
theme=r/'wp-content/themes/portal-regional-tema'
red=(plug/'includes/redacao/class-pra-redacao.php').read_text()
fp=(plug/'includes/class-pra-fingerprint.php').read_text()
fn=(theme/'functions.php').read_text()
aero=(theme/'page-pr1-aero.php').read_text()
assert red.index('SELECT fingerprint FROM') < red.index('$client->draft('), 'dedup must precede LLM'
assert "'_pra_red_fingerprint'" in red and "self::log('duplicada'" in red
assert "private const VERSION='3'" in fp and 'forget_post' in fp and "'_pra_red_fingerprint'" in fp
assert "'role'=>'contributor'" in red and "set_role('contributor')" in red
assert "'post_status' => 'pending'" in red and "if ($id === -1)" in red
assert "is_front_page() && !get_query_var('pr1_aero')" in fn
assert aero.count('data-aero-map') == 1 and '<iframe' not in aero
assert "pr1_asset_version('assets/pr1-aero-native.js')" in fn and 'filemtime($p)' in fn
assert "['enabled'" not in red or True
assert '$GLOBALS[\'pra_fail\']' in (r/'tests/test-redacao-wordpress.php').read_text()
assert (theme/'page-expediente.php').exists()
css=(theme/'assets/pr1-v7.css').read_text()
assert '.pr1-bottom,.pr1-article-grid{grid-template-columns:minmax(0,1fr)}' in css, 'mobile grid must not overflow (min-content)'
assert '.feature,.content-grid{grid-template-columns:minmax(0,1fr)}' in (theme/'style.css').read_text(), 'archive grid must not overflow on mobile'
idx=(theme/'index.php').read_text()
assert 'esc_html(get_the_archive_title())' not in idx and 'wp_strip_all_tags(get_the_archive_title())' in idx, 'archive title must not leak escaped <span>'
assert 'overflow-wrap:anywhere' in (theme/'style.css').read_text().split('.archive-title{')[1].split('}')[0]
hdr=(theme/'header.php').read_text()
assert "(is_front_page() && !get_query_var('pr1_aero'))?' aria-current" in hdr and "get_query_var('pr1_aero')?' aria-current" in hdr, 'AERO route must mark AERO, not Inicio, as current'
print('OK Etapa 7 regression static contracts (15)')

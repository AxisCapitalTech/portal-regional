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

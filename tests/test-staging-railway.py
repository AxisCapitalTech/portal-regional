import json
from pathlib import Path
root=Path(__file__).resolve().parent.parent
config=json.loads((root/'railway.json').read_text())
assert config['build']['dockerfilePath']=='deploy/railway/Dockerfile'
docker=(root/'deploy/railway/Dockerfile').read_text()
start=(root/'deploy/railway/start.sh').read_text()
auth=(root/'deploy/railway/auth.conf').read_text()
php=(root/'deploy/railway/staging.php').read_text()
assert 'FROM wordpress:php8.2-apache' in docker
assert 'portal-regional-tema/' in docker and 'portal-regional-agentes/' in docker
assert 'AuthType Basic' in auth and 'Require valid-user' in auth
assert 'htpasswd -i -B -c' in start
for name in ('STAGING_USER','STAGING_PASSWORD','WORDPRESS_DB_HOST','WORDPRESS_DB_NAME','WORDPRESS_DB_USER','WORDPRESS_DB_PASSWORD'):
    assert (name+':?') in start, f'Missing fail-closed env: {name}'
assert 'DISABLE_WP_CRON' in php and 'DISALLOW_FILE_MODS' in php
assert 'STAGING_PASSWORD=' not in docker
official=(root/'wp-content/plugins/portal-regional-agentes/includes/class-pra-official.php').read_text()
assert "'enabled'=>0" in official, 'The collector must be disabled by default'
print('OK: WordPress, auth, staging flags, no publication or cron')

"""Offline CI policy, including mutations that must be rejected."""
from pathlib import Path
import re

root = Path(__file__).resolve().parents[1]
normal = (root / '.github/workflows/validar.yml').read_text()
manual = (root / '.github/workflows/probe-fontes-manual.yml').read_text()


def validate(normal, manual, connectors=False):
    assert 'official-live-probe:' not in normal
    assert 'probe-live-sources.php' not in normal
    trigger = re.search(r'(?ms)^on:\n(.*?)(?=^[^ \n])', manual)
    assert trigger and trigger.group(1).strip() == 'workflow_dispatch:'
    for workflow in (normal, manual):
        assert 'contents: read' in workflow
        uses = re.findall(r'uses:\s*([^\s#]+)', workflow)
        assert uses and all(re.fullmatch(r'[^@]+@[0-9a-f]{40}', use) for use in uses)
    for required in (
        'name: Testar hostname seguro do AERO',
        'php tests/test-aero-host.php',
        'name: Conferir arquivos de segurança',
        'test ! -e .github/workflows/importar-v5.yml',
        "grep -RIn --exclude-dir=.git --exclude=validar.yml -E 'alex" + ".direito' .",
        'sha512sum --check --strict',
    ):
        assert required in normal, 'CI perdeu: ' + required
    checksum = re.search(r'echo "([a-f0-9]{128})  /tmp/wp-cli.phar" \| sha512sum --check --strict', normal)
    assert checksum, 'WP-CLI sem checksum SHA512'
    assert normal.index('sha512sum --check --strict') < normal.index('php /tmp/wp-cli.phar')
    if connectors:
        for test in ('test-robots.php', 'test-freshness.php', 'test-fingerprint-concurrency.php'):
            assert re.search(r'php\s+.*tests/' + re.escape(test), normal), 'CI perdeu: ' + test


connectors = any((root / 'tests' / name).exists() for name in (
    'test-robots.php', 'test-freshness.php', 'test-fingerprint-concurrency.php'))
validate(normal, manual, connectors)
assert not (root / '.github/workflows/importar-v5.yml').exists()
mutations = [
    normal.replace('php tests/test-aero-host.php', 'true'),
    normal.replace('name: Testar hostname seguro do AERO', 'name: removido'),
    normal.replace('name: Conferir arquivos de segurança', 'name: removido'),
    normal.replace('test ! -e .github/workflows/importar-v5.yml', 'true'),
    normal.replace('if grep -RIn', 'if echo'),
    normal.replace('sha512sum --check --strict', 'cat'),
    re.sub(r'(actions/checkout)@[a-f0-9]{40}', r'\1@v4', normal),
]
# Exercise future integration requirements even on the standalone PR #1.
integrated = normal + '\n'.join('php tests/' + t for t in (
    'test-robots.php', 'test-freshness.php', 'test-fingerprint-concurrency.php'))
validate(integrated, manual, True)
for test in ('test-robots.php', 'test-freshness.php', 'test-fingerprint-concurrency.php'):
    try:
        validate(integrated.replace(test, 'removed.php'), manual, True)
    except AssertionError:
        pass
    else:
        raise AssertionError('Mutação não detectada: ' + test)
for mutant in mutations:
    try:
        validate(mutant, manual, connectors)
    except AssertionError:
        pass
    else:
        raise AssertionError('Mutação não detectada')
print('OK CI: guardas AERO, união dos conectores, SHA40, SHA512 e 10 mutações; probe só manual')

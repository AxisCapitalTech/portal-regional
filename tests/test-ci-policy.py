"""Regression guard: external source probes must never run on push or PR."""
from pathlib import Path
import re

root = Path(__file__).resolve().parents[1]
normal = (root / ".github/workflows/validar.yml").read_text()
manual = (root / ".github/workflows/probe-fontes-manual.yml").read_text()
assert "official-live-probe:" not in normal
assert "probe-live-sources.php" not in normal
trigger = re.search(r"(?ms)^on:\n(.*?)(?=^[^ \n])", manual)
assert trigger and trigger.group(1).strip() == "workflow_dispatch:"
assert "contents: read" in normal and "contents: read" in manual
assert not (root / ".github/workflows/importar-v5.yml").exists()
print("OK CI sem fontes externas em push/PR; diagnóstico exclusivamente manual")

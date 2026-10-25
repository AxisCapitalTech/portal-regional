#!/usr/bin/env bash
# Executar em computador com Git autenticado e APÓS criar repositório privado vazio.
set -euo pipefail
cd "$(dirname "$0")"
TARGET="https://github.com/AxisCapitalTech/portal-regional.git"
if ! command -v git >/dev/null; then
  echo 'Git não encontrado. Instale o Git e execute novamente.' >&2
  exit 1
fi
if ! git rev-parse --is-inside-work-tree >/dev/null 2>&1; then
  echo 'Este pacote deve conter o diretório .git. Extraia o ZIP completo.' >&2
  exit 1
fi
if git remote get-url origin >/dev/null 2>&1; then
  EXISTING="$(git remote get-url origin)"
  if [ "$EXISTING" != "$TARGET" ]; then
    echo "Remote origin diferente: $EXISTING" >&2
    echo "Configure manualmente após revisar: $TARGET" >&2
    exit 1
  fi
else
  git remote add origin "$TARGET"
fi
printf 'Destino: %s\n' "$TARGET"
printf 'Confirmando envio para o repositório privado que você criou...\n'
git push -u origin main
git push origin v5.0.0
printf 'Envio concluído. Confira GitHub Actions no repositório.\n'

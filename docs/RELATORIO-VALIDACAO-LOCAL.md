# Validação local do Portal Regional V5 (20/10/2026)

- PHP CLI: 8.4.24.
- Verificação de sintaxe: todos os arquivos PHP em `wp-content/` e `tests/` sem erros.
- `tests/test-repository.py`: aprovado.
- `test-source-parser.php`: aprovado para Arapongas, Londrina, Maringá, Apucarana e AEN com HTML sintético.
- `test-publication-guard.php`: aprovado.
- `test-approval.php`: aprovado.
- `test-official-collect.php`: aprovado, inclusive duplicatas e manutenção do último sucesso quando há falha simulada.
- WordPress + MariaDB: **não executado**, requer runner GitHub ou máquina com Docker.
- Fontes oficiais ao vivo: **não testadas** neste passo.
- GitHub remoto: **não criado** pela integração; sem permissão de criação de repositório ou instalações listadas.

A execução do teste de falha simulada produz propositalmente aviso no stderr, sem falha de teste.

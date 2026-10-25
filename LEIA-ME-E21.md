# PR1 News — Etapa 21

## Implementado
- Endpoint administrativo WordPress `admin_post_pra_verify_source` (POST), nonce, `edit_post` e post `pending`.
- Formulário em Ferramentas → PR1 — Verificar fonte (`tools.php?page=pra-source-review&post_id=123`).
- Metadados: URL HTTPS, texto, hash SHA-256, identificador do editor, timestamp e histórico limitado a 50 eventos.
- Integração com gate E20 do worker, que continua desligado.
- DEMO-E21.html: simulação local interativa; não acessa WordPress nem servidor.

## Testes
- `php tests/test-e21-review.php`: 9 PASS, com stubs WordPress.
- `php -l` no novo arquivo: PASS.
- Não testado em WordPress/MySQL, sem teste de concorrência ou de end-to-end.

## Segurança / limitações
- O hash não prova a veracidade da fonte. A autenticação da fonte é uma declaração do editor autorizado, não verificação criptográfica da instituição de origem.
- O histórico de metadados é editável por administradores; não é auditoria imutável.
- O envio sequencial de múltiplos campos não é transacional; precisa de banco de dados e controle de concorrência antes de produção.
- Não há publicação automática, cobrança, IA ou coleta habilitadas. Não fazer deploy de produção.

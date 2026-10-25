# PR1 News — integração E22 para homologação

**Estado da entrega**: código da E8 + E15–E22 montado e testado isoladamente. Não há instalação WordPress/MySQL pública; não confunda com a demonstração Vercel E21.

## Correções adicionadas
- Confirmação de fontes exige editor (`edit_others_posts`) e permissão para alterar a pauta.
- Link para verificar fonte aparece na caixa de publicação de pautas `pending`.
- Worker somente aceita o registro canônico E22 em um único metadado, com ator, SHA-256 e texto atestado. Pautas E21 antigas precisam ser verificadas novamente. Metadados incoerentes falham fechados.
- Lock de worker com timestamp + nonce aleatório exclusivo; CAS mantém recuperação segura quando há expiração.
- CI agora cobre testes isolados E16–E22, além de smoke em WordPress/MariaDB executado somente quando houver runner/serviços habilitados.

## Limitações e próximos requisitos operacionais
- Não foram executados testes WordPress/MySQL reais nesta sessão (sem Docker, mysqli, SimpleXML e mbstring no runtime). O workflow CI contém smoke test, mas a sua execução ainda não foi observada.
- O registro canônico reduz leituras parciais, mas não implementa auditoria imutável nem transações gerais, e o SHA-256 não comprova veracidade da fonte.
- Deploy real requer infraestrutura WordPress PHP 8.2+, banco MySQL/MariaDB, HTTPS, proteção de acesso, backups, credenciais seguras e testes de integração.
- Coleta automática, IA e publicação automática ficam **desligadas**. Não ligar serviços pagos sem análise de custos e consentimento.
- Manter o projeto PR1 V9 no ar sem modificações.

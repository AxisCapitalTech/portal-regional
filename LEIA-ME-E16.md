# PR1 News E16 — auditoria e proteção fail-closed

Base: integração E8 + E15 fornecida no pacote anterior.

**Alteração de segurança:** bloqueio incondicional temporário do cliente OpenAI e do worker agendado até que exista controle financeiro atômico real e homologação. Mesmo constantes de ativação não desbloqueiam as chamadas.

A E15 não tinha teto financeiro atômico, ingestão de texto verificado ou trava recuperável. Não é seguro executar autonomamente.

Executar `php tests/test-e16-fail-closed.php` para validar o bloqueio sem rede.

**Não instalar em produção.** Sem push, merge, deploy ou gastos.

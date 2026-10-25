# PR1 News — E18: recuperação de trava do worker

Base: E17 (E8 + E15 + E16 + E17). Sem push, merge, deploy ou ativação de IA.

## Alteração
A trava de execução do worker agora permite recuperação de um registro antigo (15 minutos) por compare-and-swap SQL, e sua liberação só exclui o valor pertencente ao executor original. Registros inválidos bloqueiam a execução. Essa proteção reduz travamentos e remoções indevidas entre execuções.

## Verificação
`php tests/test-e18-worker-lock.php` — teste isolado com simulador de opções/SQL, sem WordPress/MySQL real. `php -l` do módulo modificado.

## Limites e pendências
A implementação não equivale a lock distribuído com lease renovável. Um trabalho acima de 15 minutos pode ter sobreposição; é obrigatório limitar o tempo da execução ou implementar heartbeat antes da ativação. A integração com banco real, ingestão verificável de fontes, orçamento sob concorrência e teste editorial ponta a ponta ainda estão pendentes. Cliente OpenAI e worker mantêm `return false` incondicional; nenhuma API paga é acionada. Não usar em produção.

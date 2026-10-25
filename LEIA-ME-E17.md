# PR1 News — Etapa 17: reserva financeira atômica (offline)

Base: E8 + E15 + E16. Adicionado `PRA_OpenAI_Budget`, com reserva diária conservadora em microdólares por SQL atômico. Cliente chama a reserva após quota diária e antes da rede.

## Importante
- Cliente OpenAI e worker **continuam com `return false` incondicional** da E16.
- A tabela `wp_pra_openai_budget` ainda exige instalação via `PRA_OpenAI_Budget::install()` no WordPress de homologação.
- Para um futuro teste autorizado, configurar `PR1_OPENAI_DAILY_BUDGET_MICRO_USD` e `PR1_OPENAI_MAX_COST_PER_REQUEST_MICRO_USD` com custo máximo demonstrável para o modelo escolhido. O teto depende de preço vigente, tokens de entrada/saída e overhead; não presumir custo.
- Reserva é conservadora: não estorna em falha, nem tenta reconciliar faturamento real; teto apenas para o tráfego controlado por este cliente, não para a conta OpenAI inteira.
- A trava do worker e ingestão verificada seguem pendentes. Não habilitar IA nem produção.
- Testes isolados verificam fail-closed e formato SQL; **não comprovam concorrência real no MySQL**.
- Nenhum push, merge, deploy, serviço pago ou coleta.

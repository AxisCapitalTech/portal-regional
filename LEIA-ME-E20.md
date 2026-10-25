# PR1 News — Etapa 20: bloqueio de fontes sem evidência
Base: pacote E19/E18 preservado.
O worker exige URL HTTPS, texto de 200+ bytes, hash SHA256, marcador de revisão humana e timestamp.
**Limitação:** o marcador é apenas metadado; não comprova identidade nem autorização. Hash comprova integridade, não veracidade. Não existe ainda fluxo autenticado de atestação.
Sem ingestão real, sem MySQL/WordPress, sem IA, sem custos ou deploy.
Teste isolado: 6/6 casos aprovados (1 positivo, 5 negativos). PHP lint: 2/2.
Pendente: autenticação do verificador, trilha de auditoria, concorrência MySQL, orçamento integrado, E2E e WordPress real.

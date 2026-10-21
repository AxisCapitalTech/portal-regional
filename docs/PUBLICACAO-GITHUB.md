# Publicar o Portal Regional V5 no GitHub

Repositório desejado: `AxisCapitalTech/portal-regional`, **privado**.

## Etapa obrigatória na conta GitHub

1. Acesse https://github.com/new, selecione a conta `AxisCapitalTech`, nomeie como `portal-regional` e marque **Private**.
2. Não marque README, `.gitignore` ou licença na criação (o pacote já contém esses arquivos).
3. Acesse https://github.com/settings/installations e configure o acesso da integração GitHub/ChatGPT ao repositório recém-criado (ou reautorize o conector se solicitado).
4. No computador com Git instalado, descompacte `portal-regional-v5-repositorio-git.zip` e execute o script `PUBLICAR-GITHUB.sh` a partir da pasta extraída, após autenticar-se no GitHub para `git push`.

Alternativamente, informe no ChatGPT que o repositório está criado e autorizado; o conector poderá tentar subir os arquivos nele.

## Controle de versões

- Branch inicial: `main`.
- Versão de referência: tag `v5.0.0`.
- CI em `.github/workflows/validar.yml` executará PHP lint, testes unitários/simulados e o smoke de WordPress + MariaDB, se a integração e as políticas do GitHub Actions permitirem.
- `ops/.env` e segredos não são versionados. Configure valores de teste somente no ambiente de homologação.
- A V5 não foi publicada na internet e nenhuma coleta ou notícia foi colocada em produção.

## Critérios para liberar operação real

Após CI ficar verde, validar coleta real das quatro cidades e AEN, documentação das fontes, taxas de erro, proteção editorial, SSL, backups, consentimento dos assinantes e reputação de e-mail. Não ativar publicação automática para notícias sensíveis.

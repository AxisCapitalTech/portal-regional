# Portal Regional V5 — homologação reproduzível

Portal de notícias WordPress para **Arapongas, Londrina, Maringá e Apucarana**, com complemento Paraná/Brasil. A V5 mantém o tema e integra salvaguardas de revisão editorial, testes sintéticos completos e ambiente local de homologação.

## Conteúdo do repositório

- `wp-content/themes/portal-regional-tema`: tema responsivo V4 preservado;
- `wp-content/plugins/portal-regional-agentes`: plugin de monitoramento e publicidade, com aprovação de texto revisado;
- `tests/`: parser, proteção editorial, integração simulada e smoke test para WordPress real;
- `ops/compose.yaml` + `ops/.env.example`: laboratório com WordPress/MariaDB ligado só a localhost;
- `.github/workflows/validar.yml`: testes PHP e smoke test em WordPress real no GitHub Actions.

## Uso de laboratório

É necessário **Docker Compose** em uma máquina própria. Não foi possível executar Docker ou MySQL neste ambiente de criação.

1. Copie `ops/.env.example` para `ops/.env` e substitua senhas por valores fortes locais.
2. Na pasta `ops`, execute `docker compose up -d`.
3. Abra `http://localhost:8088` e finalize a configuração WordPress.
4. Instale/ative o plugin **Portal Regional — Agentes Editoriais** e o tema **Portal Regional Tema**.
5. Confirme `Ferramentas > Fontes Oficiais`: mantenha **coleta automática desativada**; execute uma tentativa manual por fonte e inspecione títulos e links.
6. Configure `Configurações > Fuso horário > São Paulo`. Verifique o agendamento externo (container `cron`).
7. Instale o MailPoet apenas após aprovar política de privacidade e envio; não importe contatos sem consentimento.
8. Para encerrar o laboratório sem apagar dados: `docker compose down` (não use `down -v` sem backup).

**Atenção:** o Docker Compose abre só `127.0.0.1` e não provê HTTPS público, backup remoto, certificado, WAF ou observabilidade externa. Nunca o exponha diretamente na internet. No ambiente real, contratar hospedagem PHP+MySQL com HTTPS e cron de servidor.

## Salvaguarda editorial V5

1. Uma fonte gera somente post `pending` com `[PAUTA]` e link de origem.
2. O editor redige um texto novo, retira os marcadores, confere a fonte e marca aprovação na metabox **Origem da pauta automática**.
3. Salva **antes de publicar**, para registrar a aprovação vinculada por SHA-256 ao título e ao conteúdo.
4. Em novo comando de publicação, o plugin compara o conteúdo atual com o aprovado; se houver divergência, mantém pendente.
5. Qualquer atualização do texto precisa de nova conferência. Outras postagens autorais normais continuam com fluxo do WordPress.

A conferência técnica e o checkbox não substituem apuração de fatos, direitos de imagem, responsabilidade editorial, LGPD nem orientação jurídica.

## Testes locais

```bash
php tests/test-source-parser.php
php tests/test-publication-guard.php
php tests/test-approval.php
php tests/test-official-collect.php
python3 tests/test-repository.py
find wp-content tests -name '*.php' -print0 | xargs -0 -n 1 php -l
```

Os testes sintéticos de fontes não usam a internet. O teste `smoke-wordpress.php` exige WordPress/MariaDB e é preparado para GitHub Actions quando este repositório estiver no GitHub.

## GitHub

A conta do conector foi identificada como `AxisCapitalTech`, mas **nenhuma instalação ou repositório acessível estava disponível**. Não houve push. Crie um repositório privado e conceda à integração acesso explícito. Importe os arquivos sem incluir `ops/.env`, credenciais ou dados privados. Quando acionado, o workflow executará os testes reais no runner remoto; não foram executados neste ambiente.

## Limitações

- Não instalamos WordPress nem enviamos dados a uma hospedagem real.
- Coletas HTML podem quebrar com mudanças no layout municipal. Em particular, Arapongas deve ser testada no servidor porque a página pode depender de JavaScript.
- O RSS do Blog Londrina tem fallback HTML, que pode não extrair links amigáveis.
- Publicidade e newsletter exigem configuração/comprovação própria; não são enviadas nesta etapa.
- O monitor das fontes não comprova veracidade dos conteúdos nem licencia imagens de terceiros.

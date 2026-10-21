# V6 — PR1 News (marca provisória de homologação)

## Decisão atual
- Nome de apresentação de teste: **PR1 News**.
- Símbolo: **PR1**.
- Frase: "Notícias da sua região, com contexto."
- Paleta de referência: azul #003087, vermelho #E30613, branco.
- Identificadores técnicos como `AxisCapitalTech/portal-regional`, `portal-regional-tema` e `portal-regional-agentes` **não mudam**.

## Marca não validada juridicamente
PR1 já é usado por veículo jornalístico paranaense. "News" pode não ser suficiente para evitar conflito. **Não lançar sob esta marca**, solicitar anúncios, comprar domínio, registrar em redes sociais ou imprimir material até fazer busca de anterioridade no INPI (marcas relacionadas e classes de serviços), análise de risco e decisão do responsável. Não afirmar exclusividade.

## Ajustes reversíveis no WordPress
1. No painel, ir a **Aparência > Personalizar > Marca pública — homologação**.
2. Editar nome, sigla e frase. Isso troca a apresentação do cabeçalho, rodapé e início da página sem alterar código do tema.
3. Ajustar também **Configurações > Geral > Título do site** e descrição, para manter a tag HTML e metadados consistentes.
4. Em homologação, usar **Configurações > Leitura > Desencorajar mecanismos de busca** (esta opção não protege contra acessos; exigir autenticação/restrição de acesso no servidor).
5. Manter envio a assinantes, anúncios e coleta automática de notícias desligados até testes e aprovações.

## Próxima fase: homologação acessível por navegador
Necessário provisionar hospedagem WordPress com PHP 8.2+, MariaDB/MySQL, HTTPS, cron do servidor, controle de acesso, backups e logs. O ambiente Docker fornecido é **somente local**, não uma implantação pública.

Após obter hospedagem, instalar pacotes do workflow, confirmar fuso America/Sao_Paulo e executar coleta **manual** de cada fonte, registrando resultados, links, erros, data e duplicatas. A integração sintética no CI não prova que os layouts reais das fontes externas funcionam.

## Critérios para liberar publicação
- Nome juridicamente aprovado e domínio sob controle do projeto.
- Política editorial, páginas de contato, privacidade e correções publicadas.
- Responsável editorial humano para cada notícia; pautas capturadas nunca passam automaticamente a publicadas.
- Verificação de acessibilidade/responsividade, SEO, disponibilidade, backup e monitoramento.

# PR1 News V6 — homologação privada com WordPress no Railway

**Estado:** infraestrutura preparada no GitHub. **Ainda não implantada, sem URL e sem gastos autorizados.** O nome PR1 News é provisório e não deve ser divulgado como marca definitiva antes da busca de anterioridade.

## Pré-requisitos e configuração do Railway

1. Conectar Railway ao ChatGPT e conferir plano, previsão de cobrança e limites antes de provisionar recursos tarifáveis.
2. Criar projeto isolado de homologação. Criar serviço **MySQL** com rede interna e volume persistente; não habilitar TCP público.
3. Criar serviço de aplicação pelo GitHub privado `AxisCapitalTech/portal-regional`, usando a branch `main`. O `railway.json` escolhe `deploy/railway/Dockerfile`.
4. Configurar `PORT=80` para o WordPress. Configurar `WORDPRESS_DB_HOST`, `WORDPRESS_DB_NAME`, `WORDPRESS_DB_USER` e `WORDPRESS_DB_PASSWORD` usando as referências **internas** do serviço MySQL (o host deve incluir porta).
5. Configurar `STAGING_USER` e `STAGING_PASSWORD` nos segredos do Railway (senha exclusiva de **20 caracteres ou mais**), nunca no GitHub. Sem ambos, a imagem falha na inicialização.
6. Anexar volume persistente ao serviço WordPress em `/var/www/html/wp-content/uploads`. Não cobrir `/var/www/html` ou o diretório completo `/wp-content`, pois isso ocultaria arquivos do tema/plugin da imagem.
7. Gerar URL temporária HTTPS do Railway. Verificar resposta `401` sem credenciais, rejeição de senha errada e acesso autorizado. O site só deve permitir completar o instalador **depois** da senha de homologação.
8. Instalar WordPress com administrador e senha próprios (diferentes da autenticação de homologação), ativar tema e plugin, definir fuso horário `America/Sao_Paulo`, confirmar coletores desligados.
9. Fazer coleta manual de cada fonte (Arapongas, Londrina, Maringá, Apucarana e AEN), registrar resultados e erros de extração, sem publicação automática.
10. Verificar responsividade, logs, backups de banco e uploads, restauração, permissões editoriais e política de privacidade antes de abrir ao público.

## Segurança e escopo

- Site de homologação não é anúncio nem lançamento. PR1 News permanece **provisório** por possível conflito de marca.
- WordPress e arquivos passam por autenticação HTTP Basic, mas **somente sobre HTTPS**. As credenciais não substituem proteção de aplicação e banco.
- Não habilitar cron, notícias automáticas, disparos de e-mail, publicidade real ou contratação de domínio.
- Os testes sintéticos de coleta não certificam que os cinco conectores funcionam na internet real.
- O custo e possíveis cobranças da hospedagem dependem do plano Railway; apresentar antes de provisionar.

Documentação: https://docs.railway.com/guides/wordpress , https://docs.railway.com/guides/mysql , https://docs.railway.com/reference/volumes .

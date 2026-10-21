# PR1 News — Etapa 1 · segurança institucional (PR para revisão)

**Não mesclar nem implantar sem revisão humana.** Base: commit `54ff6e1` da `main`.

## Configuração do AERO

O endereço pessoal padrão foi retirado dos arquivos atuais do tema. O endpoint externo passa a ser habilitado apenas se o operador definir um domínio sob seu controle em `wp-config.php` (fora do Git):

```php
define('PR1_AERO_HOST', 'clima.exemplo.com.br');
```

A constante recebe apenas um hostname DNS: sem `https://`, porta, caminho, parâmetros ou credenciais. A função `validated_host` forma a allowlist única para o link e o `iframe`. Se a constante estiver ausente ou inválida, nenhum URL externo ou iframe é emitido; um aviso aparece na página AERO. O widget de previsão local fica preservado e continua bloqueado por padrão em produção sem licença comercial.

**Antes de habilitar um hostname na implantação:** assegurar que há TLS válido, domínio efetivamente controlado, conteúdo revisado e raio LIVE corrigido no serviço externo. O host antigo pode continuar nos commits históricos; a alteração do Git atual não apaga o histórico de versões.

## Pipeline

- O workflow legado `.github/workflows/importar-v5.yml` foi removido da árvore atual. Ele tinha `contents: write` e podia fazer push diretamente à `main`.
- O pipeline restante `.github/workflows/validar.yml` possui `permissions: contents: read` e executa testes do WordPress e plugins.
- Senhas de administrador no smoke e de Basic Auth no Docker passam a ser geradas, por execução, com `openssl rand -hex 24`. São utilizadas somente no runner e não devem ser registradas em logs.
- Adicionados testes independentes para validar hostname, rejeitar URLs inseguras e verificar o caso sem configuração.
- Preservados `post_status='pending'`, `prevent_unreviewed_publish`, `record_editorial_approval` e coleta desativada por padrão.

## Pendências explícitas

Conectores/robots/frescor (Etapa 2); páginas legais/SEO/UX e governança editorial (Etapa 3); correção do AERO V13 no **repositório próprio**, quando autorizado e disponível (Etapa 4). Nenhum site foi implantado, domínio comprado, newsletter ou cron de produção ativado.

## Domínio oficial pendente de definição

**Ainda não há domínio AERO oficial homologado para o PR1.** O valor `clima.exemplo.com.br` acima é ilustrativo, não é endereço registrado, configurado ou publicável. Para produção, aprovar juridicamente e controlar um domínio, revisar a fonte de raios, verificar HTTPS e `frame-ancestors` no AERO; somente então definir `PR1_AERO_HOST` no `wp-config.php`. Sem constante, o painel remoto fica indisponível de propósito.

O diagnóstico de fontes externas foi removido do workflow de push/PR e passou a `.github/workflows/probe-fontes-manual.yml` com gatilho exclusivo `workflow_dispatch`. O teste de ausência de domínio pessoal usa `grep` do runner em vez de depender de `rg` instalado.

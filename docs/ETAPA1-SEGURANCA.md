# PR1 News — Etapa 1: institucional e segurança

**Pull request para revisão humana. Sem merge e sem implantação.**

## AERO: origem HTTPS controlada

O domínio antigo foi retirado do código atual e o campo `regional_aero_url` removido do personalizador WordPress.

Para ativar o painel externo AERO, o administrador deverá definir **um hostname de domínio próprio, sem protocolo, porta ou caminho**, exclusivamente no `wp-config.php` (arquivo ignorado pelo Git):

```php
define('PR1_AERO_HOST', 'clima.exemplo.com.br');
```

Esse domínio é **apenas ilustrativo**; só habilite após confirmar o controle, HTTPS e a segurança dos dados de raios. Se a constante estiver ausente ou inválida, o tema **não gera link ou iframe** para o AERO externo. O módulo de previsão local do PR1 não muda.

Não use domínio pessoal, URL com barra, querystring, localhost ou endereço IP. A validação também rejeita hosts DNS malformados. O histórico Git anterior ao PR permanece preservado; a limpeza refere-se à árvore atual da branch.

## Workflow removido
O importador legado `.github/workflows/importar-v5.yml` era capaz de decodificar código base64 e fazer push em `main` com `contents: write`. O arquivo é removido na branch, sem reescrever o histórico.

## Senhas temporárias de CI
A instalação WordPress com WP-CLI e os testes de autenticação do contêiner usam `openssl rand -hex 24`, somente em variáveis de ambiente locais ao job, sem imprimir a senha. Não há geração/alteração de credenciais reais.

## Testes e limitações
- `tests/test-aero-host.php`: rejeição de URL, porta, credenciais, IP, barra e host inválido; garante desativação por padrão.
- `tests/test-aero-host-configured.php`: uma origem HTTPS autorizada com hostname definido antes da carga do tema.
- `tests/test-repository.py`: ausência do hostname pessoal, workflow legado, configuração antiga e credenciais fixas.
- `tests/smoke-wordpress.php`: smoke da configuração desativada e validação de DNS.
- O fluxo editorial (pautas `pending`, SHA-256, aprovações humanas) não foi alterado; `enabled=0` permanece.
- Nenhuma implantação, cron, IA, newsletter, cobrança ou serviço de notificação foi ativado.

**Pendente:** Etapa 2 (conectores), Etapa 3 (legal/SEO/UX) e Etapa 4 (repositório AERO separado). O problema de raios desatualizados deve ser resolvido antes de incorporar o AERO em produção.

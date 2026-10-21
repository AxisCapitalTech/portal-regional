# PR1 News V9 — AERO e fontes oficiais (21/10/2026)

## Integração AERO
- O portal WordPress tem um widget de condições meteorológicas de Arapongas, Londrina, Maringá e Apucarana com REST, dados da Open-Meteo, cache de 10 minutos, limites de 8s de consulta, validação de campos e tratamento explícito de indisponibilidade.
- A página `/?pr1_aero=1` cria uma central de meteorologia responsiva e permite abrir o AERO Command Center **original** no domínio de implantação preexistente. A incorporação iframe é opcional, iniciada pelo visitante. Algumas implantações bloqueiam iframe via cabeçalhos; o link externo é o fallback.
- **Não foi copiado o código do AERO**: o arquivo de referência do app não está disponível como arquivo executável do repositório e ele inclui rotas próprias, como `/api/lightning`, `/api/radar-frame` e `/api/inmet-alerts`. Assim, apenas o AERO original pode oferecer suas funções completas.
- O endereço AERO permanece configurável no Personalizar, mas limitado ao hostname aprovado.

## Restrição de licença para meteorologia
A API aberta da Open-Meteo autoriza uso **não comercial**, com atribuição CC BY 4.0. Como o PR1 News prevê publicidade/assinatura, o REST de previsão fica indisponível em `WP_ENVIRONMENT_TYPE=production` por padrão. Antes de ativá-lo em operação, contratar licença comercial ou implementar um provedor comercialmente autorizado, e então definir `PR1_AERO_COMMERCIAL_API_APPROVED=true` no ambiente, comprovando aprovação contratual. Nunca informar clima atualizado quando a fonte falhar.
Referências: https://open-meteo.com/en/terms / https://open-meteo.com/en/pricing

## Conectores
- Londrina, Apucarana, AEN: os testes reais obtiveram 6 links em cada fonte.
- Arapongas: HTML obtido com HTTP 200, **sem URLs aproveitáveis**. A página pode depender de JavaScript ou de API privada; nada será adivinhado.
- Maringá: timeout de conexão observado também via cURL em runner; não considerar coleta automática validada.
- Adicionado à tela `Ferramentas > Fontes Oficiais` um fluxo manual para Arapongas e Maringá: administrador envia URL HTTPS e título vistos na fonte, valida-se hostname/padrão, o sistema elimina duplicatas e cria somente `post_status='pending'` com proveniência. Não é um conector automático resolvido; é continuidade operacional segura.
- Fontes automáticas permanecem desativadas e notícias oficiais públicas apenas no clipping externo identificado.

## Homologação
GitHub Actions: PHP 8.0/8.2/8.3 + WordPress/MariaDB + testes de coletor + lint + Docker e proteção. Ainda é necessário hospedar WordPress PHP/MySQL num ambiente protegido e testar visualmente a central AERO no iPhone. O navegador pode bloquear o iframe do aplicativo externo; acessar no domínio de origem é a integração garantida.

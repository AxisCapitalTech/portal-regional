# PR1 News · Integração visual V7 no WordPress

**Estado:** versão V7 integrada diretamente ao tema original `portal-regional-tema` no GitHub. A prévia HTML anterior continua sendo uma referência independente; o código funcional segue WordPress + MariaDB.

## O que foi transferido
- Cabeçalho PR1 News, barra superior, navegação horizontal por cidade e controles acessíveis de leitura.
- Capa em azul/vermelho, seletor de cidades, hierarquia editorial, formatos de conteúdo, cartões de cidades, anúncios claramente sinalizados e compromisso com checagem humana.
- Modelo da reportagem real com título, linha fina quando disponível, autoria, data, atualização, imagem com legenda e link à fonte.
- Estilos responsivos para iPhone e desktop, sem bibliotecas de CSS/JS externas.

## O que permanece inalterado
- Identificador técnico do repositório `AxisCapitalTech/portal-regional`, tema e plugin originais.
- Pesquisa nativa, categorias WordPress, posts `publish` e respeito ao fluxo de aprovação de pautas `pending` do plugin.
- Fontes monitoradas não são publicadas automaticamente, nem qualquer notícia foi criada artificialmente para preencher o layout.
- A marca **PR1 News** é provisória, sujeita à verificação de anterioridade antes de domínio ou lançamento.
- Não houve hospedagem pública ou integração de mensagens reais/assinaturas.

## Validações
As verificações estáticas de estrutura foram estendidas para a V7; a rotina de GitHub Actions executa teste WordPress+MariaDB, teste de aprovação e proteção editorial, mais lint PHP 8.0/8.2/8.3.
A renderização em dispositivo real e conectores reais ainda exigem hospedagem de homologação para o WordPress.

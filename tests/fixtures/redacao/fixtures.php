<?php
/**
 * FIXTURES DE TESTE — conteúdo FICTÍCIO criado só para testes automatizados.
 * Não são notícias, não vieram de nenhuma fonte real e não devem ser publicados.
 */
return (function () {
  $src0 = 'FIXTURE DE TESTE. A Prefeitura de Arapongas informou que a campanha de vacinação contra a gripe '
    . 'será realizada de 3 a 14 de novembro em 12 unidades básicas de saúde. Segundo a secretaria, o público-alvo inclui '
    . 'idosos acima de 60 anos e gestantes. "A vacina estará disponível das 8h às 17h em todas as unidades", disse a secretária de Saúde.';
  $body = "Moradores de Arapongas terão nova oportunidade de proteção contra a gripe neste mês. A administração municipal "
    . "organizou a ação nas unidades de saúde espalhadas pelos bairros, com foco nos grupos mais vulneráveis.\n\n"
    . "De acordo com o comunicado oficial, a imunização ocorre entre os dias 3 e 14, e quem pertence aos grupos prioritários "
    . "deve levar documento com foto e a carteira de vacinação. A orientação é procurar a unidade mais próxima de casa.\n\n"
    . "A secretária afirmou que \"a vacina estará disponível das 8h às 17h em todas as unidades\". Dúvidas podem ser tiradas pelos canais oficiais do município.";
  $good = [
    'title' => '[FIXTURE] Arapongas faz campanha de vacinação contra a gripe em novembro',
    'subtitle' => 'Ação ocorre em 12 unidades básicas de saúde, com foco em idosos e gestantes',
    'lead' => 'A Prefeitura de Arapongas realiza de 3 a 14 de novembro a campanha de vacinação contra a gripe em 12 unidades.',
    'body' => $body,
    'sources' => [['url' => 'https://www.arapongas.pr.gov.br/fixture-teste', 'org' => 'Prefeitura de Arapongas (FIXTURE)', 'accessed_at' => '2026-10-22T13:00:00-03:00']],
    'facts' => [['text' => 'campanha de vacinação contra a gripe de 3 a 14 de novembro em 12 unidades básicas de saúde', 'source' => 0]],
    'claims' => [],
    'seo' => ['seo_title' => 'Vacinação contra gripe em Arapongas', 'meta_description' => 'Campanha de 3 a 14 de novembro em 12 UBS.', 'focus_keyword' => 'vacinação Arapongas'],
    'provenance' => ['agent' => 'reporter_regional', 'model' => 'fixture', 'generated_at' => '2026-10-22T13:00:00Z', 'pauta_fingerprint' => 'fixture'],
    'status' => 'pending',
  ];
  return ['source_texts' => [$src0], 'good' => $good,
    'pauta' => ['title' => '[FIXTURE] Campanha de vacinação contra gripe em Arapongas', 'url' => 'https://www.arapongas.pr.gov.br/fixture-teste', 'area' => 'Arapongas']];
})();

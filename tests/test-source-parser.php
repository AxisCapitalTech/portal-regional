<?php
/** Testes com HTML sintético. Não afirmam validar páginas reais ao vivo. */
define('ABSPATH', __DIR__.'/');
if(!function_exists('mb_strtolower')) { function mb_strtolower($value,$encoding='UTF-8'){return strtolower($value);} }
if(!function_exists('mb_strlen')) { function mb_strlen($value,$encoding='UTF-8'){return strlen($value);} }
require __DIR__.'/../wp-content/plugins/portal-regional-agentes/includes/class-pra-source-parser.php';
$tests=[
 'arapongas'=>['https://arapongas.atende.net/cidadao/noticia/prefeitura-anuncia-agenda-de-eventos','Prefeitura divulga programação cultural em Arapongas'],
 'londrina'=>['https://blog.londrina.pr.gov.br/?p=245462','Prefeitura de Londrina anuncia exposição escolar'],
 'maringa'=>['https://www.maringa.pr.gov.br/noticias/acao-de-formacao-para-a-populacao/43327','Maringá realiza ação de formação para a população'],
 'apucarana'=>['https://www.apucarana.pr.gov.br/site/novas-vagas-de-trabalho-na-cidade/','Apucarana divulga novas vagas de trabalho na cidade'],
 'aen'=>['https://www.parana.pr.gov.br/aen/Noticia/Parana-inicia-nova-etapa-do-projeto','Paraná inicia nova etapa do projeto estadual'],
];
foreach($tests as $k=>[$url,$title]){
 $html='<html><body><section><h3><a href="'.htmlspecialchars($url,ENT_QUOTES).'">'.htmlspecialchars($title,ENT_QUOTES).'</a></h3></section></body></html>';
 $res=PR_Source_Parser::extract($html,$k);
 if(count($res)!==1 || $res[0]['title']!==$title || $res[0]['link']!==PR_Source_Parser::canonical($url,$k)){fwrite(STDERR,"FAIL extraction $k ".json_encode($res)."\n");exit(1);}
 $evil=str_replace(parse_url($url,PHP_URL_HOST),'evil.example', $url);
 if(PR_Source_Parser::canonical($evil,$k)!==''){fwrite(STDERR,"FAIL external host $k\n");exit(1);}
 if(PR_Source_Parser::canonical('http://'.parse_url($url,PHP_URL_HOST).parse_url($url,PHP_URL_PATH),$k)!==''){fwrite(STDERR,"FAIL non-HTTPS $k\n");exit(1);}
 if(PR_Source_Parser::canonical($url.($k==='londrina'?'&':'?').'utm_medium=spam',$k)!==PR_Source_Parser::canonical($url,$k)){fwrite(STDERR,"FAIL canonical $k\n");exit(1);}
 echo "OK $k\n";
}
$dupe='<h3><a href="'.$tests['maringa'][0].'">'.$tests['maringa'][1].'</a></h3><h3><a href="'.$tests['maringa'][0].'">'.$tests['maringa'][1].'</a></h3>';
if(count(PR_Source_Parser::extract($dupe,'maringa'))!==1){fwrite(STDERR,"FAIL duplicate\n");exit(1);} echo "OK duplicate\n";
$mal='<h3><a href="https://www.maringa.pr.gov.br.evil.example/noticias/teste/12345">Notícia falsa em domínio malicioso teste</a></h3>';
if(count(PR_Source_Parser::extract($mal,'maringa'))!==0){fwrite(STDERR,"FAIL deceptive hostname\n");exit(1);}echo "OK host spoofing\n";

$not_article=[
  'https://www.apucarana.pr.gov.br/site/noticias/',
  'https://www.apucarana.pr.gov.br/site/secretarias/',
  'https://www.apucarana.pr.gov.br/site/contato/',
  'https://www.apucarana.pr.gov.br/site/portal-da-transparencia/',
  'https://www.apucarana.pr.gov.br/site/ouvidoria/'
];
foreach($not_article as $url){
  if(PR_Source_Parser::canonical($url,'apucarana')!==''){fwrite(STDERR,"FAIL institutional Apucarana URL: $url\n");exit(1);}
  echo "OK institutional slug excluded $url\n";
}

$menu='<nav><h3><a href="https://www.apucarana.pr.gov.br/site/secretaria-de-gestao-da-cidade/">Secretaria de Gestão da Cidade e Informações</a></h3></nav>';
if(PR_Source_Parser::extract($menu,'apucarana')){fwrite(STDERR,"FAIL Apucarana menu accepted\n");exit(1);}
echo "OK Apucarana navigation rejected\n";

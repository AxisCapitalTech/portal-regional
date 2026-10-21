<?php
/** Diagnóstico externo informativo. Não grava posts, não altera opções, não publica notícias. */
define('ABSPATH', dirname(__DIR__).'/');
require_once ABSPATH.'wp-content/plugins/portal-regional-agentes/includes/class-pra-source-parser.php';
$targets=[
 'arapongas'=>'https://arapongas.atende.net/cidadao/noticia',
 'londrina'=>'https://blog.londrina.pr.gov.br/',
 'maringa'=>'https://www.maringa.pr.gov.br/noticias',
 'apucarana'=>'https://www.apucarana.pr.gov.br/site/noticias/',
 'aen'=>'https://www.parana.pr.gov.br/aen/arquivo'
];
$success=0;
foreach($targets as $key=>$url){
  $start=microtime(true);
  $ctx=stream_context_create(['http'=>[
    'timeout'=>13,'follow_location'=>1,'max_redirects'=>2,
    'header'=>"Accept: text/html\r\nUser-Agent: PR1NewsHomologacao/0.8 (somente links e titulos)\r\n"
  ]]);
  $body=@file_get_contents($url,false,$ctx);
  $status=($body!==false && isset($http_response_header[0]))?$http_response_header[0]:'sem resposta';
  $items=($body!==false&&strlen($body)>350)?PR_Source_Parser::extract($body,$key,6):[];
  $count=count($items);
  if($count)$success++;
  // Sanitized, bounded structural diagnostics when a source is downloaded but no URLs are recognized.
  $examples=[];
  if($count===0 && is_string($body) && strlen($body)>350 && preg_match_all('~<a\\b[^>]*\\bhref\\s*=\\s*(["\\\'])(.*?)\\1~isu',$body,$hits,PREG_SET_ORDER)){
    foreach($hits as $hit) {
      $href=html_entity_decode((string)$hit[2],ENT_QUOTES|ENT_HTML5,'UTF-8');
      $path=(string)(parse_url($href,PHP_URL_PATH)??'');
      $host=(string)(parse_url($href,PHP_URL_HOST)??'');
      // paths only; never log query strings, secrets, embedded HTML or third party hosts.
      if(!preg_match('~^(?:/cidadao|/noticias|/noticia|/site)~i',$path))continue;
      if($host && !in_array($host,['arapongas.atende.net','www.maringa.pr.gov.br'],true))continue;
      $examples[]=mb_substr($path,0,140);
      if(count($examples)>=8)break;
    }
  }
  echo json_encode([
    'source'=>$key,'http'=>substr($status,0,100),'downloaded_bytes'=>is_string($body)?strlen($body):0,
    'extracted_links'=>$count,'example'=>$count?$items[0]['title']:null,
    'seconds'=>round(microtime(true)-$start,1),
    'result'=>$count?'links encontrados':'sem links no padrão validado',
    'path_examples'=>$examples
  ],JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)."\n";
}
echo "RESUMO: {$success}/".count($targets)." fontes com links reconhecidos (este teste NÃO confirma exatidão factual, data ou direitos de uso).\n";

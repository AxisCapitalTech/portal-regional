<?php
/** In-memory WordPress shim: tests actual official source pipeline with synthetic HTML. */
error_reporting(E_ALL);
if(!function_exists('mb_substr')) {function mb_substr($s,$start,$length=null,$enc=null){return substr($s,$start,$length);}}
if(!function_exists('mb_strtolower')) {function mb_strtolower($s,$enc=null){return strtolower($s);}}
if(!function_exists('mb_strlen')) {function mb_strlen($s,$enc=null){return strlen($s);}}
define('ABSPATH', __DIR__.'/wp-stubs/'); define('WPINC','wp-includes');
define('HOUR_IN_SECONDS',3600);define('MINUTE_IN_SECONDS',60);
$GLOBALS['opts']=[];$GLOBALS['meta']=[];$GLOBALS['posts']=[];$GLOBALS['calls']=[];$GLOBALS['blocks']=[];
function add_action(...$args){} function add_filter(...$args){} function add_shortcode(...$args){}
function wp_parse_args($args,$defaults){return array_merge($defaults,(array)$args);}
function get_option($key,$default=false){return $GLOBALS['opts'][$key]??$default;}
function add_option($key,$value,...$args){$GLOBALS['opts'][$key]=$value;return true;}
function update_option($key,$value,...$args){$GLOBALS['opts'][$key]=$value;return true;}
function wp_next_scheduled(...$args){return false;}function wp_schedule_event(...$args){}
function term_exists($name,$tax){return isset($GLOBALS['terms'][$name])?['term_id'=>$GLOBALS['terms'][$name]]:false;}
function wp_insert_term($name,$tax,$params=[]){$id=count($GLOBALS['terms']??[])+1;$GLOBALS['terms'][$name]=$id;return ['term_id'=>$id];}
function get_transient($key){return false;}function set_transient($key,$v,$s){return true;}function delete_transient($key){return true;}
function wp_safe_remote_get($url,$args){
 $GLOBALS['calls'][]=$url;
 if(str_ends_with((string)parse_url($url,PHP_URL_PATH),'/robots.txt'))
   return ['status'=>200,'type'=>'text/plain','body'=>"User-agent: *\\nAllow: /\\nSitemap: https://arapongas.atende.net/sitemap.xml\\n"];
 if(str_ends_with((string)parse_url($url,PHP_URL_PATH),'/sitemap.xml'))
   return ['status'=>200,'type'=>'application/xml','body'=>'<urlset><url><loc>https://arapongas.atende.net/cidadao/noticia/agenda-cultural-na-cidade</loc><lastmod>'.gmdate('c').'</lastmod></url></urlset>'];
 foreach($GLOBALS['blocks'] as $host)if(str_contains($url,$host))return new WP_Error('offline');
 $map=['arapongas.atende.net'=>['/cidadao/noticia/agenda-cultural-na-cidade','Prefeitura de Arapongas divulga nova programação cultural'],
  'blog.londrina.pr.gov.br'=>['/?p=187239','Londrina abre inscrições para evento municipal'],
  'maringa.pr.gov.br'=>['/noticias/inscricoes-para-trilha-noturna-no-parque-do-inga-podem-ser-feitas-nesta-quarta-feira-21/43328','Inscrições para trilha noturna no Parque do Ingá podem ser feitas nesta quarta-feira, 21'],
  'apucarana.pr.gov.br'=>['/site/prefeitura-de-apucarana-faz-retirada-preventiva-de-eucalipto-com-risco-de-queda-ao-lado-do-espaco-das-feiras/','Prefeitura de Apucarana faz retirada preventiva de eucalipto com risco de queda'],
  'parana.pr.gov.br'=>['/aen/Noticia/Parana-ganha-nova-tecnologia-de-fundicao-de-aluminio','Paraná ganha nova tecnologia de fundição de alumínio em fábrica de motores']];
 foreach($map as $host=>$pair)if(str_contains($url,$host))return ['status'=>200,'type'=>'text/html; charset=utf-8','body'=>'<html><head><title>'.htmlspecialchars($pair[1]).'</title></head><body><article><h2><a href="https://'.(str_starts_with($host,'maringa')?'www.':(str_starts_with($host,'apucarana')?'www.':(str_starts_with($host,'parana')?'www.':''))).$host.$pair[0].'">'.htmlspecialchars($pair[1]).'</a></h2></article></body></html>'.str_repeat(' ',400)];
 return new WP_Error('unknown');
}
class WP_Error {public function __construct($msg='error'){} }
function is_wp_error($e){return $e instanceof WP_Error;}
function wp_remote_retrieve_response_code($r){return $r['status']??0;}
function wp_remote_retrieve_body($r){return $r['body']??'';}
function wp_remote_retrieve_header($r,$header){return $r['type']??'';}
function fetch_feed($url){return new WP_Error('simulate fallback');}
function wp_parse_url($url,$component=-1){return parse_url($url,$component);}
function sanitize_text_field($s){return trim(strip_tags($s));}
function wp_strip_all_tags($s){return strip_tags($s);}function esc_url_raw($s){return $s;}
function esc_url($s){return $s;} function wp_kses_post($s){return $s;}function esc_html($s){return htmlspecialchars((string)$s);}
function wp_date($format,$when){return date($format,$when);}
function remove_accents($s){return iconv('UTF-8','ASCII//TRANSLIT//IGNORE',$s)?:$s;}
function wp_insert_post($args,$wp_error=false){
 $id=count($GLOBALS['posts'])+1;$GLOBALS['posts'][$id]=$args;return $id;
}
function get_post_meta($id,$key,$single=true){return $GLOBALS['meta'][$id][$key]??'';}
function update_post_meta($id,$key,$value){$GLOBALS['meta'][$id][$key]=$value;}
class MockDB{public $postmeta="wp_postmeta";function prepare($query,...$args){return (string)end($args);}function get_var($fp){foreach($GLOBALS['meta'] as $id=>$meta)if(($meta['_pra_source_fingerprint']??'')===$fp)return $id;return null;}}
$GLOBALS['wpdb']=new MockDB;
@mkdir(ABSPATH.'wp-includes',0777,true);
// Source fixture only: the test does not call remote network or WordPress install.
require_once __DIR__.'/../wp-content/plugins/portal-regional-agentes/includes/class-pra-official.php';
function check($name,$ok){if(!$ok){fwrite(STDERR,"FAIL $name\n");exit(1);}echo "OK $name\n";}
$valid=PR_Official::normalize_manual_item('maringa','https://www.maringa.pr.gov.br/noticias/acao-de-formacao-para-a-populacao/43327','Maringá realiza ação de formação para a população');
check('manual fallback accepts official Maringa URL',is_array($valid)&&str_contains($valid['link'],'maringa.pr.gov.br'));
$valid=PR_Official::normalize_manual_item('arapongas','https://arapongas.atende.net/cidadao/noticia/prefeitura-anuncia-agenda-de-eventos','Prefeitura divulga programação cultural em Arapongas');
check('manual fallback accepts official Arapongas URL',is_array($valid)&&str_contains($valid['link'],'arapongas.atende.net'));
check('manual fallback rejects untrusted host',PR_Official::normalize_manual_item('maringa','https://www.maringa.pr.gov.br.evil.example/noticias/slug/12345','Um título qualquer de notícia verdadeira oficial')===null);
check('manual fallback rejects HTTP',PR_Official::normalize_manual_item('arapongas','http://arapongas.atende.net/cidadao/noticia/prefeitura-anuncia-agenda-de-eventos','Prefeitura divulga programação cultural em Arapongas')===null);
check('manual fallback rejects inadequate title',PR_Official::normalize_manual_item('arapongas','https://arapongas.atende.net/cidadao/noticia/prefeitura-anuncia-agenda-de-eventos','Leia mais')===null);
PR_Official::collect(true);
$r=get_option('pra_official_report');$h=get_option('pra_official_health');
check('all five sources created pending',count($GLOBALS['posts'])===5 && $r['created']===5);
check('city provenance stored',in_array('Arapongas',array_column($GLOBALS['meta'],'_pra_city'),true));
check('source health recorded',count($h)===5 && array_reduce($h,fn($c,$s)=>$c&&($s['last_success']??0)>0,true));
foreach($GLOBALS['posts'] as $post)check('pending only',($post['post_status']??'')==='pending');
PR_Official::collect(true);$r=get_option('pra_official_report');check('duplicate suppression',$r['created']===0 && $r['duplicates']===5);
$prior=$h['maringa']['last_success'];$GLOBALS['blocks']=['maringa.pr.gov.br'];
PR_Official::collect(true);$h=get_option('pra_official_health');$r=get_option('pra_official_report');
check('failed source preserves last success',$h['maringa']['last_success']===$prior);
check('failed source reports error',str_contains($r['sources']['maringa']['status'],'Erro'));

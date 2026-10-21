<?php
/** Extrator conservador de LINKS e TÍTULOS; não extrai reportagens. Pode ser testado sem WordPress. */
if (!defined('ABSPATH')) { exit; }
final class PR_Source_Parser {
  private const SOURCES = [
    'arapongas' => ['host'=>'arapongas.atende.net','path'=>'~^/cidadao/noticia/(?!categoria/)[a-z0-9-]+/?$~i'],
    'londrina' => ['host'=>'blog.londrina.pr.gov.br','path'=>'~^/$~', 'query'=>'p'],
    'maringa'  => ['host'=>'www.maringa.pr.gov.br','path'=>'~^/noticias/[a-z0-9-]+/[0-9]+/?$~i'],
    'apucarana'=> ['host'=>'www.apucarana.pr.gov.br','path'=>'~^/site/[a-z0-9-]+/?$~i'],
    'aen'      => ['host'=>'www.parana.pr.gov.br','path'=>'~^/aen/Noticia/[a-z0-9-]+/?$~i'],
  ];
  public static function canonical(string $href, string $kind): string {
    if (!isset(self::SOURCES[$kind])) return '';
    $s=self::SOURCES[$kind]; $href=html_entity_decode(trim($href), ENT_QUOTES|ENT_HTML5, 'UTF-8');
    if (str_starts_with($href,'/')) $href='https://'.$s['host'].$href;
    if (!str_starts_with(strtolower($href),'https://')) return '';
    $parts=parse_url($href);
    if (!is_array($parts) || strtolower((string)($parts['host']??''))!==$s['host'] || strtolower((string)($parts['scheme']??''))!=='https' || isset($parts['user']) || isset($parts['pass'])) return '';
    $path=(string)($parts['path']??'/');
    if (!preg_match($s['path'],$path)) return '';
    if ($kind==='londrina') {
      parse_str((string)($parts['query']??''),$qs);
      if (!isset($qs['p']) || !ctype_digit((string)$qs['p']) || (int)$qs['p']<1) return '';
      return 'https://'.$s['host'].'/?p='.(int)$qs['p'];
    }
    return 'https://'.$s['host'].rtrim($path,'/');
  }
  public static function extract(string $html,string $kind,int $limit=14): array {
    if (!isset(self::SOURCES[$kind]) || strlen($html)>2400000) return [];
    $limit=max(1,min($limit,30)); $links=[];
    if (class_exists('DOMDocument')) {
      $dom=new DOMDocument('1.0','UTF-8');$prev=libxml_use_internal_errors(true);
      try {$ok=$dom->loadHTML('<?xml encoding="utf-8"?>'.$html,LIBXML_NONET|LIBXML_NOERROR|LIBXML_NOWARNING);}
      finally {libxml_clear_errors();libxml_use_internal_errors($prev);}
      if ($ok) {
        $xp=new DOMXPath($dom);$anchors=$xp->query('//a[@href]');
        if ($anchors!==false)foreach($anchors as $a){
          $url=self::canonical((string)$a->getAttribute('href'),$kind);if (!$url || isset($links[$url]))continue;
          $title=self::clean((string)$a->textContent);
          if (self::bad_title($title)) {
            $ancestor=$a->parentNode;
            for($i=0;$i<3 && $ancestor;$i++,$ancestor=$ancestor->parentNode){
              if ($ancestor instanceof DOMElement && in_array(strtolower($ancestor->tagName),['h2','h3','h4'],true)){$title=self::clean((string)$ancestor->textContent);break;}
            }
          }
          if (self::bad_title($title))continue;
          $links[$url]=['title'=>$title,'link'=>$url];if(count($links)>=$limit)break;
        }
      }
    }
    if (!$links && preg_match_all('~<a\b[^>]*\bhref\s*=\s*(["\'])(.*?)\1[^>]*>(.*?)</a>~isu',$html,$found,PREG_SET_ORDER)){
      foreach(array_slice($found,0,2500) as $a){
        $url=self::canonical($a[2],$kind);if(!$url || isset($links[$url]))continue;
        $title=self::clean(strip_tags($a[3]));if(self::bad_title($title))continue;
        $links[$url]=['title'=>$title,'link'=>$url];if(count($links)>=$limit)break;
      }
    }
    return array_values($links);
  }
  private static function clean(string $text): string {
    $text=html_entity_decode($text,ENT_QUOTES|ENT_HTML5,'UTF-8');
    return trim((string)preg_replace('/\s+/u',' ',$text));
  }
  private static function bad_title(string $text): bool {
    $t=mb_strtolower($text,'UTF-8');
    return mb_strlen($text)<23 || mb_strlen($text)>205 || in_array($t,['leia mais','ver mais','compartilhe','notícias','aviso de pauta','noticias recentes'],true);
  }
}

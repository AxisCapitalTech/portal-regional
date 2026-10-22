<?php
/** Conservative robots.txt policy reader: read-only, never overrides exclusions. */
if(!defined('ABSPATH'))exit;
final class PR_Official_Robots {
  private static function normalize(string $value):string {
    return preg_replace_callback('/%([0-9a-f]{2})/i',static function($m){
      $char=chr(hexdec($m[1]));
      return preg_match('/[A-Za-z0-9._~-]/',$char)?$char:'%'.strtoupper($m[1]);
    },$value);
  }
  public static function parse(string $robots,string $path,string $ua='PortalRegionalTriagem'):array {
    $path=self::normalize($path);
    $groups=[];$agents=[];$rules=[];$sitemaps=[];$inRules=false;
    $flush=function()use(&$groups,&$agents,&$rules,&$inRules){
      if($agents)$groups[]=['agents'=>$agents,'rules'=>$rules];
      $agents=[];$rules=[];$inRules=false;
    };
    foreach(preg_split('/\r\n|\r|\n/',$robots) as $line){
      $line=trim(preg_replace('/\s+#.*$/','',$line));
      if($line===''||!preg_match('/^([\w-]+):\s*(.*)$/',$line,$m))continue;
      $key=strtolower($m[1]);$value=trim($m[2]);
      if($key==='sitemap'){
        if(filter_var($value,FILTER_VALIDATE_URL))$sitemaps[]=$value;
        continue;
      }
      if($key==='user-agent'){
        if($inRules)$flush();
        $agents[]=strtolower($value);
        continue;
      }
      if(!$agents)continue;
      if(in_array($key,['allow','disallow','crawl-delay'],true)){
        $rules[]=[$key,$value];$inRules=true;
      }
    }
    $flush();
    $matching=[];
    foreach($groups as $g){
      foreach($g['agents'] as $agent){
        if($agent==='*'||str_contains(strtolower($ua),$agent)){
          $matching[]=['score'=>$agent==='*'?0:strlen($agent),'rules'=>$g['rules']];
        }
      }
    }
    $score=$matching?max(array_column($matching,'score')):-1;
    $rules=[];
    foreach($matching as $entry)if($entry['score']===$score)$rules=array_merge($rules,$entry['rules']);
    $allowed=true;$length=-1;$delay=0;
    foreach($rules as [$kind,$pattern]){
      if($kind==='crawl-delay'){$delay=max($delay,(int)ceil((float)$pattern));continue;}
      if($pattern==='')continue;
      $pattern=self::normalize($pattern);
      $end=str_ends_with($pattern,'$');
      $literal=$end?substr($pattern,0,-1):$pattern;
      $regex='~^'.str_replace('\\*','.*',preg_quote($literal,'~')).($end?'$':'').'~';
      if(!preg_match($regex,$path))continue;
      $l=strlen(str_replace(['*','$'],'',$pattern));
      if($l>$length||($l===$length&&$kind==='allow')){$allowed=$kind==='allow';$length=$l;}
    }
    return ['allowed'=>$allowed,'delay'=>$delay,'sitemaps'=>array_values(array_unique($sitemaps))];
  }
  /**
   * Only children of a <sitemapindex> are returned. Never follows arbitrary XML,
   * external domains, nested indexes or more than two child files per run.
   */
  public static function sitemap_children(string $xml,int $now):array {
    if(strlen($xml)>2000000||preg_match('/<!DOCTYPE|<!ENTITY/i',$xml))return [];
    $prior=libxml_use_internal_errors(true);
    try{$root=simplexml_load_string($xml,'SimpleXMLElement',LIBXML_NONET);}
    finally{libxml_clear_errors();libxml_use_internal_errors($prior);}
    if(!$root||$root->getName()!=='sitemapindex')return [];
    $out=[];
    foreach($root->sitemap??[] as $node){
      $url=trim((string)$node->loc);
      if($url===''||strlen($url)>500)continue;
      $updated=0;$batch=0;
      if(isset($node->lastmod)){
        $updated=strtotime((string)$node->lastmod);
        if($updated && ($updated<$now-48*3600||$updated>$now+3600))continue;
      }
      if(!$updated && preg_match('/(\d{4})_(\d{2})_(\d{2})_(\d+)/',basename((string)parse_url($url,PHP_URL_PATH)),$m)){
        if(checkdate((int)$m[2],(int)$m[3],(int)$m[1]))$updated=(int)strtotime($m[1].'-'.$m[2].'-'.$m[3].' UTC');
        $batch=(int)$m[4];
      }
      $out[]=['url'=>$url,'date'=>$updated,'batch'=>$batch];
    }
    usort($out,static fn($a,$b)=>($b['date']<=>$a['date'])?:($b['batch']<=>$a['batch']));
    return array_column(array_slice($out,0,2),'url');
  }
  public static function sitemap_urls(string $xml,string $kind,int $now):array {
    if(strlen($xml)>2000000||preg_match('/<!DOCTYPE|<!ENTITY/i',$xml))return [];
    $prior=libxml_use_internal_errors(true);
    try{$root=simplexml_load_string($xml,'SimpleXMLElement',LIBXML_NONET);}
    finally{libxml_clear_errors();libxml_use_internal_errors($prior);}
    if(!$root)return [];
    $out=[];
    foreach($root->url??[] as $row){
      $url=PR_Source_Parser::canonical((string)$row->loc,$kind);
      if(!$url||!isset($row->lastmod))continue;
      $timestamp=strtotime((string)$row->lastmod);
      if(!$timestamp||$timestamp<$now-48*3600||$timestamp>$now+3600)continue;
      $out[$url]=['link'=>$url,'lastmod'=>$timestamp];
      if(count($out)>=4)break;
    }
    return array_values($out);
  }
}

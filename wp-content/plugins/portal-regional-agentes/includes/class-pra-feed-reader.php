<?php
/** RSS/Atom parsing of an already fetched response: never performs network I/O. */
if(!defined('ABSPATH'))exit;
final class PR_Feed_Reader {
  public static function items(string $xml):array {
    if(strlen($xml)>2000000||preg_match('/<!DOCTYPE|<!ENTITY/i',$xml))throw new RuntimeException('RSS/XML não permitido');
    $prior=libxml_use_internal_errors(true);
    try{$root=simplexml_load_string($xml,'SimpleXMLElement',LIBXML_NONET);}
    finally{libxml_clear_errors();libxml_use_internal_errors($prior);}
    if(!$root||!in_array($root->getName(),['rss','feed','RDF'],true))throw new RuntimeException('RSS/Atom inválido');
    $out=[];
    $atom=$root->getName()==='feed';
    $nodes=$root->xpath($atom?'/*[local-name()="feed"]/*[local-name()="entry"]':'//*[local-name()="item"]');
    foreach(array_slice($nodes?:[],0,12) as $node){
      $field=function(string $name)use($node):string{$v=$node->xpath('./*[local-name()="'.$name.'"]');return $v?(string)$v[0]:'';};
      $link=$field('link');
      if($atom){foreach($node->xpath('./*[local-name()="link"]') as $candidate){if(!isset($candidate['rel'])||(string)$candidate['rel']==='alternate'){$link=(string)$candidate['href'];break;}}}
      $date=$atom?$field('published'):($field('pubDate')?:$field('date'));
      $out[]=['title'=>$field('title'),'link'=>$link,'date'=>$date?(int)strtotime($date):0,'description'=>$field($atom?'summary':'description')];
    }
    return $out;
  }
}

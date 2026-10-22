<?php
/** Publication dates only; unknown/conflicting dates stay unknown. */
if(!defined('ABSPATH'))exit;
final class PR_Official_Freshness {
  public static function parse(string $value):array {
    $unknown=['date'=>0,'has_time'=>false];
    $value=trim($value);
    if(!preg_match('/^\d{4}-\d{2}-\d{2}(?:[T ]\d{2}:\d{2}(?::\d{2})?(?:Z|[+-]\d{2}:?\d{2})?)?$/D',$value))return $unknown;
    try {
      $date=new DateTimeImmutable($value,function_exists('wp_timezone')?wp_timezone():new DateTimeZone('UTC'));
      $errors=DateTimeImmutable::getLastErrors();
      if($errors && ($errors['warning_count']||$errors['error_count']))return $unknown;
      return ['date'=>max(0,$date->getTimestamp()),'has_time'=>strlen($value)>10];
    }catch(Exception $e){return $unknown;}
  }
  private static function resolve(array $values):array {
    $dates=[];
    foreach($values as $value){$info=self::parse($value);if($info['date'])$dates[$info['date']]=$info;}
    return count($dates)===1?reset($dates):['date'=>0,'has_time'=>false];
  }
  private static function json_dates($node,array &$values):void {
    if(!is_array($node))return;
    $types=(array)($node['@type']??[]);
    if(array_intersect($types,['Article','NewsArticle','BlogPosting','ReportageNewsArticle']) && is_string($node['datePublished']??null))$values[]=$node['datePublished'];
    foreach($node as $value)if(is_array($value))self::json_dates($value,$values);
  }
  public static function info_from_html(string $html):array {
    $unknown=['date'=>0,'has_time'=>false];
    if(strlen($html)>2400000||!class_exists('DOMDocument'))return $unknown;
    $dom=new DOMDocument('1.0','UTF-8');$prior=libxml_use_internal_errors(true);
    try{$ok=$dom->loadHTML('<?xml encoding="utf-8"?>'.$html,LIBXML_NONET|LIBXML_NOERROR|LIBXML_NOWARNING);}
    finally{libxml_clear_errors();libxml_use_internal_errors($prior);}
    if(!$ok)return $unknown;
    $xp=new DOMXPath($dom);$primary=[];
    foreach($xp->query('//meta[@content]') as $tag){
      $name=strtolower($tag->getAttribute('property')?:($tag->getAttribute('name')?:$tag->getAttribute('itemprop')));
      if(in_array($name,['article:published_time','datepublished','pubdate','dc.date.issued'],true))$primary[]=$tag->getAttribute('content');
    }
    foreach($xp->query('//script[@type="application/ld+json"]') as $script)self::json_dates(json_decode($script->textContent,true),$primary);
    // Explicit publication metadata wins over incidental clocks in the page.
    if($primary)return self::resolve($primary);
    $values=[];
    foreach($xp->query('//time[@datetime][ancestor::article or ancestor::main][not(ancestor::aside or ancestor::nav or ancestor::footer)]') as $tag)$values[]=$tag->getAttribute('datetime');
    return self::resolve($values);
  }
  public static function date_from_html(string $html):int {return self::info_from_html($html)['date'];}
}

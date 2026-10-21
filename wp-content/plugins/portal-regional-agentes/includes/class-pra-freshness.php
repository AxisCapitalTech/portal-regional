<?php
/** Parses a publication date from official article HTML only; does not guess dates. */
if(!defined('ABSPATH'))exit;
final class PR_Official_Freshness {
  public static function date_from_html(string $html):int {
    if(strlen($html)>2400000)return 0;
    if(!class_exists('DOMDocument'))return 0;
    $dom=new DOMDocument('1.0','UTF-8');
    $prior=libxml_use_internal_errors(true);
    try{
      $ok=$dom->loadHTML('<?xml encoding="utf-8"?>'.$html,LIBXML_NONET|LIBXML_NOERROR|LIBXML_NOWARNING);
    } finally {libxml_clear_errors();libxml_use_internal_errors($prior);}
    if(!$ok)return 0;
    $xp=new DOMXPath($dom);
    $tags=$xp->query('//meta[@content] | //time[@datetime]');
    if(!$tags)return 0;
    foreach($tags as $tag){
      $name=strtolower($tag->getAttribute('property')?:$tag->getAttribute('name'));
      $candidate='';
      if($tag->tagName==='time')$candidate=$tag->getAttribute('datetime');
      elseif(in_array($name,['article:published_time','datepublished','pubdate','datecreated','dc.date.issued'],true))
        $candidate=$tag->getAttribute('content');
      if($candidate==='')continue;
      // Require an unambiguous ISO date; do not mistake sidebar dates for publication dates.
      if(!preg_match('/^\\d{4}-\\d{2}-\\d{2}(?:[T ]\\d{2}:\\d{2}(?::\\d{2})?(?:Z|[+-]\\d{2}:?\\d{2})?)?$/',$candidate))continue;
      $date=strtotime($candidate);
      if($date!==false && $date>0)return (int)$date;
    }
    return 0;
  }
}

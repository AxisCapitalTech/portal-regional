<?php
define('ABSPATH',__DIR__.'/');
require_once __DIR__.'/../wp-content/plugins/portal-regional-agentes/includes/class-pra-freshness.php';
function check_fresh($ok,$label){if(!$ok){fwrite(STDERR,"FAIL $label\n");exit(1);}echo "OK $label\n";}
$date='2026-10-21T10:35:00-03:00';
$meta='<html><head><meta content="'.$date.'" property="article:published_time"></head><body></body></html>';
check_fresh(PR_Official_Freshness::date_from_html($meta)===strtotime($date),'published_time metadata (attribute order)');
$time='<html><body><article><time datetime="'.$date.'">21/10/2026</time></article></body></html>';
check_fresh(PR_Official_Freshness::date_from_html($time)===strtotime($date),'time datetime');
check_fresh(PR_Official_Freshness::date_from_html('<html><body>Sem data</body></html>')===0,'unknown date stays unknown');
check_fresh(PR_Official_Freshness::date_from_html('<html><head><meta property="other" content="'.$date.'"></head></html>')===0,'unrelated metadata ignored');
echo "OK extraction of dated articles complete\n";
function wp_timezone(){return new DateTimeZone('America/Sao_Paulo');}
$side='<aside><time datetime="2020-01-01">antiga</time></aside>';
check_fresh(PR_Official_Freshness::date_from_html($side.$meta)===strtotime($date),'sidebar before metadata cannot override publication');
check_fresh(PR_Official_Freshness::date_from_html($side)===0,'sidebar alone is unknown');
check_fresh(PR_Official_Freshness::date_from_html('<main>'.$side.'</main>')===0,'nested sidebar excluded');
check_fresh(PR_Official_Freshness::date_from_html($meta.'<meta itemprop="datePublished" content="2020-01-01">')===0,'conflicting metadata rejected');
check_fresh(PR_Official_Freshness::date_from_html('<script type="application/ld+json">{"@type":"NewsArticle","datePublished":"'.$date.'"}</script>')===strtotime($date),'JSON-LD publication date');
$info=PR_Official_Freshness::info_from_html('<article><time datetime="2026-10-21">data</time></article>');
check_fresh($info['date']===strtotime('2026-10-21T00:00:00-03:00') && !$info['has_time'],'date only uses WordPress timezone and no invented time');
check_fresh(PR_Official_Freshness::parse('2026-10-21T10:35:00')['date']===strtotime($date),'datetime without offset uses WordPress timezone');
check_fresh(PR_Official_Freshness::parse('2026-02-31')['date']===0,'invalid calendar date rejected');

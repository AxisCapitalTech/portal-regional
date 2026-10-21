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

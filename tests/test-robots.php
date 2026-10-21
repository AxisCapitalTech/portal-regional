<?php
define('ABSPATH',__DIR__.'/');
if(!function_exists('mb_strlen'))function mb_strlen($x,...$args){return strlen($x);}
require_once __DIR__.'/../wp-content/plugins/portal-regional-agentes/includes/class-pra-source-parser.php';
require_once __DIR__.'/../wp-content/plugins/portal-regional-agentes/includes/class-pra-robots.php';
function assert_ok($v,$name){if(!$v){fwrite(STDERR,"FAIL $name\n");exit(1);}echo "OK $name\n";}
$txt="User-agent: *\nDisallow: /private\nCrawl-delay: 10\nAllow: /private/public\nSitemap: https://arapongas.atende.net/sitemap.xml\n";
assert_ok(!PR_Official_Robots::parse($txt,'/private/secret')['allowed'],'robots disallow');
assert_ok(PR_Official_Robots::parse($txt,'/private/public/item')['allowed'],'robots longest allow');
assert_ok(PR_Official_Robots::parse($txt,'/public')['delay']===10,'robots crawl delay 10');
assert_ok(count(PR_Official_Robots::parse($txt,'/')['sitemaps'])===1,'robots sitemap declared');
$today=time();$new=gmdate('c',$today-1800);$old=gmdate('c',$today-9*86400);
$xml='<?xml version="1.0"?><urlset><url><loc>https://arapongas.atende.net/cidadao/noticia/agenda-cultural</loc><lastmod>'.$new.'</lastmod></url><url><loc>https://arapongas.atende.net/cidadao/noticia/antiga</loc><lastmod>'.$old.'</lastmod></url><url><loc>https://evil.example/cidadao/noticia/ataque</loc><lastmod>'.$new.'</lastmod></url></urlset>';
$out=PR_Official_Robots::sitemap_urls($xml,'arapongas',$today);
assert_ok(count($out)===1&&str_contains($out[0]['link'],'agenda-cultural'),'sitemap only fresh trusted 48h');
echo "OK robots and sitemap tests completed\n";

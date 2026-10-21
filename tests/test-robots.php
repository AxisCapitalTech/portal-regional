<?php
define('ABSPATH',__DIR__.'/');
if (!function_exists('mb_strlen')) { function mb_strlen($x,...$args) { return strlen($x); } }
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
$index='<?xml version="1.0"?><sitemapindex><sitemap><loc>https://arapongas.atende.net/sitemap/noticias.xml</loc><lastmod>'.$new.'</lastmod></sitemap><sitemap><loc>https://arapongas.atende.net/sitemap/antigo.xml</loc><lastmod>'.$old.'</lastmod></sitemap></sitemapindex>';
$files=PR_Official_Robots::sitemap_children($index,$today);
assert_ok(count($files)===1&&$files[0]==='https://arapongas.atende.net/sitemap/noticias.xml','sitemapindex children only recent');
assert_ok(PR_Official_Robots::sitemap_children($xml,$today)===[],'urlset not mistaken for sitemapindex');
assert_ok(PR_Official_Robots::sitemap_children(str_repeat('z',2000001),$today)===[],'oversized sitemap refused');
echo "OK robots and sitemap tests completed\n";

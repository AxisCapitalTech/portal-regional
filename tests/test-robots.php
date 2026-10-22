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

assert_ok(PR_Official_Robots::parse("User-agent: *\nCrawl-delay: 120\n",'/')['delay']===120,'crawl-delay never shortened');
$group="User-agent: *\nUser-agent: PortalRegionalTriagem\nDisallow: /private\n\nUser-agent: OtherBot\nAllow: /\n";
assert_ok(!PR_Official_Robots::parse($group,'/private')['allowed'],'specific agent in shared group remains restrictive');
$groups="User-agent: *\nUser-agent: PortalRegionalTriagem\nDisallow: /private\nUser-agent: PortalRegionalTriagem\nDisallow: /secret\n";
assert_ok(!PR_Official_Robots::parse($groups,'/private')['allowed'],'merge all equally specific groups');
assert_ok(!PR_Official_Robots::parse("User-agent: *\nDisallow: /%7Ejoe",'/~joe')['allowed'],'percent encoded unreserved matches plain path');
assert_ok(!PR_Official_Robots::parse("User-agent: *\nDisallow: /~joe",'/%7ejoe')['allowed'],'percent encoded request normalized');
$index='<sitemapindex>';
foreach(['2026_10_20_1','2026_10_21_1','2026_10_22_1','2026_10_22_2'] as $name)$index.='<sitemap><loc>https://arapongas.atende.net/'.$name.'.xml</loc></sitemap>';
$index.='</sitemapindex>';
$files=PR_Official_Robots::sitemap_children($index,strtotime('2026-10-22T12:00:00Z'));
assert_ok(str_contains($files[0],'2026_10_22_2')&&str_contains($files[1],'2026_10_22_1'),'ascending sitemap index selects newest date and batch');

<?php
/** Offline wp_head output: no HTTP or published fixtures. */
define('ABSPATH',__DIR__.'/');$GLOBALS['view']='post';$GLOBALS['hooks']=[];$GLOBALS['filters']=[];
function add_action($hook,$cb,...$args){$GLOBALS['hooks'][$hook][]=$cb;}
function add_filter($hook,$cb,...$args){$GLOBALS['filters'][$hook][]=$cb;}
function is_singular($type=null){return in_array($GLOBALS['view'],['post','page'],true)&&(!$type||$type===$GLOBALS['view']);}
function is_front_page(){return $GLOBALS['view']==='front';}function is_home(){return is_front_page();}
function is_category(){return $GLOBALS['view']==='category';}function is_admin(){return false;}function is_search(){return false;}
function get_queried_object_id(){return 17;}function get_query_var($key){return 0;}
function wp_get_canonical_url($id){return 'https://example.test/'.$GLOBALS['view'].'/';}
function home_url($path){return 'https://example.test'.$path;}
function get_category_link($id){return home_url('/category/londrina/');}
function get_the_excerpt($id){return $GLOBALS['view']==='page'?'Descrição da página de teste':'Descrição da reportagem de teste';}
function get_post_field(...$args){return '';}
function wp_strip_all_tags($s){return strip_tags($s);}function wp_trim_words($s,...$args){return $s;}
function category_description($id){return '<p>Informações locais de Londrina</p>';}
function single_cat_title(...$args){return 'Londrina';}
function regional_public_brand(){return 'PR1 News';}function regional_public_tagline(){return 'Jornalismo regional';}
function get_the_title($post){return $GLOBALS['view']==='page'?'Contato':'Reportagem de teste';}
function get_theme_mod($key,$default=0){return $GLOBALS['logo']??0;}
function wp_get_attachment_image_url($id,$size){return home_url('/configured-logo.png');}
function get_template_directory(){return __DIR__.'/../wp-content/themes/portal-regional-tema';}
function get_template_directory_uri(){return home_url('/theme');}
function get_the_post_thumbnail_url(...$args){return false;}
function esc_url_raw($s){return $s;}function esc_url($s){return htmlspecialchars($s,ENT_QUOTES);}
function esc_attr($s){return htmlspecialchars($s,ENT_QUOTES);}
function get_post_status($id){return 'publish';}
function get_post($id){return (object)['ID'=>17,'post_author'=>1];}
function get_permalink($post){return home_url('/post/');}
function get_post_time(...$args){return '2026-10-22T10:00:00Z';}
function get_post_modified_time(...$args){return '2026-10-22T10:00:00Z';}
function get_the_author_meta(...$args){return 'Fixture local';}
function get_the_category(...$args){return [];}
function wp_json_encode($v,$flags=0){return json_encode($v,$flags);}
require get_template_directory().'/includes/pr1-news-seo.php';
function verify($ok,$name){if(!$ok){fwrite(STDERR,"FAIL $name\n");exit(1);}echo "OK $name\n";}
function render_head(){ob_start();foreach($GLOBALS['hooks']['wp_head'] as $cb)$cb();return ob_get_clean();}
foreach(['tag','author','date','404'] as $view){$GLOBALS['view']=$view;$html=render_head();verify(!str_contains($html,'rel="canonical"')&&!str_contains($html,'property="og:url"'),'no home fallback output: '.$view);}
$GLOBALS['view']='post';$html=render_head();
preg_match('~<script type="application/ld\+json">(.*?)</script>~s',$html,$m);$json=json_decode($m[1]??'',true);
verify(($json['publisher']['logo']['@type']??'')==='ImageObject'&&str_contains($json['publisher']['logo']['url'],'pr1-wordmark-provisorio.png'),'NewsArticle emits valid theme publisher.logo');
$fallback=get_template_directory().'/assets/pr1-wordmark-provisorio.png';
$dimensions=getimagesize($fallback);
verify(is_array($dimensions)&&$dimensions[0]<=600&&$dimensions[1]<=60,'publisher logo fallback fits at most 600x60');
verify(str_contains($html,'pr1-social-provisorio.png'),'OG fallback is separate 1200x630 asset');
$GLOBALS['logo']=1;verify(pr1_news_publisher_logo()===home_url('/configured-logo.png'),'configured logo takes precedence');
$GLOBALS['view']='category';$html=render_head();verify(str_contains($html,'Notícias de Londrina | PR1 News')&&str_contains($html,'Informações locais de Londrina'),'category has own title and description');
$GLOBALS['view']='page';$html=render_head();verify(str_contains($html,'Contato | PR1 News')&&str_contains($html,'Descrição da página de teste'),'page has own title and description');
$text=($GLOBALS['filters']['robots_txt'][0])('',true);
verify(str_contains($text,'Sitemap: https://example.test/wp-sitemap.xml')&&str_contains($text,'/news-sitemap.xml'),'both sitemaps advertised');
$text=($GLOBALS['filters']['robots_txt'][0])($text,true);verify(substr_count($text,'/wp-sitemap.xml')===1,'core sitemap not duplicated');
define('WPSEO_VERSION','fixture');verify(render_head()==='','external SEO suppresses all native metadata');

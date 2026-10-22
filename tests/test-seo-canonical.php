<?php
/** Offline behavioral tests; no HTTP, no posts created. */
define('ABSPATH',__DIR__.'/');
$GLOBALS['hooks']=[];$GLOBALS['removed']=[];$GLOBALS['view']='post';$GLOBALS['paged']=1;
function add_action($hook,$cb,...$args){$GLOBALS['hooks'][$hook][]=$cb;}
function add_filter(...$args){}
function remove_action($hook,$cb){$GLOBALS['removed'][]=[$hook,$cb];}
function is_singular(...$args){return $GLOBALS['view']==='post';}
function is_front_page(){return $GLOBALS['view']==='front';}
function is_home(){return in_array($GLOBALS['view'],['front','blog'],true);}
function is_category(){return $GLOBALS['view']==='category';}
function get_queried_object_id(){return 17;}
function get_query_var($key){return $GLOBALS['paged'];}
function wp_get_canonical_url($id){return 'https://example.test/article/2/';}
function get_permalink($id){return 'https://example.test/blog/';}
function get_option($key){return 27;}
function home_url($path){return 'https://example.test'.$path;}
function get_pagenum_link($page){return 'https://example.test/blog/page/'.$page.'/';}
function get_category_link($id){return 'https://example.test/category/regiao/';}
require __DIR__.'/../wp-content/themes/portal-regional-tema/includes/pr1-news-seo.php';
function expect_url($want,$label){
 if(pr1_news_canonical_url()!==$want){fwrite(STDERR,"FAIL $label\n");exit(1);}
 echo "OK $label\n";
}
expect_url('https://example.test/article/2/','multipage article canonical from WordPress');
$GLOBALS['view']='front';expect_url('https://example.test/','front canonical');
$GLOBALS['view']='blog';expect_url('https://example.test/blog/','separate posts page canonical');
$GLOBALS['paged']=2;expect_url('https://example.test/blog/page/2/','paginated archive canonical');
$GLOBALS['paged']=1;$GLOBALS['view']='category';expect_url('https://example.test/category/regiao/','category canonical');
foreach($GLOBALS['hooks']['wp'] as $cb)$cb();
if($GLOBALS['removed']!==[['wp_head','rel_canonical']]){fwrite(STDERR,"FAIL duplicate core canonical\n");exit(1);}
echo "OK native SEO removes duplicate core canonical\n";
$GLOBALS['removed']=[];define('WPSEO_VERSION','test');
foreach($GLOBALS['hooks']['wp'] as $cb)$cb();
if($GLOBALS['removed']!==[]){fwrite(STDERR,"FAIL external SEO ownership\n");exit(1);}
echo "OK external SEO retains canonical ownership\n";

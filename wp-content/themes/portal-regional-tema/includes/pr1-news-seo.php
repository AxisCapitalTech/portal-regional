<?php
/**
 * PR1 News — metadados editoriais próprios (somente se não houver plugin SEO).
 * Não publica posts, não cria páginas e não altera dados editoriais.
 */
if(!defined('ABSPATH'))exit;
function pr1_news_seo_is_external():bool {
  return defined('WPSEO_VERSION') || defined('RANK_MATH_VERSION') || defined('AIOSEO_VERSION')
    || defined('SEOPRESS_VERSION') || defined('THE_SEO_FRAMEWORK_VERSION')
    || class_exists('WPSEO_Options',false) || class_exists('RankMath\\Helper',false)
    || class_exists('AIOSEO\\Plugin\\AIOSEO',false);
}
function pr1_news_description():string {
  if(is_singular()) {
    $text=(string)get_the_excerpt(get_queried_object_id());
    if($text==='')$text=wp_strip_all_tags((string)get_post_field('post_content',get_queried_object_id()));
    return wp_trim_words(wp_strip_all_tags($text),34,'…');
  }
  if(is_category()){
    $text=trim(wp_strip_all_tags((string)category_description(get_queried_object_id())));
    return $text!==''?wp_trim_words($text,34,'…'):'Notícias de '.single_cat_title('',false).' | '.regional_public_brand();
  }
  return function_exists('regional_public_tagline')?regional_public_tagline():(string)get_bloginfo('description');
}
function pr1_news_title():string {
  if(is_singular())return get_the_title(get_queried_object_id()).' | '.regional_public_brand();
  if(is_category())return 'Notícias de '.single_cat_title('',false).' | '.regional_public_brand();
  return regional_public_brand();
}
function pr1_news_theme_image(string $name,int $width,int $height):string {
  $file=get_template_directory().'/assets/'.$name;
  if(!is_file($file))return '';
  $size=getimagesize($file);
  if(!$size||$size[0]<$width||$size[1]<$height)return '';
  return esc_url_raw(get_template_directory_uri().'/assets/'.$name);
}
function pr1_news_publisher_logo():string {
  $id=(int)get_theme_mod('custom_logo',0);
  $url=$id?wp_get_attachment_image_url($id,'full'):false;
  return $url?esc_url_raw($url):pr1_news_theme_image('pr1-wordmark-provisorio.png',600,60);
}
function pr1_news_og_image():string {
  if(is_singular('post')){
    $img=get_the_post_thumbnail_url(get_queried_object_id(),'full');
    if($img)return esc_url_raw($img);
  }
  return pr1_news_theme_image('pr1-social-provisorio.png',1200,630);
}
function pr1_news_canonical_url():string {
  if(is_front_page()||is_home()||is_category()){
    $page=max(1,(int)get_query_var('paged'),(int)get_query_var('page'));
    if($page>1)return (string)get_pagenum_link($page);
    if(is_category())return (string)get_category_link(get_queried_object_id());
    if(is_home() && !is_front_page())return (string)get_permalink((int)get_option('page_for_posts'));
    return home_url('/');
  }
  if(is_singular())return (string)wp_get_canonical_url(get_queried_object_id());
  return '';
}
// WordPress emits rel_canonical too: keep one owner, preserving external SEO plugins.
add_action('wp',function(){
  if(!pr1_news_seo_is_external())remove_action('wp_head','rel_canonical');
});
add_action('wp_head',function(){
  if(pr1_news_seo_is_external()||is_admin()||is_search())return;
  $description=pr1_news_description();
  if($description!=='')echo '<meta name="description" content="'.esc_attr($description).'">'."\n";
  $isArticle=is_singular('post') && get_post_status(get_queried_object_id())==='publish';
  $title=pr1_news_title();
  $link=pr1_news_canonical_url();
  $og_image=pr1_news_og_image();
  if($link!=='')echo '<link rel="canonical" href="'.esc_url($link).'">'."\n";
  echo '<meta property="og:site_name" content="'.esc_attr(regional_public_brand()).'">'."\n";
  echo '<meta property="og:locale" content="pt_BR">'."\n";
  echo '<meta property="og:type" content="'.($isArticle?'article':'website').'">'."\n";
  echo '<meta property="og:title" content="'.esc_attr($title).'">'."\n";
  if($link!=='')echo '<meta property="og:url" content="'.esc_url($link).'">'."\n";
  if($description!=='')echo '<meta property="og:description" content="'.esc_attr($description).'">'."\n";
  echo '<meta name="twitter:card" content="summary_large_image">'."\n";
  echo '<meta name="twitter:title" content="'.esc_attr($title).'">'."\n";
  if($description!=='')echo '<meta name="twitter:description" content="'.esc_attr($description).'">'."\n";
  if($og_image){
    echo '<meta property="og:image" content="'.esc_url($og_image).'">'."\n";
    echo '<meta name="twitter:image" content="'.esc_url($og_image).'">'."\n";
  }
  if(!$isArticle)return;
  $post=get_post(get_queried_object_id());
  if(!$post)return;
  $image=get_the_post_thumbnail_url($post->ID,'full');
  $news=[
    '@context'=>'https://schema.org','@type'=>'NewsArticle',
    'mainEntityOfPage'=>['@type'=>'WebPage','@id'=>get_permalink($post)],
    'headline'=>get_the_title($post),
    'datePublished'=>get_post_time('c',true,$post),
    'dateModified'=>get_post_modified_time('c',true,$post),
    'author'=>['@type'=>'Person','name'=>get_the_author_meta('display_name',$post->post_author)],
    'publisher'=>['@type'=>'Organization','name'=>regional_public_brand(),'url'=>home_url('/')],
  ];
  $publisher_logo=pr1_news_publisher_logo();
  if($publisher_logo)$news['publisher']['logo']=['@type'=>'ImageObject','url'=>$publisher_logo];
  if($description!=='')$news['description']=$description;
  if($image)$news['image']=[$image];
  $trail=[
    ['@type'=>'ListItem','position'=>1,'name'=>'Início','item'=>home_url('/')]
  ];
  $cat=get_the_category($post->ID);
  if($cat){$trail[]=['@type'=>'ListItem','position'=>2,'name'=>$cat[0]->name,'item'=>get_category_link($cat[0])];}
  $trail[]=['@type'=>'ListItem','position'=>count($trail)+1,'name'=>get_the_title($post),'item'=>get_permalink($post)];
  $crumb=['@context'=>'https://schema.org','@type'=>'BreadcrumbList','itemListElement'=>$trail];
  foreach([$news,$crumb] as $payload){
    echo '<script type="application/ld+json">'.wp_json_encode($payload,
      JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT).'</script>'."\n";
  }
},5);
add_filter('wp_robots',function($robots){
  if(is_search()){$robots['noindex']=true;$robots['follow']=true;}
  return $robots;
});
add_action('init',function(){
  if(pr1_news_seo_is_external())return;
  add_rewrite_rule('^news-sitemap\\.xml$','index.php?pr1_news_sitemap=1','top');
});
add_filter('query_vars',function($vars){$vars[]='pr1_news_sitemap';return $vars;});
add_action('after_switch_theme',function(){
  delete_option('pr1_news_rewrites_v2');
  flush_rewrite_rules(false);
});
add_action('admin_init',function(){
  if(pr1_news_seo_is_external()||get_option('pr1_news_rewrites_v2')==='1')return;
  flush_rewrite_rules(false);
  update_option('pr1_news_rewrites_v2','1',false);
});
add_action('template_redirect',function(){
  if(!get_query_var('pr1_news_sitemap'))return;
  if(pr1_news_seo_is_external()){status_header(404);exit;}
  status_header(200);
  header('Content-Type: application/xml; charset=UTF-8');
  header('X-Robots-Tag: noindex, follow');
  $posts=get_posts([
    'post_type'=>'post','post_status'=>'publish','numberposts'=>300,
    'date_query'=>[['after'=>gmdate('Y-m-d H:i:s',time()-48*HOUR_IN_SECONDS),
      'column'=>'post_date_gmt','inclusive'=>true]],
    'orderby'=>'date','order'=>'DESC'
  ]);
  echo '<?xml version="1.0" encoding="UTF-8"?>'."\n";
  echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:news="http://www.google.com/schemas/sitemap-news/0.9">'."\n";
  foreach($posts as $post){
    if($post->post_status!=='publish')continue;
    echo '<url><loc>'.esc_xml(get_permalink($post)).'</loc><news:news><news:publication>';
    echo '<news:name>'.esc_xml(regional_public_brand()).'</news:name><news:language>pt</news:language>';
    echo '</news:publication><news:publication_date>'.esc_xml(get_post_time('c',true,$post)).'</news:publication_date>';
    echo '<news:title>'.esc_xml(get_the_title($post)).'</news:title></news:news></url>'."\n";
  }
  echo '</urlset>';
  exit;
});
add_filter('robots_txt',function($text,$public){
  if(!$public||pr1_news_seo_is_external())return $text;
  $core='Sitemap: '.home_url('/wp-sitemap.xml');
  if(strpos($text,$core)===false)$text=rtrim($text)."\n".$core;
  return rtrim($text)."\nSitemap: ".home_url('/news-sitemap.xml')."\n";
},10,2);

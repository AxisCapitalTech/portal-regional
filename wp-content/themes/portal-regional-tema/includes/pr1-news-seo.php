<?php
/**
 * PR1 News — metadados editoriais próprios (somente se não houver plugin SEO).
 * Não publica posts, não cria páginas e não altera dados editoriais.
 */
if(!defined('ABSPATH'))exit;
function pr1_news_seo_is_external():bool {
  return defined('WPSEO_VERSION') || defined('RANK_MATH_VERSION')
    || class_exists('WPSEO_Options') || class_exists('RankMath\\Helper',false);
}
function pr1_news_description():string {
  if(is_singular('post')) {
    $text=(string)get_the_excerpt(get_queried_object_id());
    if($text==='')$text=wp_strip_all_tags((string)get_post_field('post_content',get_queried_object_id()));
    return wp_trim_words(wp_strip_all_tags($text),34,'…');
  }
  return function_exists('regional_public_tagline')?regional_public_tagline():(string)get_bloginfo('description');
}
add_action('wp_head',function(){
  if(pr1_news_seo_is_external()||is_admin()||is_search())return;
  $description=pr1_news_description();
  if($description!=='')echo '<meta name="description" content="'.esc_attr($description).'">'."\n";
  $isArticle=is_singular('post') && get_post_status(get_queried_object_id())==='publish';
  $title=$isArticle?get_the_title(get_queried_object_id()):regional_public_brand();
  $link=$isArticle?get_permalink(get_queried_object_id()):home_url('/');
  echo '<meta property="og:type" content="'.($isArticle?'article':'website').'">'."\n";
  echo '<meta property="og:title" content="'.esc_attr($title).'">'."\n";
  echo '<meta property="og:url" content="'.esc_url($link).'">'."\n";
  if($description!=='')echo '<meta property="og:description" content="'.esc_attr($description).'">'."\n";
  echo '<meta name="twitter:card" content="summary_large_image">'."\n";
  echo '<meta name="twitter:title" content="'.esc_attr($title).'">'."\n";
  if($description!=='')echo '<meta name="twitter:description" content="'.esc_attr($description).'">'."\n";
  if(!$isArticle)return;
  $post=get_post(get_queried_object_id());
  if(!$post)return;
  $image=get_the_post_thumbnail_url($post->ID,'full');
  if($image){
    echo '<meta property="og:image" content="'.esc_url($image).'">'."\n";
    echo '<meta name="twitter:image" content="'.esc_url($image).'">'."\n";
  }
  $news=[
    '@context'=>'https://schema.org','@type'=>'NewsArticle',
    'mainEntityOfPage'=>['@type'=>'WebPage','@id'=>get_permalink($post)],
    'headline'=>get_the_title($post),
    'datePublished'=>get_post_time('c',true,$post),
    'dateModified'=>get_post_modified_time('c',true,$post),
    'author'=>['@type'=>'Person','name'=>get_the_author_meta('display_name',$post->post_author)],
    'publisher'=>['@type'=>'Organization','name'=>regional_public_brand(),'url'=>home_url('/')],
  ];
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
add_action('after_switch_theme',function(){flush_rewrite_rules(false);});
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
  return rtrim($text)."\nSitemap: ".home_url('/news-sitemap.xml')."\n";
},10,2);

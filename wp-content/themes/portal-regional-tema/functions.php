<?php
if (!defined('ABSPATH')) exit;
add_action('after_setup_theme', function () {
  load_theme_textdomain('portal-regional-tema', get_template_directory().'/languages');
  add_theme_support('title-tag'); add_theme_support('post-thumbnails'); add_theme_support('automatic-feed-links');
  add_theme_support('html5', ['search-form','gallery','caption','style','script']);
  register_nav_menus(['primary'=>'Menu principal']);
  add_image_size('regional-lead', 950, 520, true);
});
add_action('wp_enqueue_scripts', function () {
  wp_enqueue_style('regional-theme', get_stylesheet_uri(), [], '0.4.0');
});
add_action('widgets_init', function () {
  register_sidebar(['name'=>'Barra lateral','id'=>'regional-sidebar','description'=>'Espaço para anúncios identificados, banners e informações institucionais','before_widget'=>'<section class="panel sidebar-widget">','after_widget'=>'</section>','before_title'=>'<h3 class="widget-title">','after_title'=>'</h3>']);
});
function regional_label($id) {
  $cats = get_the_category($id);
  return $cats ? $cats[0]->name : 'Notícias';
}
function regional_card($post, $mode='story') {
  setup_postdata($post);
  $id = $post->ID;
  if ($mode === 'lead') {
    echo '<article class="lead"><a href="'.esc_url(get_permalink($id)).'">';
    if (has_post_thumbnail($id)) echo get_the_post_thumbnail($id,'regional-lead');
    else echo '<div class="lead-media">Notícia em destaque</div>';
    echo '</a><div class="lead-content"><span class="kicker">'.esc_html(regional_label($id)).'</span><h2><a href="'.esc_url(get_permalink($id)).'">'.esc_html(get_the_title($id)).'</a></h2><div class="summary">'.esc_html(wp_trim_words(get_the_excerpt($id),28)).'</div><div class="byline">'.esc_html(get_the_date('d/m/Y',$id)).' · '.esc_html(get_the_author_meta('display_name',$post->post_author)).'</div></div></article>';
  } elseif ($mode === 'compact') {
    echo '<article class="compact-story"><span class="kicker">'.esc_html(regional_label($id)).'</span><h3><a href="'.esc_url(get_permalink($id)).'">'.esc_html(get_the_title($id)).'</a></h3><div class="byline">'.esc_html(get_the_date('d/m/Y',$id)).'</div></article>';
  } else {
    echo '<article class="story"><a href="'.esc_url(get_permalink($id)).'">';
    if (has_post_thumbnail($id)) echo '<div class="story-media">'.get_the_post_thumbnail($id,'medium_large').'</div>';
    else echo '<div class="story-media">Notícias e serviços</div>';
    echo '</a><div class="story-body"><span class="kicker">'.esc_html(regional_label($id)).'</span><h3><a href="'.esc_url(get_permalink($id)).'">'.esc_html(get_the_title($id)).'</a></h3><p class="excerpt">'.esc_html(wp_trim_words(get_the_excerpt($id),20)).'</p><div class="byline">'.esc_html(get_the_date('d/m/Y',$id)).'</div></div></article>';
  }
  wp_reset_postdata();
}

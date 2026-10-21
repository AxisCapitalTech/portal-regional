<?php
if (!defined('ABSPATH')) exit;
require_once get_template_directory().'/includes/pr1-aero-bridge.php';
require_once get_template_directory().'/includes/pr1-news-seo.php';


/**
 * Marca editorial de homologação: alterável no Personalizar, sem renomear o projeto interno.
 * Esta configuração não constitui registro de marca nem publica conteúdo automaticamente.
 */
function regional_brand_setting(string $key, string $fallback): string {
  $value = sanitize_text_field((string) get_theme_mod($key, $fallback));
  return $value !== '' ? $value : $fallback;
}
function regional_public_brand(): string {
  return regional_brand_setting('regional_public_brand', 'PR1 News');
}
function regional_brand_mark(): string {
  return regional_brand_setting('regional_brand_mark', 'PR1');
}
function regional_public_tagline(): string {
  return regional_brand_setting('regional_public_tagline', 'Notícias da sua região, com contexto.');
}
add_action('customize_register', function ($customize) {
  $customize->add_section('regional_brand_section', [
    'title' => 'Marca pública — homologação',
    'description' => 'Nome provisório de apresentação. Não altera o nome técnico do tema ou do repositório.',
    'priority' => 25
  ]);
  foreach ([
    'regional_aero_url' => ['URL do AERO (HTTPS)', 'https://aero-command-center-alex-direito-6512.vercel.app/'],
    'regional_public_brand' => ['Nome público', 'PR1 News'],
    'regional_brand_mark' => ['Sigla no símbolo', 'PR1'],
    'regional_public_tagline' => ['Frase da marca', 'Notícias da sua região, com contexto.']
  ] as $id => $item) {
    $customize->add_setting($id, [
      'default' => $item[1],
      'sanitize_callback' => 'sanitize_text_field',
      'transport' => 'refresh'
    ]);
    $customize->add_control($id, [
      'label' => $item[0],
      'section' => 'regional_brand_section',
      'type' => 'text'
    ]);
  }
});

add_action('after_setup_theme', function () {
  load_theme_textdomain('portal-regional-tema', get_template_directory().'/languages');
  add_theme_support('title-tag'); add_theme_support('post-thumbnails'); add_theme_support('automatic-feed-links');
  add_theme_support('html5', ['search-form','gallery','caption','style','script']);
  register_nav_menus(['primary'=>'Menu principal']);
  add_image_size('regional-lead', 950, 520, true);
});
add_action('wp_enqueue_scripts', function () {
  wp_enqueue_style('regional-theme', get_stylesheet_uri(), [], '0.9.0');
  wp_enqueue_style('regional-v7', get_template_directory_uri().'/assets/pr1-v7.css', ['regional-theme'], '0.9.0');
  wp_enqueue_script('regional-v7-reading', get_template_directory_uri().'/assets/pr1-v7.js', [], '0.9.0', true);
  if(is_front_page() || (bool)get_query_var('pr1_aero') || is_page_template('page-pr1-aero.php')) {
    wp_enqueue_script('regional-aero', get_template_directory_uri().'/assets/pr1-aero.js', [], '0.9.0', true);
  }
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

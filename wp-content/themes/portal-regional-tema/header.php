<?php if (!defined('ABSPATH')) exit; ?>
<!doctype html><html <?php language_attributes(); ?>><head><meta charset="<?php bloginfo('charset'); ?>"><meta name="viewport" content="width=device-width,initial-scale=1,viewport-fit=cover"><?php wp_head(); ?></head>
<body <?php body_class('pr1-v7'); ?>><?php wp_body_open(); ?>
<a class="skip-link" href="#conteudo">Pular para o conteúdo</a>
<div class="ticker pr1-ribbon"><div class="wrap"><span>PR1 NEWS · Jornalismo regional</span><span class="pr1-ribbon-detail"><?php if (wp_get_environment_type() !== 'production'): ?>Prévia em homologação<?php else: ?>Informação perto de você<?php endif; ?></span></div></div>
<header class="mast pr1-mast" role="banner"><div class="wrap pr1-mast-inner">
 <a class="brand pr1-identity" href="<?php echo esc_url(home_url('/')); ?>" rel="home" aria-label="<?php echo esc_attr(regional_public_brand().' — página inicial'); ?>">
  <span class="brand-mark pr1-identity-mark" aria-hidden="true"><?php echo esc_html(regional_brand_mark()); ?></span>
  <span class="pr1-identity-word"><b><?php echo esc_html(regional_public_brand()); ?></b><small><?php echo esc_html(regional_public_tagline()); ?></small></span>
 </a>
 <div class="pr1-edition"><strong>REDAÇÃO REGIONAL</strong><span><?php echo esc_html(wp_date('d/m/Y')); ?> · Arapongas e região</span></div>
 <div class="pr1-tools" aria-label="Preferências de leitura">
  <button type="button" class="pr1-tool" data-pr1-contrast aria-label="Alternar contraste" aria-pressed="false">◐ Contraste</button>
  <button type="button" class="pr1-tool" data-pr1-type aria-label="Aumentar fonte das reportagens" aria-pressed="false">A+ Leitura</button>
 </div>
</div></header>
<nav class="header-nav pr1-nav" aria-label="Navegação por editoria"><div class="wrap nav-inner pr1-nav-inner">
<?php if (has_nav_menu('primary')): wp_nav_menu(['theme_location'=>'primary','container'=>false,'menu_class'=>'nav-list pr1-nav-list','depth'=>1]); else: ?>
<ul class="nav-list pr1-nav-list"><li><a href="<?php echo esc_url(home_url('/')); ?>"<?php echo is_front_page()?' aria-current="page"':''; ?>>Início</a></li>
<?php foreach (['Arapongas','Londrina','Maringá','Apucarana','Paraná','Brasil'] as $label):
  $term=get_term_by('name',$label,'category');
  if (!$term || is_wp_error($term)) continue;
  $is_active=is_category($term->term_id);
?>
<li><a href="<?php echo esc_url(get_category_link($term)); ?>"<?php echo $is_active?' aria-current="page"':''; ?>><?php echo esc_html($label); ?></a></li>
<?php endforeach; ?><li><a href="<?php echo esc_url(home_url('/?s=')); ?>">Buscar</a></li></ul>
<?php endif; ?>
</div></nav>

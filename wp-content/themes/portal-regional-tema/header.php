<?php if (!defined('ABSPATH')) exit; ?>
<!doctype html><html <?php language_attributes(); ?>><head><meta charset="<?php bloginfo('charset'); ?>"><meta name="viewport" content="width=device-width,initial-scale=1"><?php wp_head(); ?></head>
<body <?php body_class(); ?>><?php wp_body_open(); ?>
<a class="skip-link" href="#conteudo">Pular para o conteúdo</a>
<div class="ticker"><div class="wrap"><span>INFORMAÇÃO DA NOSSA REGIÃO</span><span class="sub"><?php echo esc_html(wp_date('l, d \d\e F \d\e Y')); ?></span></div></div>
<header class="mast" role="banner"><div class="wrap mast-row"><a class="brand" href="<?php echo esc_url(home_url('/')); ?>" rel="home"><div class="brand-mark">R</div><span><b><?php echo esc_html(get_bloginfo('name') ?: 'Portal Regional'); ?></b><small>Arapongas · Londrina · Maringá · Apucarana</small></span></a><div class="mast-search"><?php get_search_form(); ?></div></div></header>
<nav class="header-nav" aria-label="Navegação principal"><div class="wrap nav-inner">
<?php if (has_nav_menu('primary')) wp_nav_menu(['theme_location'=>'primary','container'=>false,'menu_class'=>'nav-list','depth'=>1]); else { ?>
<ul class="nav-list"><li><a href="<?php echo esc_url(home_url('/')); ?>">Início</a></li>
<?php foreach (['Arapongas','Londrina','Maringá','Apucarana','Paraná','Brasil'] as $label) { $term = get_term_by('name',$label,'category'); if ($term) echo '<li><a href="'.esc_url(get_category_link($term)).'">'.esc_html($label).'</a></li>'; } ?>
</ul><?php } ?><span class="nav-status">Jornalismo com transparência</span></div></nav>

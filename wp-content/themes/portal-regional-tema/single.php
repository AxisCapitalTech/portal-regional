<?php if (!defined('ABSPATH')) exit; get_header(); ?>
<main id="conteudo" class="wrap pr1-main"><?php while(have_posts()):the_post(); ?>
 <nav class="pr1-breadcrumb" aria-label="Você está aqui"><a href="<?php echo esc_url(home_url('/')); ?>">Início</a><span aria-hidden="true">/</span><?php $pr1_term=get_the_category(get_the_ID()); $pr1_category=$pr1_term?$pr1_term[0]:null; if($pr1_category): ?><a href="<?php echo esc_url(get_category_link($pr1_category)); ?>"><?php echo esc_html($pr1_category->name); ?></a><?php else: ?><span>Notícias</span><?php endif; ?></nav>
 <article class="article pr1-article">
  <header class="pr1-article-heading"><span class="kicker"><?php echo esc_html(regional_label(get_the_ID())); ?></span>
   <h1 class="article-title"><?php the_title(); ?></h1>
   <?php if(has_excerpt()): ?><p class="pr1-dek"><?php echo esc_html(get_the_excerpt()); ?></p><?php endif; ?>
   <div class="byline pr1-article-byline"><span>Publicado em <time datetime="<?php echo esc_attr(get_the_date('c')); ?>"><?php echo esc_html(get_the_date('d/m/Y \à\s H:i')); ?></time> · <a href="<?php echo esc_url(get_author_posts_url((int)get_the_author_meta('ID'))); ?>" rel="author"><?php echo esc_html(get_the_author()); ?></a></span>
   <?php if(get_the_modified_date('U')!==get_the_date('U')): ?><span>Atualizado em <time datetime="<?php echo esc_attr(get_the_modified_date('c')); ?>"><?php echo esc_html(get_the_modified_date('d/m/Y H:i')); ?></time></span><?php endif; ?></div>
  </header>
  <div class="pr1-article-grid"><div>
  <?php if(has_post_thumbnail()): ?><figure class="pr1-article-cover"><?php the_post_thumbnail('large'); ?><figcaption><?php echo esc_html(get_the_post_thumbnail_caption()); ?></figcaption></figure><?php endif; ?>
  <div class="article-content pr1-article-body"><?php the_content(); ?></div>
  <?php $source=get_post_meta(get_the_ID(),'_pra_source_url',true); $source_name=(string)get_post_meta(get_the_ID(),'_pra_source_name',true); if($source): ?><p class="pr1-source"><strong>Fonte<?php echo $source_name?' — '.esc_html($source_name):' consultada'; ?>:</strong> <a href="<?php echo esc_url($source); ?>" target="_blank" rel="noopener noreferrer nofollow">Acessar publicação original ↗</a></p><?php endif; ?>
  <?php the_tags('<div class="pr1-article-tags"><strong>Temas: </strong>',', ','</div>'); ?>
  <?php $related=($pr1_category)?get_posts(['category'=>(int)$pr1_category->term_id,'exclude'=>[get_the_ID()],'post_status'=>'publish','numberposts'=>3]):[]; ?>
  <?php if($related): ?><section class="pr1-related" aria-label="Leia também"><h2>Leia também</h2><ul><?php foreach($related as $more): ?><li><a href="<?php echo esc_url(get_permalink($more)); ?>"><?php echo esc_html(get_the_title($more)); ?></a></li><?php endforeach; ?></ul></section><?php endif; ?>
  </div><aside class="pr1-article-aside" aria-label="Informações adicionais">
    <div class="panel"><span class="pr1-panel-label">LEITURA</span><h3>Leitura confortável</h3><p>Use os controles no cabeçalho para aumentar as letras ou ajustar o contraste.</p></div>
    <?php if(shortcode_exists('pra_anuncios')) echo do_shortcode('[pra_anuncios position="sidebar" limit="2"]'); ?>
    <div class="panel"><span class="pr1-panel-label">TRANSPARÊNCIA</span><p>Matérias provenientes de monitoramento de fontes exigem revisão e aprovação da redação antes da publicação.</p></div>
  </aside></div>
 </article>
<?php endwhile; ?></main>
<?php get_footer(); ?>

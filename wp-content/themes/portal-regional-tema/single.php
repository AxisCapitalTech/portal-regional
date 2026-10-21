<?php if (!defined('ABSPATH')) exit; get_header(); ?>
<main id="conteudo" class="wrap pr1-main"><?php while(have_posts()):the_post(); ?>
 <nav class="pr1-breadcrumb" aria-label="Você está aqui"><a href="<?php echo esc_url(home_url('/')); ?>">Início</a><span aria-hidden="true">/</span><span><?php echo esc_html(regional_label(get_the_ID())); ?></span></nav>
 <article class="article pr1-article">
  <header class="pr1-article-heading"><span class="kicker"><?php echo esc_html(regional_label(get_the_ID())); ?></span>
   <h1 class="article-title"><?php the_title(); ?></h1>
   <?php if(has_excerpt()): ?><p class="pr1-dek"><?php echo esc_html(get_the_excerpt()); ?></p><?php endif; ?>
   <div class="byline pr1-article-byline"><span>Publicado em <?php echo esc_html(get_the_date('d/m/Y \à\s H:i')); ?> · <?php echo esc_html(get_the_author()); ?></span>
   <?php if(get_the_modified_date('U')!==get_the_date('U')): ?><span>Atualizado em <?php echo esc_html(get_the_modified_date('d/m/Y H:i')); ?></span><?php endif; ?></div>
  </header>
  <div class="pr1-article-grid"><div>
  <?php if(has_post_thumbnail()): ?><figure class="pr1-article-cover"><?php the_post_thumbnail('large'); ?><figcaption><?php echo esc_html(get_the_post_thumbnail_caption()); ?></figcaption></figure><?php endif; ?>
  <div class="article-content pr1-article-body"><?php the_content(); ?></div>
  <?php $source=get_post_meta(get_the_ID(),'_pra_source_url',true); if($source): ?><p class="pr1-source"><strong>Fonte consultada:</strong> <a href="<?php echo esc_url($source); ?>" target="_blank" rel="noopener noreferrer nofollow">Acessar publicação original ↗</a></p><?php endif; ?>
  </div><aside class="pr1-article-aside" aria-label="Informações adicionais">
    <div class="panel"><span class="pr1-panel-label">LEITURA</span><h3>Leitura confortável</h3><p>Use os controles no cabeçalho para aumentar as letras ou ajustar o contraste.</p></div>
    <?php if(shortcode_exists('pra_anuncios')) echo do_shortcode('[pra_anuncios position="sidebar" limit="2"]'); ?>
    <div class="panel"><span class="pr1-panel-label">TRANSPARÊNCIA</span><p>Matérias provenientes de monitoramento de fontes exigem revisão e aprovação da redação antes da publicação.</p></div>
  </aside></div>
 </article>
<?php endwhile; ?></main>
<?php get_footer(); ?>

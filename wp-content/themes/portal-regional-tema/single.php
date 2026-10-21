<?php get_header(); ?>
<main id="conteudo" class="wrap"><?php while(have_posts()):the_post(); ?>
<article class="article"><span class="kicker"><?php echo esc_html(regional_label(get_the_ID())); ?></span><h1 class="article-title"><?php the_title(); ?></h1>
<div class="byline">Publicado em <?php echo esc_html(get_the_date('d/m/Y \à\s H:i')); ?> · <?php the_author(); ?><?php if(get_the_modified_date('U')!==get_the_date('U')) echo ' · Atualizado em '.esc_html(get_the_modified_date('d/m/Y H:i')); ?></div>
<?php if(has_post_thumbnail()) { ?><figure><?php the_post_thumbnail('large'); ?></figure><?php } ?>
<div class="article-content"><?php the_content(); ?></div>
<?php $source=get_post_meta(get_the_ID(),'_pra_source_url',true); if ($source) { ?><p class="byline">Fonte consultada: <a href="<?php echo esc_url($source); ?>" target="_blank" rel="noopener noreferrer nofollow">Acessar publicação original</a></p><?php } ?>
</article><?php endwhile; ?></main>
<?php get_footer(); ?>

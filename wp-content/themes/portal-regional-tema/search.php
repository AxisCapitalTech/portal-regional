<?php get_header(); ?>
<main id="conteudo" class="wrap"><h1 class="archive-title">Resultados para: <?php echo esc_html(get_search_query()); ?></h1>
<div class="stories"><?php if(have_posts()):while(have_posts()):the_post();global $post;regional_card($post);endwhile;else:?><p>Não encontramos resultados para sua busca.</p><?php endif;?></div><div class="pagination"><?php the_posts_pagination(); ?></div></main>
<?php get_footer(); ?>

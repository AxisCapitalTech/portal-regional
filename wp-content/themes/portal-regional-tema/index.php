<?php get_header(); ?>
<main id="conteudo" class="wrap"><h1 class="archive-title"><?php echo is_home() ? 'Últimas notícias' : esc_html(get_the_archive_title()); ?></h1>
<div class="content-grid"><div><div class="stories"><?php if (have_posts()): while (have_posts()): the_post(); global $post; regional_card($post); endwhile; else: ?><div class="empty">Nenhuma notícia disponível nesta seção.</div><?php endif; ?></div><div class="pagination"><?php the_posts_pagination(); ?></div></div><aside><?php if (is_active_sidebar('regional-sidebar')) dynamic_sidebar('regional-sidebar'); ?></aside></div></main>
<?php get_footer(); ?>

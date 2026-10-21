<?php get_header(); ?>
<main id="conteudo" class="wrap"><?php while(have_posts()):the_post(); ?><article class="article"><h1 class="article-title"><?php the_title(); ?></h1><div class="article-content"><?php the_content(); ?></div></article><?php endwhile; ?></main>
<?php get_footer(); ?>

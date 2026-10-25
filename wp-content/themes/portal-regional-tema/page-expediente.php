<?php
/** Template Name: PR1 — Expediente (homologação editorial) */
if (!defined('ABSPATH')) exit;
get_header(); ?>
<main id="conteudo" class="wrap pr1-main"><article class="pr1-article"><h1>Expediente</h1>
<?php if (defined('PR1_EDITORIAL_NOTICE_APPROVED') && PR1_EDITORIAL_NOTICE_APPROVED === true): ?>
<p>Fábio Roberto, Letícia Alvarez e Alberto Torres são assinaturas editoriais fictícias, utilizadas em textos produzidos com apoio de automação e submetidos a revisão editorial humana.</p>
<p>Editor responsável: <?php echo esc_html((string) get_option('pr1_editor_responsavel_publico', 'A designar — publicação bloqueada até aprovação')); ?></p>
<?php else: ?><p>Expediente em preparação. Declaração editorial ainda não aprovada.</p><?php endif; ?>
<?php while(have_posts()):the_post();the_content();endwhile; ?>
</article></main><?php get_footer();

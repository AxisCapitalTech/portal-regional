<?php if (!defined('ABSPATH')) exit; ?>
<footer class="footer pr1-footer"><div class="wrap pr1-footer-inner"><div class="pr1-footer-brand"><strong><?php echo esc_html(regional_public_brand()); ?></strong>
<p>Projeto de jornalismo regional com informações de interesse público. Publicidade identificada e apuração editorial responsável.</p>
<small>© <?php echo esc_html(wp_date('Y')); ?> · <?php echo esc_html(regional_public_brand()); ?><?php if(wp_get_environment_type() !== 'production'): ?> · Marca provisória em homologação<?php endif; ?></small></div>
<div class="pr1-footer-coverage">Arapongas · Londrina · Maringá · Apucarana<br>Paraná · Brasil</div>
<div class="pr1-footer-actions"><a href="<?php echo esc_url(home_url('/')); ?>">Página inicial</a><a href="<?php echo esc_url(home_url('/?s=')); ?>">Pesquisar</a><a href="<?php echo esc_url(home_url('/#processo-editorial')); ?>">Como verificamos</a><span>Contato e política editorial: configurar antes do lançamento</span></div>
</div></footer>
<?php wp_footer(); ?></body></html>

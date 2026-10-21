<?php if (!defined('ABSPATH')) exit; ?>
<footer class="footer pr1-footer"><div class="wrap pr1-footer-inner"><div class="pr1-footer-brand"><strong><?php echo esc_html(regional_public_brand()); ?></strong>
<p>Projeto de jornalismo regional com informações de interesse público. Publicidade identificada e apuração editorial responsável.</p>
<p class="pr1-independencia">Portal independente: este site não é canal oficial nem veículo institucional da Prefeitura de Arapongas.</p>
<small>© <?php echo esc_html(wp_date('Y')); ?> · <?php echo esc_html(regional_public_brand()); ?><?php if(wp_get_environment_type() !== 'production'): ?> · Marca provisória em homologação<?php endif; ?></small></div>
<div class="pr1-footer-coverage">Arapongas · Londrina · Maringá · Apucarana<br>Paraná · Brasil</div>
<div class="pr1-footer-actions"><a href="<?php echo esc_url(home_url('/')); ?>">Página inicial</a><a href="<?php echo esc_url(home_url('/?s=')); ?>">Pesquisar</a><a href="<?php echo esc_url(home_url('/#processo-editorial')); ?>">Como verificamos</a><?php $pr1_legal_pages=wp_cache_get('pr1_legal_pages_v1','pr1news');
if(!is_array($pr1_legal_pages)){
  $pr1_legal_pages=[];
  foreach(['expediente'=>'Expediente','contato'=>'Contato','politica-editorial'=>'Política editorial e correções','direito-de-resposta'=>'Direito de resposta e remoção','termos-de-uso'=>'Termos de uso'] as $slug=>$label){
    $page=get_page_by_path($slug);
    if($page && get_post_status($page)==='publish')$pr1_legal_pages[]=[$page,$label];
  }
  wp_cache_set('pr1_legal_pages_v1',$pr1_legal_pages,'pr1news',300);
}
foreach($pr1_legal_pages as [$page,$label]): ?><a href="<?php echo esc_url(get_permalink($page)); ?>"><?php echo esc_html($label); ?></a><?php endforeach; ?>
<?php $privacy=get_privacy_policy_url(); if($privacy): ?><a href="<?php echo esc_url($privacy); ?>">Privacidade</a><?php endif; ?></div>
</div></footer>
<?php wp_footer(); ?></body></html>

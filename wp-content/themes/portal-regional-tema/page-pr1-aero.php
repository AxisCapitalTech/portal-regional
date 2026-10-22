<?php if(!defined('ABSPATH'))exit; get_header(); ?>
<main id="conteudo" class="wrap pr1-main pr1-aero-page">
 <nav class="pr1-breadcrumb" aria-label="Você está aqui"><a href="<?php echo esc_url(home_url('/')); ?>">PR1 News</a> <span>/</span> Meteorologia</nav>
 <section class="pr1-aero-top"><span class="pr1-kicker">METEOROLOGIA REGIONAL</span><h1>AERO Command Center</h1><p>O painel meteorológico integrado ao PR1 News. Previsões, radar, raios e avisos dependem das fontes e serviços originais do AERO.</p>
 <?php $aero_url=PR1_Aero_Bridge::app_url(); ?>
 <div class="pr1-aero-buttons"><?php if($aero_url!==''): ?><a href="<?php echo esc_url($aero_url); ?>" target="_blank" rel="noopener noreferrer" class="pr1-aero-open">Abrir AERO completo ↗</a><?php endif; ?><a href="<?php echo esc_url(home_url('/')); ?>">Voltar ao PR1 News</a></div>
 </section>
 <section class="pr1-aero-inline" aria-labelledby="pr1-aero-forecast-title">
  <h2 id="pr1-aero-forecast-title">Condições nas cidades</h2>
  <?php get_template_part('template-parts/pr1-aero-conditions'); ?>
 </section>
 <?php if($aero_url!==''): ?>
 <section class="pr1-aero-frame-section" aria-labelledby="pr1-aero-embed-title">
  <h2 id="pr1-aero-embed-title">Painel AERO</h2><p>O aplicativo abre em uma área separada quando você solicitar. Caso o serviço impeça exibição incorporada, use “Abrir AERO completo”.</p>
  <button type="button" class="pr1-aero-load" data-aero-load>Carregar painel meteorológico</button>
  <div class="pr1-aero-embed" data-aero-container>
   <iframe title="AERO Command Center — painel externo" loading="lazy"
    data-src="<?php echo esc_url($aero_url); ?>" sandbox="allow-scripts allow-same-origin allow-forms allow-popups" referrerpolicy="no-referrer" allow="geolocation 'none'"></iframe>
  </div>
 </section>
 <?php else: ?><p class="pr1-aero-disclaimer" role="status">Painel AERO externo desativado até configurar PR1_AERO_HOST no wp-config.php.</p><?php endif; ?>
 <p class="pr1-aero-disclaimer"><strong>Aviso:</strong> não transforme previsão ou índices do AERO em alerta oficial. Para ocorrências perigosas, consulte o INMET, Defesa Civil e demais autoridades. Sem validação de atualização, dados não são exibidos como condições atuais.</p>
</main>
<?php get_footer(); ?>

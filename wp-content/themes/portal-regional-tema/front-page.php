<?php if (!defined('ABSPATH')) exit; get_header(); ?>
<main id="conteudo" class="wrap pr1-main">
 <div class="pr1-crumb"><span>PORTAL DE NOTÍCIAS</span><?php if(wp_get_environment_type() !== 'production'): ?><span class="pr1-pill">EM HOMOLOGAÇÃO</span><?php endif; ?></div>
 <section class="pr1-hero" aria-label="Apresentação do portal">
  <div class="pr1-hero-inner"><div class="pr1-kicker"><span aria-hidden="true" class="pr1-dot"></span> INFORMAÇÃO REGIONAL</div>
   <h1>A notícia perto de você.</h1><p>Uma nova experiência para acompanhar o que importa em Arapongas, Londrina, Maringá, Apucarana e todo o Paraná.</p>
   <div class="pr1-hero-tags" aria-label="Cidades de cobertura"><?php foreach(['Arapongas','Londrina','Maringá','Apucarana'] as $city): ?><span><?php echo esc_html($city); ?></span><?php endforeach; ?></div>
   <div class="pr1-hero-actions"><a class="pr1-btn-primary" href="#cidades">Explorar minha cidade <span aria-hidden="true">→</span></a><a class="pr1-btn-secondary" href="#processo-editorial">Como verificamos <span aria-hidden="true">↗</span></a></div>
   <p class="pr1-hero-note">Notícias de fontes monitoradas só aparecem após aprovação editorial humana.</p>
  </div>
 </section>
 <?php if(wp_get_environment_type() !== 'production'): ?>
 <div class="pr1-info" role="note"><span aria-hidden="true">ⓘ</span><p><strong>V7 integrada ao WordPress — ambiente de homologação.</strong> Este ambiente é de testes. Não é um lançamento comercial; o nome PR1 News ainda depende de verificação de marca.</p></div>
 <?php endif; ?>
 <section class="pr1-quick-local" aria-label="Escolha uma cidade"><div><span class="pr1-kicker-dark">SUA REGIÃO</span><h2>Onde você quer se informar?</h2><p>Acompanhe o noticiário da sua cidade.</p></div>
  <div class="pr1-quick-cities">
  <?php foreach(['Arapongas','Londrina','Maringá','Apucarana'] as $city):
    $term=get_term_by('name',$city,'category'); if($term && !is_wp_error($term)): ?>
    <a href="<?php echo esc_url(get_category_link($term)); ?>"><?php echo esc_html($city); ?><span aria-hidden="true">↗</span></a>
    <?php else: ?><span class="pr1-city-inactive"><?php echo esc_html($city); ?><small>Em preparação</small></span><?php endif; endforeach; ?>
  </div>
 </section>
 <section class="pr1-aero-home" aria-labelledby="pr1-aero-home-title">
  <div class="pr1-aero-home-top"><div><span class="pr1-kicker">AERO × PR1 NEWS</span>
  <h2 id="pr1-aero-home-title">Meteorologia da nossa região</h2>
  <p>Consulte condições meteorológicas de quatro cidades e abra o AERO Command Center para acompanhar o radar e outras camadas de monitoramento.</p></div>
  <a class="pr1-aero-link" href="<?php echo esc_url(PR1_Aero_Bridge::portal_url()); ?>">Explorar painel AERO ↗</a></div>
  <?php get_template_part('template-parts/pr1-aero-conditions'); ?>
 </section>
 <div class="pr1-section-head"><h2>Destaques da capa</h2><span>Somente notícias publicadas pela redação</span></div>
 <?php
  $lead_term=get_term_by('name','Arapongas','category');
  $arapongas_lead=($lead_term && !is_wp_error($lead_term)) ? get_posts(['category'=>$lead_term->term_id,'numberposts'=>1,'post_status'=>'publish']) : [];
  $lead=$arapongas_lead?$arapongas_lead[0]:null;
  $others=get_posts(['numberposts'=>4,'post_status'=>'publish','exclude'=>$lead?[$lead->ID]:[]]);
  if(!$lead && $others) $lead=array_shift($others);
 ?>
 <div class="pr1-feature">
  <?php if($lead): ?><div class="pr1-feature-lead"><?php regional_card($lead,'lead'); ?></div>
  <?php else: ?>
  <article class="pr1-feature-empty"><div class="pr1-feature-art" aria-hidden="true"><span>▤</span></div>
   <div class="pr1-feature-copy"><span class="kicker">DESTAQUE PRINCIPAL</span><h3>As notícias verificadas aparecerão aqui.</h3><p>A publicação depende de apuração e aprovação editorial humana.</p></div>
  </article>
  <?php endif; ?>
  <div class="pr1-feature-side">
   <?php if($others): foreach(array_slice($others,0,3) as $item) regional_card($item,'compact'); ?>
   <?php else: ?>
   <div class="pr1-side-empty"><span class="kicker">CIDADES</span><strong>Noticiário municipal em preparação</strong><small>Sem manchetes fictícias.</small></div>
   <div class="pr1-side-empty"><span class="kicker">SERVIÇOS</span><strong>Informações úteis para a comunidade</strong><small>Conteúdos a confirmar.</small></div>
   <div class="pr1-side-empty"><span class="kicker">PARANÁ</span><strong>Cobertura estadual em desenvolvimento</strong><small>Aguardando fontes validadas.</small></div>
   <?php endif; ?>
  </div>
 </div>
 <section class="pr1-official-links" aria-labelledby="pr1-official-heading">
   <div class="pr1-section-head"><h2 id="pr1-official-heading">Nas fontes oficiais</h2><span>Links externos selecionados em 21/10/2026</span></div>
   <p class="pr1-official-disclosure"><strong>Clipping de comunicados oficiais.</strong> Estas chamadas são referências para leitura no site de origem, não reportagens produzidas pelo PR1. Não há atualização automática nem aprovação editorial implícita de todo o conteúdo externo.</p>
   <div class="pr1-official-grid">
   <?php $official_links=require get_template_directory().'/includes/pr1-links-oficiais-v8.php';
   foreach ($official_links as $entry):
      $host=wp_parse_url($entry['url'], PHP_URL_HOST);
      if (!in_array($host,['arapongas.atende.net','blog.londrina.pr.gov.br','www.maringa.pr.gov.br','www.apucarana.pr.gov.br','www.parana.pr.gov.br'],true)) continue;
   ?>
     <article class="pr1-official-item">
       <div class="pr1-official-meta"><span><?php echo esc_html($entry['city']); ?></span><time><?php echo esc_html($entry['date']); ?></time></div>
       <h3><a href="<?php echo esc_url($entry['url']); ?>" target="_blank" rel="noopener noreferrer nofollow external"><?php echo esc_html($entry['title']); ?> <span aria-hidden="true">↗</span></a></h3>
       <p>Fonte: <?php echo esc_html($entry['source']); ?></p>
     </article>
   <?php endforeach; ?>
   </div>
 </section>
 <section class="pr1-formats" aria-label="Formatos editoriais"><div class="pr1-section-head"><h2>Notícia, serviço e contexto</h2><span>Três formatos de informação local</span></div>
  <div class="pr1-format-grid">
   <article class="pr1-format-card"><b aria-hidden="true">01</b><span class="pr1-red-label">NOTÍCIA</span><h3>O fato, com apuração</h3><p>Informação factual, fontes identificadas e responsabilidade sobre cada detalhe publicado.</p></article>
   <article class="pr1-format-card"><b aria-hidden="true">02</b><span class="pr1-red-label">SERVIÇO</span><h3>Informação que ajuda</h3><p>Guias de atendimento, avisos oficiais e orientações úteis com data e fonte verificável.</p></article>
   <article class="pr1-format-card"><b aria-hidden="true">03</b><span class="pr1-red-label">EXPLICAÇÃO</span><h3>Entenda o contexto</h3><p>O que aconteceu, por que importa e quais perguntas ainda precisam de resposta.</p></article>
  </div>
 </section>
 <div class="pr1-bottom">
  <div><section id="cidades" class="pr1-cities-section"><div class="pr1-section-head"><h2>Nossas cidades</h2></div><div class="pr1-city-grid">
  <?php foreach(['Arapongas','Londrina','Maringá','Apucarana'] as $city):
    $term=get_term_by('name',$city,'category');
    $items=($term && !is_wp_error($term))?get_posts(['category'=>$term->term_id,'numberposts'=>2,'post_status'=>'publish']):[];
  ?>
   <section class="pr1-city-card" aria-label="Notícias de <?php echo esc_attr($city); ?>">
    <div class="pr1-city-head"><h3><?php echo esc_html($city); ?></h3><span aria-hidden="true">↗</span></div><span class="pr1-city-status">COBERTURA PRIORITÁRIA</span>
    <?php if($items): foreach($items as $item): ?><p><a href="<?php echo esc_url(get_permalink($item)); ?>"><?php echo esc_html(get_the_title($item)); ?></a></p><?php endforeach;
    else: ?><p>Aguardando informações verificadas sobre <?php echo esc_html($city); ?>.</p><?php endif; ?>
    <?php if($term && !is_wp_error($term)): ?><a class="pr1-city-link" href="<?php echo esc_url(get_category_link($term)); ?>">Explorar editoria →</a><?php endif; ?>
   </section>
  <?php endforeach; ?></div></section>
  <?php foreach(['Paraná','Brasil'] as $area):
   $term=get_term_by('name',$area,'category'); $items=($term && !is_wp_error($term))?get_posts(['category'=>$term->term_id,'numberposts'=>4,'post_status'=>'publish']):[]; ?>
  <section class="pr1-regional-section" aria-label="Notícias de <?php echo esc_attr($area); ?>">
   <div class="pr1-section-head"><h2><?php echo esc_html($area); ?></h2><?php if($term && !is_wp_error($term)): ?><a href="<?php echo esc_url(get_category_link($term)); ?>">Ver editoria →</a><?php endif; ?></div>
   <?php if($items): ?><div class="stories"><?php foreach($items as $item) regional_card($item); ?></div>
   <?php else: ?><p class="pr1-region-empty">Noticiário em preparação. Notícias serão exibidas após revisão da redação.</p><?php endif; ?>
  </section><?php endforeach; ?>
  </div>
  <aside class="pr1-sidebar" aria-label="Comunidade e publicidade">
   <section class="panel"><span class="pr1-panel-label">COMUNIDADE</span><h3>Notícias da sua cidade</h3><p>Boletim regional: inscrições e disparos só serão ativados após configuração e política de privacidade.</p>
    <?php if(shortcode_exists('pra_newsletter')): echo do_shortcode('[pra_newsletter]'); else: ?><div class="pr1-inactive">Inscrições não ativadas</div><?php endif; ?>
   </section>
   <?php if(shortcode_exists('pra_anuncios')) echo do_shortcode('[pra_anuncios position="sidebar" limit="3"]'); ?>
   <?php if(is_active_sidebar('regional-sidebar')): dynamic_sidebar('regional-sidebar'); else: ?><section class="panel"><span class="pr1-panel-label">PUBLICIDADE</span><h3>Espaço comercial</h3><p>Reservado para futuras campanhas, sempre identificadas.</p><div class="pr1-inactive">Nenhum anunciante ativo</div></section><?php endif; ?>
   <section class="panel"><span class="pr1-panel-label">COMPROMISSO EDITORIAL</span><h3>Antes de publicar, verificar</h3><p>Fontes, contexto, data, correções e aprovação humana acompanham toda notícia originada do monitoramento.</p></section>
  </aside>
 </div>
 <section id="processo-editorial" class="pr1-promise"><div><span class="pr1-promise-symbol" aria-hidden="true">✦</span><span class="pr1-promise-label">NOSSO PROCESSO</span><h2>Rapidez sem abrir mão da verificação.</h2><p>As fontes alimentam a pauta. A redação confere os fatos, contextualiza e decide o que publicar.</p></div>
  <div class="pr1-promise-steps"><div><b>01</b>Monitorar fontes</div><div><b>02</b>Checar fatos e contexto</div><div><b>03</b>Aprovar antes de publicar</div></div>
 </section>
</main>
<?php get_footer(); ?>

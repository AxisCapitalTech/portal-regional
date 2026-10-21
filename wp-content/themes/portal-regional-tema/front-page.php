<?php get_header(); ?>
<main id="conteudo" class="wrap">
  <section class="intro"><div><span class="eyebrow"><?php echo esc_html(regional_public_brand()); ?> · cobertura regional</span><h1>A notícia perto de você.</h1><p>Arapongas em primeiro plano. Londrina, Maringá e Apucarana todos os dias. O Paraná e o Brasil em contexto.</p></div></section>
  <div class="localities" aria-label="Cidades da cobertura">
    <?php foreach(['Arapongas','Londrina','Maringá','Apucarana'] as $city): $term=get_term_by('name',$city,'category'); ?>
       <?php if ($term): ?><a href="<?php echo esc_url(get_category_link($term)); ?>"><?php echo esc_html($city); ?></a><?php else: ?><span><?php echo esc_html($city); ?></span><?php endif; ?>
    <?php endforeach; ?>
  </div>
  <div class="section-label"><h2>Destaques do dia</h2><span class="coverage-tag">Somente matérias publicadas e verificadas</span></div>
  <?php
    $lead_term=get_term_by('name','Arapongas','category');
    $arapongas_lead=$lead_term?get_posts(['category'=>$lead_term->term_id,'numberposts'=>1,'post_status'=>'publish']):[];
    $lead=$arapongas_lead?$arapongas_lead[0]:null;
    $others=get_posts(['numberposts'=>4,'post_status'=>'publish','exclude'=>$lead?[$lead->ID]:[]]);
    if(!$lead && $others) $lead=array_shift($others);
  ?>
  <?php if ($lead): ?>
    <div class="feature"><div><?php regional_card($lead,'lead'); ?></div><div class="side-stack"><?php foreach(array_slice($others,0,3) as $p)regional_card($p,'compact'); ?></div></div>
  <?php else: ?><div class="empty">O portal está em implantação. As reportagens verificadas aparecerão aqui após aprovação editorial. Não usamos notícias fictícias para preencher o site.</div><?php endif; ?>
  <div class="content-grid"><div>
    <p class="city-intro">Cobertura prioritária com seções independentes para cada cidade, seguida de notícias estaduais e nacionais.</p>
    <?php foreach (['Arapongas','Londrina','Maringá','Apucarana'] as $city):
      $term=get_term_by('name',$city,'category');
      $items=$term?get_posts(['category'=>$term->term_id,'numberposts'=>4,'post_status'=>'publish']):[];
    ?>
    <section class="city-section" aria-label="Notícias de <?php echo esc_attr($city); ?>">
      <div class="section-label"><h2><?php echo esc_html($city); ?></h2><?php if($term): ?><a href="<?php echo esc_url(get_category_link($term)); ?>">Todas de <?php echo esc_html($city); ?> →</a><?php endif; ?></div>
      <?php if($items): ?><div class="stories"><?php foreach($items as $p)regional_card($p); ?></div>
      <?php else: ?><div class="city-empty">Aguardando a primeira publicação verificada sobre <?php echo esc_html($city); ?>.</div><?php endif; ?>
    </section>
    <?php endforeach; ?>
    <?php foreach (['Paraná','Brasil'] as $label):
      $term=get_term_by('name',$label,'category');
      $items=$term?get_posts(['category'=>$term->term_id,'numberposts'=>4,'post_status'=>'publish']):[];
    ?>
      <section class="city-section"><div class="section-label"><h2><?php echo esc_html($label); ?></h2><?php if($term): ?><a href="<?php echo esc_url(get_category_link($term)); ?>">Ver notícias →</a><?php endif; ?></div>
      <?php if($items): ?><div class="stories"><?php foreach($items as $p)regional_card($p); ?></div>
      <?php else: ?><div class="city-empty">Noticiário em preparação. Apenas conteúdo revisado será exibido.</div><?php endif; ?></section>
    <?php endforeach; ?>
  </div>
  <aside aria-label="Newsletter e publicidade">
    <div class="panel"><h3>Notícias da sua cidade</h3><p>Escolha Arapongas, Londrina, Maringá ou Apucarana. Confirme sua inscrição para receber o boletim regional.</p><?php if(shortcode_exists('pra_newsletter'))echo do_shortcode('[pra_newsletter]');else echo '<small>Cadastro ainda não ativado.</small>'; ?></div>
    <?php if(shortcode_exists('pra_anuncios'))echo do_shortcode('[pra_anuncios position="sidebar" limit="3"]'); ?>
    <?php if(is_active_sidebar('regional-sidebar'))dynamic_sidebar('regional-sidebar');else{?><div class="panel"><div class="ad-slot"><strong>PUBLICIDADE</strong><small>Espaço reservado para campanhas contratadas. Nenhum anunciante real nesta demonstração.</small></div></div><?php } ?>
    <div class="panel"><h3>Compromisso editorial</h3><p>Agentes organizam pautas, mas não substituem checagem e aprovação humana. Fontes e correções devem ser transparentes.</p></div>
  </aside></div>
</main>
<?php get_footer(); ?>

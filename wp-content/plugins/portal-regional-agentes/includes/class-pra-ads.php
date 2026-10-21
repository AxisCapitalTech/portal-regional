<?php
/** Gestão manual de patrocínios locais e inserções identificadas. Sem cobrança automática. */
if (!defined('ABSPATH')) exit;
final class PR_Ads {
  public static function init():void{
    add_action('init',[__CLASS__,'register']);
    add_action('add_meta_boxes_pra_campaign',[__CLASS__,'add_box']);
    add_action('save_post_pra_campaign',[__CLASS__,'save'],10,2);
    add_action('admin_menu',[__CLASS__,'menu']);
    add_shortcode('pra_anuncios',[__CLASS__,'shortcode']);
  }
  public static function register():void{
    $caps=[];
    foreach(['edit_post','read_post','delete_post','edit_posts','edit_others_posts','publish_posts','read_private_posts','delete_posts','delete_others_posts','edit_published_posts','create_posts','delete_published_posts'] as $c)$caps[$c]='manage_options';
    register_post_type('pra_campaign',['labels'=>['name'=>'Patrocínios','singular_name'=>'Patrocínio','add_new_item'=>'Novo patrocínio','edit_item'=>'Editar patrocínio','menu_name'=>'Patrocínios','all_items'=>'Todos os patrocínios'],
      'public'=>false,'publicly_queryable'=>false,'exclude_from_search'=>true,'show_ui'=>true,'show_in_menu'=>'tools.php','supports'=>['title','thumbnail'],'menu_icon'=>'dashicons-megaphone','capabilities'=>$caps,'map_meta_cap'=>false,'show_in_rest'=>false]);
  }
  public static function menu():void{
    add_management_page('Receita Publicitária','Receita Publicitária','manage_options','pra-ad-stats',[__CLASS__,'dashboard']);
  }
  public static function add_box():void{add_meta_box('pra_ad_details','Contrato e posicionamento comercial',[__CLASS__,'box'],'pra_campaign','normal','high');}
  public static function box(WP_Post $post):void{
    wp_nonce_field('pra_ad_save','pra_ad_nonce');
    $get=fn($key)=>get_post_meta($post->ID,'_pra_ad_'.$key,true);
    $url=(string)$get('url');$cta=(string)$get('cta');$position=(string)$get('position');
    echo '<p><label>Link de destino <input style="width:100%" type="url" name="pra_ad_url" value="'.esc_attr($url).'" placeholder="https://anunciante.com.br"></label></p>';
    echo '<p><label>Chamada comercial <input style="width:100%" type="text" name="pra_ad_cta" value="'.esc_attr($cta).'" maxlength="100" placeholder="Conheça a empresa"></label></p>';
    echo '<p><label>Posição <select name="pra_ad_position">';foreach(['sidebar'=>'Barra lateral','home'=>'Destaque comercial','both'=>'Ambas'] as $v=>$label)echo '<option value="'.esc_attr($v).'" '.selected($position,$v,false).'>'.esc_html($label).'</option>';echo '</select></label></p>';
    foreach(['start'=>'Início (AAAA-MM-DD)','end'=>'Fim (AAAA-MM-DD)'] as $key=>$label)echo '<p><label>'.esc_html($label).' <input type="date" name="pra_ad_'.esc_attr($key).'" value="'.esc_attr((string)$get($key)).'"></label></p>';
    echo '<p><label>Mensalidade contratada em reais (somente relatório; não cobra): <input type="number" min="0" max="100000" step="0.01" name="pra_ad_value" value="'.esc_attr((string)$get('value')).'"></label></p>';
    echo '<p><label><input type="checkbox" name="pra_ad_active" value="1" '.checked((int)$get('active'),1,false).'> Campanha liberada para exibição, se estiver publicada e dentro das datas</label></p>';
    echo '<p>Use a <strong>imagem destacada</strong> como banner; ela deve ser autorizada. O anúncio sempre será marcado como PUBLICIDADE. O cadastro não gera cobrança, contrato assinado ou fatura.</p>';
  }
  public static function save(int $post_id,WP_Post $post):void{
    if(!isset($_POST['pra_ad_nonce'])||!wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['pra_ad_nonce'])),'pra_ad_save'))return;
    if(defined('DOING_AUTOSAVE') && DOING_AUTOSAVE)return;
    if(wp_is_post_revision($post_id)||!current_user_can('manage_options'))return;
    $url=isset($_POST['pra_ad_url'])?esc_url_raw(wp_unslash($_POST['pra_ad_url'])):'';
    if($url && (!str_starts_with($url,'https://') || !wp_http_validate_url($url)))$url='';
    update_post_meta($post_id,'_pra_ad_url',$url);
    update_post_meta($post_id,'_pra_ad_cta',isset($_POST['pra_ad_cta'])?mb_substr(sanitize_text_field(wp_unslash($_POST['pra_ad_cta'])),0,100):'');
    $pos=isset($_POST['pra_ad_position'])?sanitize_key(wp_unslash($_POST['pra_ad_position'])):'sidebar';
    update_post_meta($post_id,'_pra_ad_position',in_array($pos,['sidebar','home','both'],true)?$pos:'sidebar');
    foreach(['start','end'] as $k){$s=isset($_POST['pra_ad_'.$k])?sanitize_text_field(wp_unslash($_POST['pra_ad_'.$k])):'';update_post_meta($post_id,'_pra_ad_'.$k,preg_match('/^\d{4}-\d{2}-\d{2}$/',$s)?$s:'');}
    $raw=isset($_POST['pra_ad_value'])?(string)wp_unslash($_POST['pra_ad_value']):'0';$v=max(0,min(100000,(float)$raw));
    update_post_meta($post_id,'_pra_ad_value',number_format($v,2,'.',''));
    update_post_meta($post_id,'_pra_ad_active',isset($_POST['pra_ad_active'])?1:0);
  }
  private static function active():array{
    $posts=get_posts(['post_type'=>'pra_campaign','post_status'=>'publish','numberposts'=>120,'orderby'=>'date','order'=>'DESC','suppress_filters'=>false]);
    $today=wp_date('Y-m-d');$result=[];
    foreach($posts as $post){
      if(!(int)get_post_meta($post->ID,'_pra_ad_active',true))continue;
      $a=(string)get_post_meta($post->ID,'_pra_ad_start',true);$b=(string)get_post_meta($post->ID,'_pra_ad_end',true);
      if(($a && $a>$today)||($b && $b<$today))continue;
      $link=(string)get_post_meta($post->ID,'_pra_ad_url',true);
      if(!$link)continue;
      $result[]=$post;
    }
    return $result;
  }
  public static function shortcode(array $atts=[]):string{
    $atts=shortcode_atts(['position'=>'sidebar','limit'=>2],$atts,'pra_anuncios');
    $position=in_array($atts['position'],['sidebar','home'],true)?$atts['position']:'sidebar';
    $limit=max(1,min(8,(int)$atts['limit']));$count=0;$html='';
    foreach(self::active() as $post){
      $pos=(string)get_post_meta($post->ID,'_pra_ad_position',true);
      if(!in_array($pos,[$position,'both'],true))continue;
      $url=(string)get_post_meta($post->ID,'_pra_ad_url',true);
      $cta=(string)get_post_meta($post->ID,'_pra_ad_cta',true);
      $html.='<article class="pra-ad" aria-label="Publicidade"><span class="pra-ad-label">PUBLICIDADE</span><a class="pra-ad-link" href="'.esc_url($url).'" rel="nofollow sponsored noopener noreferrer" target="_blank">';
      if(has_post_thumbnail($post->ID))$html.=get_the_post_thumbnail($post->ID,'medium',['loading'=>'lazy','alt'=>'Anúncio de '.get_the_title($post->ID)]);
      $html.='<strong>'.esc_html(get_the_title($post->ID)).'</strong>';
      if($cta)$html.='<small>'.esc_html($cta).'</small>';
      $html.='</a></article>'; $count++;if($count>=$limit)break;
    }
    return $html;
  }
  public static function dashboard():void{
    if(!current_user_can('manage_options'))return;
    $active=self::active();$amount=0;$expiring=0;
    foreach($active as $post){$amount+=(float)get_post_meta($post->ID,'_pra_ad_value',true);
      $end=(string)get_post_meta($post->ID,'_pra_ad_end',true);
      if($end && $end<=wp_date('Y-m-d',time()+30*DAY_IN_SECONDS))$expiring++;
    }
    $fmt=number_format($amount,2,',','.');
    echo '<div class="wrap"><h1>Receita publicitária — painel operacional</h1><p>Campanhas vigentes: <strong>'.count($active).'</strong> | Mensalidades cadastradas em campanhas vigentes: <strong>R$ '.esc_html($fmt).'</strong> | Vencem nos próximos 30 dias: <strong>'.(int)$expiring.'</strong></p>';
    echo '<p><strong>Atenção:</strong> o valor é contratual cadastrado, não faturamento reconhecido, pagamento recebido ou lucro. Não existe faturamento automático. Para registrar contratos assinados e recebimentos, integre sistema financeiro externo.</p>';
    echo '<p><a class="button button-primary" href="'.esc_url(admin_url('post-new.php?post_type=pra_campaign')).'">Cadastrar patrocínio</a> <a class="button" href="'.esc_url(admin_url('edit.php?post_type=pra_campaign')).'">Ver campanhas</a></p>';
    echo '<h2>Como exibir</h2><p>O tema inclui <code>[pra_anuncios position="sidebar"]</code>. Também pode usar <code>[pra_anuncios position="home"]</code> em uma página ou bloco de shortcode.</p>';
    echo '<p>Identifique todo conteúdo comercial, mantenha política de anúncios, autorização para marcas/imagens e atribuição de responsabilidade.</p></div>';
  }
}
PR_Ads::init();

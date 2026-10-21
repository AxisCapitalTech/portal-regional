<?php
/** Monitor de fontes oficiais. Somente metadados e links; nunca publica matérias. */
if (!defined('ABSPATH')) exit;
require_once __DIR__.'/class-pra-source-parser.php';
final class PR_Official {
  private const OPT='pra_official_options';
  private const LOG='pra_official_report';
  private const HEALTH='pra_official_health';
  private const EVENT='pra_official_hourly';
  private const LOCK='pra_official_lock';
  private const META='_pra_source_fingerprint';
  private const SOURCES=[
    'arapongas'=>['label'=>'Prefeitura de Arapongas','url'=>'https://arapongas.atende.net/cidadao/noticia','city'=>'Arapongas','kind'=>'html'],
    'londrina'=>['label'=>'Blog Oficial de Londrina','url'=>'https://blog.londrina.pr.gov.br/','rss'=>'https://blog.londrina.pr.gov.br/feed/','city'=>'Londrina','kind'=>'rss-html'],
    'maringa'=>['label'=>'Prefeitura de Maringá','url'=>'https://www.maringa.pr.gov.br/noticias','city'=>'Maringá','kind'=>'html'],
    'apucarana'=>['label'=>'Prefeitura de Apucarana','url'=>'https://www.apucarana.pr.gov.br/site/noticias/','city'=>'Apucarana','kind'=>'html'],
    'aen'=>['label'=>'Agência Estadual de Notícias (Paraná)','url'=>'https://www.parana.pr.gov.br/aen/noticias','city'=>'Paraná','kind'=>'html']
  ];
  public static function init(): void {
    add_action('admin_menu',[__CLASS__,'menu']);
    add_action('admin_post_pra_official_save',[__CLASS__,'save']);
    add_action('admin_post_pra_official_run',[__CLASS__,'manual']);
    add_action(self::EVENT,[__CLASS__,'collect']);
    add_action('init',[__CLASS__,'ensure_schedule']);
  }
  private static function defaults(): array {return ['enabled'=>0,'aen'=>1,'arapongas'=>1,'londrina'=>1,'maringa'=>1,'apucarana'=>1];}
  private static function options(): array {return wp_parse_args((array)get_option(self::OPT,[]),self::defaults());}
  public static function activate(): void {self::ensure_schedule();}
  public static function ensure_schedule(): void {
    if (get_option(self::OPT)===false) add_option(self::OPT,self::defaults(),'',false);
    if(!wp_next_scheduled(self::EVENT))wp_schedule_event(time()+270,'hourly',self::EVENT);
    if(get_option('pra_category_setup_version')!=='3'){foreach(['Arapongas','Londrina','Maringá','Apucarana'] as $c)self::category_ids($c);self::category_ids('Paraná');update_option('pra_category_setup_version','3',false);}
  }
  public static function deactivate(): void {wp_clear_scheduled_hook(self::EVENT);delete_transient(self::LOCK);}
  public static function menu():void {add_management_page('Fontes oficiais — Portal Regional','Fontes Oficiais','manage_options','pra-oficiais',[__CLASS__,'page']);}
  public static function page(): void {
    if(!current_user_can('manage_options'))return;
    $o=self::options();$r=(array)get_option(self::LOG,[]);$health=(array)get_option(self::HEALTH,[]);
    echo '<div class="wrap"><h1>Fontes oficiais — cidades prioritárias</h1><p>Monitora Arapongas, Londrina, Maringá, Apucarana e Paraná. Cria <strong>pautas pendentes</strong> com título e link; a IA não publica reportagens. O horário mostrado é o de coleta, nunca presumido como horário de publicação.</p>';
    echo '<p>Última execução: <strong>'.esc_html(isset($r['finished'])?wp_date('d/m/Y H:i',(int)$r['finished']):'Nunca').'</strong>. Itens novos: <strong>'.(int)($r['created']??0).'</strong> · Duplicatas: <strong>'.(int)($r['duplicates']??0).'</strong>.</p>';
    echo '<table class="widefat striped"><thead><tr><th>Fonte</th><th>Status da última tentativa</th><th>Última coleta bem-sucedida</th><th>Novas pautas</th><th>Duplicatas</th></tr></thead><tbody>';
    foreach(self::SOURCES as $key=>$src){
      $x=$r['sources'][$key]??[];
      $last=(int)($health[$key]['last_success']??0);
      $fresh=$last && (time()-$last)<=3*HOUR_IN_SECONDS;
      $indicator=$last ? wp_date('d/m/Y H:i',$last).($fresh?' · atualizada':' · há mais de 3 horas') : 'Não confirmada';
      $status=(string)($x['status']??'Ainda não testado');
      if(empty($o[$key]))$status='Desativada';
      echo '<tr><td>'.esc_html($src['label']).'</td><td>'.esc_html($status).'</td><td>'.esc_html($indicator).'</td><td>'.(int)($x['created']??0).'</td><td>'.(int)($x['duplicates']??0).'</td></tr>';
    }
    echo '</tbody></table>';
    echo '<form method="post" action="'.esc_url(admin_url('admin-post.php')).'"><input type="hidden" name="action" value="pra_official_save">';wp_nonce_field('pra_official_save');
    echo '<p><label><input type="checkbox" name="enabled" value="1" '.checked((int)$o['enabled'],1,false).'> Habilitar execução automática a cada hora (após homologação)</label></p>';
    foreach(self::SOURCES as $key=>$src)echo '<p><label><input type="checkbox" name="source_'.esc_attr($key).'" value="1" '.checked((int)$o[$key],1,false).'> '.esc_html($src['label']).' <code>'.esc_html($src['kind']).'</code></label></p>';
    submit_button('Salvar fontes');echo '</form>';
    echo '<form method="post" action="'.esc_url(admin_url('admin-post.php')).'"><input type="hidden" name="action" value="pra_official_run">';wp_nonce_field('pra_official_run');submit_button('Coletar manualmente para testar','secondary');echo '</form>';
    echo '<h2>Consultas complementares</h2><p><a target="_blank" rel="noopener noreferrer" href="https://arapongas.atende.net/diariooficial/edicao/">Diário Oficial de Arapongas</a> · <a target="_blank" rel="noopener noreferrer" href="https://portal.londrina.pr.gov.br/index.php/busca-jornal">Jornal Oficial de Londrina</a>. Documentos PDF não são ingeridos automaticamente nesta fase.</p>';
    echo '<p><strong>Notas:</strong> HTML pode mudar; feeds podem ficar indisponíveis. O painel indicará erro por fonte. Fontes com conteúdo sensível exigem revisão reforçada e toda matéria publicada deve ser escrita e verificada pela equipe editorial.</p></div>';
  }
  public static function save():void {
    if(!current_user_can('manage_options'))wp_die('Acesso negado.');check_admin_referer('pra_official_save');
    $out=['enabled'=>isset($_POST['enabled'])?1:0];foreach(self::SOURCES as $k=>$src)$out[$k]=isset($_POST['source_'.$k])?1:0;
    update_option(self::OPT,$out,false);wp_safe_redirect(admin_url('tools.php?page=pra-oficiais'));exit;
  }
  public static function manual():void {
    if(!current_user_can('manage_options'))wp_die('Acesso negado.');check_admin_referer('pra_official_run');
    self::collect(true);wp_safe_redirect(admin_url('tools.php?page=pra-oficiais'));exit;
  }
  private static function html_items(string $key,array $src):array {
    $res=wp_safe_remote_get($src['url'],[
      'timeout'=>15,'redirection'=>2,'limit_response_size'=>2000000,
      'headers'=>['Accept'=>'text/html','User-Agent'=>'PortalRegionalTriagem/0.5 (pautas com atribuicao; contato editorial no portal)']
    ]);
    if(is_wp_error($res))throw new RuntimeException('Conexão indisponível');
    if((int)wp_remote_retrieve_response_code($res)!==200)throw new RuntimeException('HTTP '.(int)wp_remote_retrieve_response_code($res));
    $content_type = (string) wp_remote_retrieve_header($res, 'content-type');
    if ($content_type && !preg_match('~(?:text/html|application/xhtml\+xml)~i', $content_type)) throw new RuntimeException('Resposta não é HTML');
    $html=wp_remote_retrieve_body($res);
    if(strlen($html)<350)throw new RuntimeException('HTML muito curto, talvez carregamento dinâmico');
    $links=PR_Source_Parser::extract($html,$key,16);
    if(!$links)throw new RuntimeException('Layout mudou ou lista só é carregada por JavaScript');
    return $links;
  }
  private static function london_rss(array $src):array {
    require_once ABSPATH.WPINC.'/feed.php';
    $feed=fetch_feed($src['rss']);
    if(is_wp_error($feed))throw new RuntimeException('Feed indisponível');
    $out=[];
    foreach($feed->get_items(0,min(16,$feed->get_item_quantity(16))) as $item){
      $url=(string)$item->get_permalink();
      // RSS da prefeitura pode ter permalinks amigáveis; aceita somente hospedagem oficial e HTTPS.
      $p=wp_parse_url($url);
      if(!is_array($p) || ($p['host']??'')!=='blog.londrina.pr.gov.br' || ($p['scheme']??'')!=='https')continue;
      $title=sanitize_text_field(wp_strip_all_tags((string)$item->get_title()));
      if(mb_strlen($title)<23)continue;
      $date=(int)$item->get_date('U');
      if(!$date || $date<time()-14*DAY_IN_SECONDS || $date>time()+DAY_IN_SECONDS)continue;
      $out[]=['title'=>$title,'link'=>esc_url_raw($url),'date'=>$date];
    }
    if(!$out)throw new RuntimeException('Feed sem publicações recentes');
    return $out;
  }
  private static function items(string $key,array $src):array {
    if($key==='londrina') {
      try{return self::london_rss($src);}catch(Throwable $e){return self::html_items($key,$src);}
    }
    return self::html_items($key,$src);
  }
  private static function category_ids(string $city):array {
    $regional=in_array($city,['Arapongas','Londrina','Maringá','Apucarana'],true);
    $parent=$regional?'Região':'Paraná';
    $p=term_exists($parent,'category');if(!$p)$p=wp_insert_term($parent,'category');
    if(is_wp_error($p))return [];
    $parent_id=is_array($p)?(int)$p['term_id']:(int)$p;
    if(!$regional)return [$parent_id];
    $t=term_exists($city,'category');if(!$t)$t=wp_insert_term($city,'category',['parent'=>$parent_id]);
    if(is_wp_error($t))return [$parent_id];
    $city_id=is_array($t)?(int)$t['term_id']:(int)$t;
    return [$parent_id,$city_id];
  }
  private static function risky(string $title):bool {
    $s=mb_strtolower(remove_accents($title),'UTF-8');
    foreach(['homicid','assassin','estup','investig','acusad','suspeit','prisao','preso','policia','denunc','fraud','desvi','violenc','morte','morre','obit','prefeit','candidat','eleic','partid','governad','saude','medic','hospital','vacina'] as $word)if(str_contains($s,$word))return true;
    return false;
  }
  private static function duplicate(string $fingerprint):bool {
    global $wpdb;
    return(bool)$wpdb->get_var($wpdb->prepare("SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key=%s AND meta_value=%s LIMIT 1",self::META,$fingerprint));
  }
  private static function create(array $item,array $src,string $key):string {
    $fp=hash('sha256',(string)$item['link']);if(self::duplicate($fp))return 'duplicate';
    $title=mb_substr(sanitize_text_field((string)$item['title']),0,200);
    $date=(int)($item['date']??0);$high=self::risky($title);
    $body='<p><strong>PAUTA DE MONITORAMENTO — NÃO PUBLICAR SEM REVISÃO.</strong></p>';
    $body.='<p><strong>Fonte:</strong> '.esc_html($src['label']).' · <strong>Cidade:</strong> '.esc_html($src['city']).' · <strong>Data da publicação:</strong> '.($date?esc_html(wp_date('d/m/Y H:i',$date)):'não identificada').'</p>';
    $body.='<p><a href="'.esc_url($item['link']).'" rel="noopener noreferrer nofollow" target="_blank">Abrir publicação na fonte oficial</a></p>';
    $body.='<p>Este monitor recuperou apenas um título e um link. Antes de redigir, verificar data, fatos, autoria, contexto, licença e eventuais atualizações.</p>';
    if($high)$body.='<p><strong>ALTO RISCO EDITORIAL:</strong> conferir integralmente com responsável humano antes de considerar publicação.</p>';
    $id=wp_insert_post(['post_type'=>'post','post_status'=>'pending','post_title'=>'[PAUTA] '.$title,'post_content'=>wp_kses_post($body),'post_excerpt'=>'Pauta em conferência, não publicada.','post_category'=>self::category_ids($src['city']),'comment_status'=>'closed'],true);
    if(is_wp_error($id))return 'error';
    update_post_meta($id,self::META,$fp);update_post_meta($id,'_pra_source_url',$item['link']);
    update_post_meta($id,'_pra_source_name',$src['label']);update_post_meta($id,'_pra_source_date',$date);
    update_post_meta($id,'_pra_city',$src['city']);update_post_meta($id,'_pra_sensitivity',$high?'alto':'normal');
    update_post_meta($id,'_pra_capture_time',time());update_post_meta($id,'_pra_connector',$key);
    return 'created';
  }
  public static function collect(bool $manual=false):void {
    $o=self::options();if(!$manual && empty($o['enabled']))return;
    if(get_transient(self::LOCK))return;set_transient(self::LOCK,1,25*MINUTE_IN_SECONDS);
    $log=['started'=>time(),'created'=>0,'duplicates'=>0,'sources'=>[]];
    $health=(array)get_option(self::HEALTH,[]);
    try {
      foreach(self::SOURCES as $key=>$src){
        if(empty($o[$key])){$log['sources'][$key]=['status'=>'Desativada'];continue;}
        $x=['status'=>'Não executada','created'=>0,'duplicates'=>0,'found'=>0,'errors'=>0];
        try {
          $items=self::items($key,$src);$x['found']=count($items);
          foreach($items as $item){
            $status=self::create($item,$src,$key);
            if($status==='created'){$x['created']++;$log['created']++;}
            elseif($status==='duplicate'){$x['duplicates']++;$log['duplicates']++;}
            else {$x['status']='Falha parcial ao gravar pauta'; $x['errors']++;}
          }
          if($x['status']==='Não executada')$x['status']='Fonte lida; revisar pautas e datas';
          // A source is healthy only if every item was processed without a write error.
          if ($x['errors']===0) {
            $health[$key]['last_success']=time();
            $health[$key]['last_items']=count($items);
            $health[$key]['last_error']='';
          } else {
            $health[$key]['last_error']='Falha parcial ao salvar algumas pautas';
          }
        }catch(Throwable $e){$x['status']='Erro: '.mb_substr($e->getMessage(),0,110);$health[$key]['last_error']=mb_substr($e->getMessage(),0,110);error_log('Portal Regional fonte '.str_replace(['\r','\n'], '',$key).': '.$e->getMessage());}
        $health[$key]['last_attempt']=time();
        $log['sources'][$key]=$x;
      }
    }finally{$log['finished']=time();update_option(self::LOG,$log,false);update_option(self::HEALTH,$health,false);delete_transient(self::LOCK);}
  }
}
PR_Official::init();

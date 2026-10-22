<?php
/** Captura de assinaturas por município usando a API pública do MailPoet. */
if (!defined('ABSPATH')) exit;
final class PR_Newsletter {
  private const OPT='pra_newsletter_lists';
  private const CITIES=['arapongas'=>'Arapongas','londrina'=>'Londrina','maringa'=>'Maringá','apucarana'=>'Apucarana','rolandia'=>'Rolândia','outras'=>'Outras cidades'];
  public static function init(): void {
    add_action('admin_menu',[__CLASS__,'menu']);
    add_action('admin_post_pra_newsletter_save',[__CLASS__,'save']);
    add_action('admin_post_nopriv_pra_subscribe',[__CLASS__,'subscribe']);
    add_action('admin_post_pra_subscribe',[__CLASS__,'subscribe']);
    add_shortcode('pra_newsletter',[__CLASS__,'shortcode']);
  }
  /** Fail closed until explicitly authorized and a published policy exists. */
  public static function can_accept():bool {
    if(!defined('PR1_NEWSLETTER_ENABLED') || PR1_NEWSLETTER_ENABLED!==true)return false;
    $id=(int)get_option('wp_page_for_privacy_policy',0);
    $page=$id?get_post($id):null;
    return $page && $page->post_status==='publish' && $page->post_type==='page'
      && trim(wp_strip_all_tags($page->post_content))!=='' && get_privacy_policy_url()!=='';
  }
  private static function lists():array {return (array)get_option(self::OPT,[]);}
  public static function menu():void{add_management_page('Newsletter Regional','Newsletter Regional','manage_options','pra-newsletter',[__CLASS__,'page']);}
  public static function page():void{
    if(!current_user_can('manage_options'))return;
    $opts=self::lists();
    echo '<div class="wrap"><h1>Newsletter por cidade</h1><p>Integração com <strong>MailPoet</strong>, usando a API pública. Instale e configure o plugin MailPoet, habilite confirmação de inscrição (double opt-in) e configure um método de envio válido antes de coletar e-mails.</p>';
    echo '<p>Estado do MailPoet: <strong>'.(class_exists('\\MailPoet\\API\\API')?'instalado':'não instalado').'</strong></p>';
    if(class_exists('\\MailPoet\\API\\API')){
      try{$ls=\MailPoet\API\API::MP('v1')->getLists();echo '<p><strong>Listas disponíveis:</strong> ';
        $labels=[];foreach($ls as $li)if(($li['deleted_at']??null)===null && ($li['type']??'')==='default')$labels[]=esc_html((string)$li['name'].' (ID '.(int)$li['id'].')');
        echo implode(' · ',$labels).'</p>';
      }catch(Throwable $e){echo '<p>Não foi possível consultar as listas no MailPoet.</p>';}
    }
    echo '<form method="post" action="'.esc_url(admin_url('admin-post.php')).'"><input type="hidden" name="action" value="pra_newsletter_save">';wp_nonce_field('pra_newsletter_save');
    echo '<table class="form-table">';foreach(self::CITIES as $key=>$name){echo '<tr><th>'.esc_html($name).'</th><td><input type="number" min="1" name="list_'.esc_attr($key).'" value="'.esc_attr((string)($opts[$key]??'')).'" placeholder="ID da lista MailPoet" class="regular-text"></td></tr>';}
    echo '</table>';submit_button('Salvar listas');echo '</form>';
    echo '<p>Formulário: use o shortcode <code>[pra_newsletter]</code>, já incluído na página inicial do tema. Sem MailPoet e sem lista definida, o formulário não recebe contatos. Para envios programados, configure as campanhas de notificação de posts no MailPoet por lista.</p>';
    echo '<p><strong>LGPD:</strong> consentimento explícito, política de privacidade acessível, gestão de preferências e cancelamento em cada e-mail. Não importar números ou e-mails sem autorização.</p></div>';
  }
  public static function save():void{
    if(!current_user_can('manage_options'))wp_die('Acesso negado.');check_admin_referer('pra_newsletter_save');
    $out=[];foreach(self::CITIES as $key=>$label)$out[$key]=isset($_POST['list_'.$key])?absint(wp_unslash($_POST['list_'.$key])):0;
    update_option(self::OPT,$out,false);wp_safe_redirect(admin_url('tools.php?page=pra-newsletter'));exit;
  }
  public static function shortcode():string{
    if(!self::can_accept())return '<div class="pra-newsletter-note">Cadastro de newsletter indisponível até autorização e validação da política de privacidade.</div>';
    $o=self::lists();$ready=class_exists('\\MailPoet\\API\\API') && count(array_filter($o));
    if(!$ready)return '<div class="pra-newsletter-note">Boletim regional em implantação. Cadastro será disponibilizado após ativação do serviço de e-mail e confirmação de inscrição.</div>';
    $message=isset($_GET['pra_nl'])?sanitize_key(wp_unslash($_GET['pra_nl'])):'';
    ob_start();
    if($message==='ok')echo '<p role="status">Se o cadastro for aceito, você receberá uma mensagem para confirmar sua inscrição.</p>';
    if($message==='error')echo '<p role="alert">Não foi possível concluir o cadastro. Tente novamente mais tarde.</p>';
    ?>
    <form class="pra-newsletter-form" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" method="post">
      <input type="hidden" name="action" value="pra_subscribe">
      <?php wp_nonce_field('pra_subscribe','pra_nonce'); ?>
      <label for="pra-email">Seu e-mail</label>
      <input id="pra-email" name="email" type="email" autocomplete="email" maxlength="150" required placeholder="nome@exemplo.com">
      <label for="pra-city">Cidade de interesse</label>
      <select id="pra-city" name="city" required>
        <?php foreach(self::CITIES as $key=>$name)if(!empty($o[$key]))echo '<option value="'.esc_attr($key).'">'.esc_html($name).'</option>'; ?>
      </select>
      <label class="pra-consent"><input name="consent" type="checkbox" value="1" required> Quero receber notícias por e-mail desta região e aceito a <a href="<?php echo esc_url(get_privacy_policy_url() ?: home_url('/politica-de-privacidade/')); ?>">política de privacidade</a>.</label>
      <label class="pra-hidden" aria-hidden="true">Não preencha <input type="text" name="company_website" value="" tabindex="-1" autocomplete="off"></label>
      <button type="submit">Quero receber o boletim</button>
    </form>
    <?php return (string)ob_get_clean();
  }
  public static function subscribe():void{
    if(!self::can_accept())wp_die('Cadastro de newsletter indisponível.', '', ['response'=>403]);
    $redirect=wp_get_referer()?:home_url('/');$redirect=wp_validate_redirect($redirect,home_url('/'));
    if(strtoupper((string)($_SERVER['REQUEST_METHOD']??''))!=='POST')wp_die('Método inválido.');
    if(!isset($_POST['pra_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['pra_nonce'])),'pra_subscribe')){wp_safe_redirect(add_query_arg('pra_nl','error',$redirect));exit;}
    if(!empty($_POST['company_website'])){wp_safe_redirect(add_query_arg('pra_nl','ok',$redirect));exit;}
    $email=isset($_POST['email'])?sanitize_email(wp_unslash($_POST['email'])):'';
    $city=isset($_POST['city'])?sanitize_key(wp_unslash($_POST['city'])):'';
    $lists=self::lists();$id=absint($lists[$city]??0);
    if(!is_email($email)||!$id||!isset($_POST['consent'])||!class_exists('\\MailPoet\\API\\API')){wp_safe_redirect(add_query_arg('pra_nl','error',$redirect));exit;}
    $rate='pra_signup_'.hash_hmac('sha256',strtolower($email),wp_salt('nonce'));
    if(get_transient($rate)){wp_safe_redirect(add_query_arg('pra_nl','ok',$redirect));exit;}
    set_transient($rate,1,120);
    try{
      $api=\MailPoet\API\API::MP('v1');
      try{$existing=$api->getSubscriber($email);}catch(Throwable $e){if((int)$e->getCode()!==4)throw $e;$existing=null;}
      if(!$existing)$api->addSubscriber(['email'=>$email],[$id],['send_confirmation_email'=>true]);
      elseif(in_array((string)($existing['status']??''),['subscribed','unconfirmed'],true))$api->subscribeToLists($email,[$id],['send_confirmation_email'=>true]);
      // Descadastros e endereços bloqueados nunca são reativados por esta integração.
      wp_safe_redirect(add_query_arg('pra_nl','ok',$redirect));exit;
    }catch(Throwable $e){error_log('Portal Regional newsletter: code '.(int)$e->getCode());delete_transient($rate);wp_safe_redirect(add_query_arg('pra_nl','error',$redirect));exit;}
  }
}
PR_Newsletter::init();

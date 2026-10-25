<?php
/** E21: WordPress authenticated evidence review. Never publishes posts. */
if (!defined('ABSPATH')) exit;
require_once __DIR__ . '/class-pra-source-evidence.php';
final class PRA_Source_Review {
  public static function init(): void {
    add_action('admin_post_pra_verify_source', [__CLASS__, 'submit']);
    add_action('admin_menu', [__CLASS__, 'menu']);
    add_action('post_submitbox_misc_actions', [__CLASS__, 'editor_link']);
  }
  /** Only editors can certify sources; contributors must not self-attest. */
  public static function may_verify(int $post_id): bool {
    return $post_id > 0 && current_user_can('edit_post', $post_id) && current_user_can('edit_others_posts');
  }
  public static function editor_link(): void {
    global $post;
    if (!$post || $post->post_type !== 'post' || $post->post_status !== 'pending' || !self::may_verify((int)$post->ID)) return;
    $link = admin_url('tools.php?page=pra-source-review&post_id='.(int)$post->ID);
    echo '<div class="misc-pub-section"><a href="'.esc_url($link).'">PR1: verificar fonte da pauta</a></div>';
  }
  public static function menu(): void {
    add_management_page('PR1 — Verificar fonte', 'PR1 — Verificar fonte', 'edit_others_posts', 'pra-source-review', [__CLASS__, 'render']);
  }
  public static function render(): void {
    $post=get_post(absint($_GET['post_id']??0));
    if (!$post) { echo '<div class="wrap"><h1>PR1 — Verificar fonte</h1><p>Abra esta tela com <code>?page=pra-source-review&post_id=123</code> usando uma pauta pendente.</p></div>'; return; }
    if ($post->post_status !== 'pending' || !self::may_verify((int)$post->ID)) {
      echo '<p>Disponível somente para pautas pendentes e editores autorizados.</p>'; return;
    }
    echo '<p>Confirme manualmente o conteúdo da fonte antes de atestar. Não equivale a uma checagem factual automática.</p>';
    echo '<form method="post" action="'.esc_url(admin_url('admin-post.php')).'">';
    wp_nonce_field('pra_verify_source_'.$post->ID);
    echo '<input type="hidden" name="action" value="pra_verify_source">';
    echo '<input type="hidden" name="post_id" value="'.esc_attr((string)$post->ID).'">';
    echo '<p><label>URL HTTPS da fonte<br><input style="width:100%" type="url" name="source_url" required value="'.esc_attr((string)get_post_meta($post->ID,'_pra_source_url',true)).'"></label></p>';
    echo '<p><label>Texto conferido (mínimo 200 caracteres)<br><textarea style="width:100%" rows="8" name="source_text" required>'.esc_textarea((string)get_post_meta($post->ID,'_pra_verified_source_text',true)).'</textarea></label></p>';
    echo '<button type="submit" class="button button-primary">Registrar verificação da fonte</button></form>';
  }
  public static function verify(int $post_id, string $url, string $text, int $actor_id): array {
    if ($post_id <= 0 || $actor_id <= 0 || !self::may_verify($post_id))
      return ['ok'=>false,'reason'=>'permission_denied'];
    $post = get_post($post_id);
    if (!$post || $post->post_type !== 'post' || $post->post_status !== 'pending')
      return ['ok'=>false,'reason'=>'post_not_pending'];
    $record=['url'=>$url,'text'=>$text,'sha256'=>hash('sha256',$text),
      'verification'=>'human_reviewed_source_text','verified_at'=>time()];
    if (!PRA_Source_Evidence::validate($record))
      return ['ok'=>false,'reason'=>'invalid_evidence'];
    // Any change must revoke the last canonical attestation BEFORE updating parts.
    // WordPress postmeta writes are not transactional; only the canonical record
    // below is read by the worker, so partial writes are never accepted.
    delete_post_meta($post_id,'_pra_source_evidence_e22');
    // A successful check only means a privileged editor attested to the supplied text.
    // The source is not independently authenticated or fact-checked by this routine.
    update_post_meta($post_id,'_pra_source_url',$record['url']);
    update_post_meta($post_id,'_pra_verified_source_text',$record['text']);
    update_post_meta($post_id,'_pra_verified_source_sha256',$record['sha256']);
    update_post_meta($post_id,'_pra_source_verification',$record['verification']);
    update_post_meta($post_id,'_pra_source_verified_at',$record['verified_at']);
    update_post_meta($post_id,'_pra_source_verified_by',$actor_id);
    $record['actor_id'] = $actor_id;
    if (!update_post_meta($post_id,'_pra_source_evidence_e22',$record)) {
      return ['ok'=>false,'reason'=>'evidence_commit_failed'];
    }
    delete_post_meta($post_id,'_pra_openai_worker_processed');
    $audit=get_post_meta($post_id,'_pra_source_review_audit',true);
    if (!is_array($audit)) $audit=[];
    $audit[]=['actor'=>$actor_id,'at'=>$record['verified_at'],'sha256'=>$record['sha256'],'url'=>$record['url']];
    if(count($audit)>50) $audit=array_slice($audit,-50);
    update_post_meta($post_id,'_pra_source_review_audit',$audit);
    return ['ok'=>true,'reason'=>'verified','sha256'=>$record['sha256']];
  }
  public static function submit(): void {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') wp_die('Method not allowed', '', ['response'=>405]);
    $id=absint($_POST['post_id']??0);
    if (!$id || !self::may_verify($id)) wp_die('Forbidden', '', ['response'=>403]);
    check_admin_referer('pra_verify_source_'.$id);
    $url=esc_url_raw(wp_unslash((string)($_POST['source_url']??'')));
    $text=wp_unslash((string)($_POST['source_text']??''));
    $result=self::verify($id,$url,$text,get_current_user_id());
    if (!$result['ok']) wp_die(esc_html($result['reason']), '', ['response'=>400]);
    wp_safe_redirect(admin_url('post.php?post='.$id.'&action=edit&pra_source_review=1'));
    exit;
  }
}
PRA_Source_Review::init();

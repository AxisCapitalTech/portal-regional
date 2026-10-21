<?php
/** Smoke the monitored-post publish barrier without a DB. */
define('ABSPATH', __DIR__.'/');
class WP_Post { public $ID; public $post_title; public $post_content; public function __construct($id, $title, $content) {$this->ID=$id; $this->post_title=$title; $this->post_content=$content;} }
function add_action(...$args){} function add_filter(...$args){} function add_shortcode(...$args){}
function register_activation_hook(...$args){} function register_deactivation_hook(...$args){}
function wp_unslash($v){return $v;}
function absint($v){return abs((int)$v);}
function sanitize_text_field($v){return trim((string)$v);}
function wp_verify_nonce($nonce,$action){return $nonce==='test-nonce';}
function wp_is_post_revision($id){return false;} function wp_is_post_autosave($id){return false;}
function current_user_can($cap,...$args){return true;} function get_current_user_id(){return 31;}
$GLOBALS['meta']=[10=>['_pra_source_url'=>'https://blog.londrina.pr.gov.br/?p=1']];
function get_post_meta($id,$key,$single=true){return $GLOBALS['meta'][$id][$key]??'';}
function update_post_meta($id,$key,$value){$GLOBALS['meta'][$id][$key]=$value;return true;}
function delete_post_meta($id,$key){unset($GLOBALS['meta'][$id][$key]);return true;}
require __DIR__.'/../wp-content/plugins/portal-regional-agentes/portal-regional-agentes.php';
function verify($label,$want,$got){if($want!==$got){fwrite(STDERR,"FAIL $label expected $want got $got\n");exit(1);}echo "OK $label\n";}
$data=['post_type'=>'post','post_status'=>'publish','post_title'=>'Notícia corrigida pelo editor','post_content'=>'Matéria com fonte e apuração.'];
$guard=fn($data)=>PR_Agentes::prevent_unreviewed_publish($data,['ID'=>10])['post_status'];
verify('monitored post not approved','pending',$guard($data));
$_POST=['pra_approval_nonce'=>'test-nonce','pra_editorial_confirmed'=>'1'];
PR_Agentes::record_editorial_approval(10,new WP_Post(10,$data['post_title'],$data['post_content']),true);
verify('approval ties to article text','publish',$guard($data));
$changed=$data;$changed['post_content'].=' Nova informação';
verify('edit after approval needs new review','pending',$guard($changed));
$marker=$data;$marker['post_title']='[PAUTA] Matéria de teste';
verify('marker blocks even signed post','pending',$guard($marker));
$_POST=['pra_approval_nonce'=>'test-nonce'];
PR_Agentes::record_editorial_approval(10,new WP_Post(10,$data['post_title'],$data['post_content']),true);
verify('approval withdrawn by unchecked save','pending',$guard($data));
$ordinary=$data;
verify('normal editorial post unaffected','publish',PR_Agentes::prevent_unreviewed_publish($ordinary,['ID'=>11])['post_status']);

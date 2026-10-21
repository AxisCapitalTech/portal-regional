<?php
/** Stub WordPress hook registration to test status guard without running a database. */
define('ABSPATH', __DIR__ . '/');
function add_action(...$args) {} function add_filter(...$args) {} function add_shortcode(...$args) {}
function register_activation_hook(...$args) {} function register_deactivation_hook(...$args) {}
function wp_unslash($value) {return $value;}
function absint($value){return abs((int)$value);}
function get_post_meta($id,$key,$single=true){return '';}
require __DIR__.'/../wp-content/plugins/portal-regional-agentes/portal-regional-agentes.php';
function verify($name, $expected, $data) {
    $actual = PR_Agentes::prevent_unreviewed_publish($data, [])['post_status'];
    if ($actual !== $expected) {fwrite(STDERR, "FAIL $name: $actual\n"); exit(1);}
    echo "OK $name\n";
}
verify('placeholder title blocked', 'pending', [
  'post_type'=>'post','post_status'=>'publish','post_title'=>'[PAUTA] Fonte oficial','post_content'=>'texto qualquer'
]);
verify('monitoring placeholder blocked', 'pending', [
  'post_type'=>'post','post_status'=>'future','post_title'=>'Nova matéria','post_content'=>'PAUTA DE MONITORAMENTO — NÃO PUBLICAR SEM REVISÃO'
]);
verify('newsroom rewrite can publish', 'publish', [
  'post_type'=>'post','post_status'=>'publish','post_title'=>'Matéria conferida','post_content'=>'Texto novo conferido e atribuído.'
]);
verify('different post types unaffected', 'publish', [
  'post_type'=>'pra_campaign','post_status'=>'publish','post_title'=>'[PAUTA] campanha','post_content'=>'Conteúdo comercial.'
]);
verify('pending remains pending', 'pending', [
  'post_type'=>'post','post_status'=>'pending','post_title'=>'[PAUTA] aguardando','post_content'=>'Pendente'
]);

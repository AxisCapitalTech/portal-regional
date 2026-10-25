<?php
/** Run via wp eval-file on test WP + MariaDB, never production. */
if (!defined('ABSPATH')) exit(1);
if (!class_exists('PRA_Source_Review') || !class_exists('PRA_Source_Evidence')) {
    fwrite(STDERR,"E22 plugin not active\n"); exit(1);
}
$admin = get_user_by('login','testadmin');
if (!$admin || !user_can($admin,'manage_options')) {fwrite(STDERR,"Test admin missing\n");exit(1);}
wp_set_current_user((int)$admin->ID);
$id = wp_insert_post(['post_type'=>'post','post_status'=>'pending','post_title'=>'PR1 E22 smoke','post_content'=>'Fictitious internal test'],true);
if (is_wp_error($id)) {fwrite(STDERR,"Test post creation failed\n");exit(1);}
try {
  $body = str_repeat('Documento fictício para teste de verificação editorial. ',8);
  $r=PRA_Source_Review::verify((int)$id,'https://example.org/e22-documento',$body,(int)$admin->ID);
  if (empty($r['ok'])) throw new RuntimeException('source verification failed');
  if (!PRA_Source_Evidence::from_post((int)$id)) throw new RuntimeException('canonical evidence not readable');
  update_post_meta((int)$id,'_pra_source_evidence_e22',['url'=>'https://example.org/e22-documento']);
  if (PRA_Source_Evidence::from_post((int)$id)) throw new RuntimeException('tampered evidence not blocked');
  if (get_post_status((int)$id)!=='pending') throw new RuntimeException('post status changed');
  if (PRA_OpenAI_Automation::allowed()) throw new RuntimeException('AI was enabled');
  echo "PASS WordPress/MySQL E22: verified -> tampered blocked; pending; AI disabled\n";
} finally {wp_delete_post((int)$id,true);}

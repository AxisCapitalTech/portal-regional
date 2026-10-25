<?php
/** wp eval-file: exclusão permanente libera o fingerprint; lixeira mantém. Sem rede. */
if (!defined('ABSPATH')) { fwrite(STDERR, "FAIL WordPress não carregado\n"); exit(1); }
require_once WP_PLUGIN_DIR.'/portal-regional-agentes/includes/class-pra-fingerprint.php';
$GLOBALS['fd_fail']=0; function fd_ok($c,$l){ if(!$c){fwrite(STDERR,"FAIL $l\n");$GLOBALS['fd_fail']++;} else echo "OK $l\n"; }
global $wpdb; PR_Fingerprint_Index::install(); $T=PR_Fingerprint_Index::table();
$fp=hash('sha256','delete-test-'.uniqid('',true));
$id=wp_insert_post(['post_type'=>'post','post_status'=>'draft','post_title'=>'fp delete test','post_content'=>'x']);
fd_ok(PR_Fingerprint_Index::reserve($fp),'reserva inicial'); PR_Fingerprint_Index::commit($fp,(int)$id);
wp_trash_post($id);
fd_ok((int)$wpdb->get_var($wpdb->prepare("SELECT post_id FROM $T WHERE fingerprint=%s",$fp))===(int)$id,'lixeira mantém o fingerprint');
wp_delete_post($id,true);
fd_ok($wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM $T WHERE fingerprint=%s",$fp))==='0','exclusão permanente remove o fingerprint');
fd_ok(PR_Fingerprint_Index::reserve($fp),'pauta pode ser reservada de novo'); PR_Fingerprint_Index::release($fp);
$orphan=hash('sha256','orphan-'.uniqid('',true)); $ghost=(int)$wpdb->get_var("SELECT MAX(ID) FROM {$wpdb->posts}")+1000;
$wpdb->insert($T,['fingerprint'=>$orphan,'post_id'=>$ghost,'created_at'=>gmdate('Y-m-d H:i:s')]);
update_option('pra_fingerprint_schema_version','2'); PR_Fingerprint_Index::install();
fd_ok($wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM $T WHERE fingerprint=%s",$orphan))==='0' && get_option('pra_fingerprint_schema_version')==='3','migração v3 remove fingerprints órfãos de posts excluídos');
if($GLOBALS['fd_fail'])exit(1); echo "OK fingerprint: exclusão permanente\n";

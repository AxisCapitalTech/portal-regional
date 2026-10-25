<?php
const ABSPATH='/tmp/';
$GLOBALS['meta']=[]; $GLOBALS['allowed']=true; $GLOBALS['editor']=true; $GLOBALS['post_status']='pending';
function add_action($a,$b){} function current_user_can($cap,$id=null){return $GLOBALS['allowed'] && ($cap!=='edit_others_posts' || $GLOBALS['editor']);}
function get_post($id){return (object)['post_type'=>'post','post_status'=>$GLOBALS['post_status']];}
function update_post_meta($id,$k,$v){$GLOBALS['meta'][$k]=$v;return true;}
function get_post_meta($id,$k,$single=true){return $GLOBALS['meta'][$k]??'';}
function delete_post_meta($id,$k){unset($GLOBALS['meta'][$k]);}
require_once __DIR__.'/../wp-content/plugins/portal-regional-agentes/includes/redacao/class-pra-source-review.php';
function check($ok,$name){if(!$ok){fwrite(STDERR,"FAIL $name\n");exit(1);}echo "PASS $name\n";}
$text=str_repeat('Trecho de documento conferido pelo editor. ',8);
$r=PRA_Source_Review::verify(123,'https://example.org/fonte',$text,7);
check($r['ok']===true,'editor autenticado pode atestar texto');
check(PRA_Source_Evidence::from_post(123)!==null,'worker valida evidência canonica');
check(isset($GLOBALS['meta']['_pra_source_evidence_e22']),'commit canonico preservado');
check(($GLOBALS['meta']['_pra_source_verified_by']??0)===7,'identidade do editor registrada');
check(count($GLOBALS['meta']['_pra_source_review_audit'])===1,'auditoria registra revisão');
$GLOBALS['allowed']=false;
check(!PRA_Source_Review::verify(123,'https://example.org/fonte',$text,8)['ok'],'usuário sem permissão bloqueado');
$GLOBALS['allowed']=true;$GLOBALS['editor']=false;
check(!PRA_Source_Review::verify(123,'https://example.org/fonte',$text,7)['ok'],'autor comum nao atesta');
$GLOBALS['editor']=true;$GLOBALS['post_status']='publish';
check(!PRA_Source_Review::verify(123,'https://example.org/fonte',$text,7)['ok'],'post publicado bloqueado');
$GLOBALS['post_status']='pending';
check(!PRA_Source_Review::verify(123,'http://example.org/fonte',$text,7)['ok'],'HTTP bloqueado');
check(!PRA_Source_Review::verify(123,'https://example.org/fonte','curto',7)['ok'],'texto curto bloqueado');
check(($GLOBALS['meta']['_pra_source_review_audit'][0]['actor']??0)===7,'negações não apagam trilha anterior');

$GLOBALS['meta']['_pra_verified_source_text']='texto adulterado';
check(PRA_Source_Evidence::from_post(123)!==null,'worker usa registro canonico e ignora campos parciais');
$GLOBALS['meta']['_pra_source_evidence_e22']['text']='texto adulterado';
check(PRA_Source_Evidence::from_post(123)===null,'worker bloqueia registro canonico adulterado');

<?php
/** Run ONLY through: wp eval-file tests/smoke-wordpress.php --path=/wordpress */
if (!defined('ABSPATH') || !class_exists('PR_Agentes') || !class_exists('PR_Official')) {
    fwrite(STDERR, "FAIL WordPress/plugins not loaded\n"); exit(1);
}
function require_condition($ok, $what) { if (!$ok) { fwrite(STDERR,"FAIL $what\n");exit(1); } echo "OK $what\n"; }
$editor = get_user_by('login', 'testadmin');
require_condition($editor instanceof WP_User, 'editorial test account exists');
wp_set_current_user((int) $editor->ID);
require_condition(current_user_can('edit_posts'), 'editor authenticated for approval');
require_condition((bool)wp_next_scheduled('pra_official_hourly'), 'official cron scheduled');
require_condition(file_exists(get_template_directory().'/assets/pr1-v7.css'), 'V7 stylesheet available');
require_condition(file_exists(get_template_directory().'/assets/pr1-v7.js'), 'V7 accessible reading script available');
require_condition((bool)locate_template('front-page.php'), 'V7 front page template available');
require_condition(function_exists('regional_public_brand') && regional_public_brand() === 'PR1 News', 'provisional public brand default');
require_condition(regional_brand_mark() === 'PR1', 'provisional brand mark default');
$previous_brand = get_theme_mod('regional_public_brand');
set_theme_mod('regional_public_brand', 'Marca Homologação');
require_condition(regional_public_brand() === 'Marca Homologação', 'brand can change without editing technical code');
if ($previous_brand === false) remove_theme_mod('regional_public_brand');
else set_theme_mod('regional_public_brand', $previous_brand);
require_condition(regional_public_brand() === 'PR1 News', 'brand restored');
require_condition((bool)get_term_by('name', 'Arapongas','category'), 'Arapongas category');
require_condition((bool)get_term_by('name', 'Maringá','category'), 'Maringá category');
require_condition((int)(get_option('pra_official_options')['enabled']??-1) === 0, 'automatic collection defaults off');
$id=wp_insert_post(['post_title'=>'[PAUTA] Verificação fictícia', 'post_content'=>'PAUTA DE MONITORAMENTO — NÃO PUBLICAR SEM REVISÃO','post_type'=>'post','post_status'=>'pending'],true);
require_condition(!is_wp_error($id),'test pending post created');
update_post_meta($id,'_pra_source_url','https://blog.londrina.pr.gov.br/?p=1');
$clean_title='Texto de homologação fictício revisado';
$clean_content='Conteúdo fictício e exclusivamente técnico para ensaio de segurança editorial.';
wp_update_post(['ID'=>$id,'post_title'=>$clean_title,'post_content'=>$clean_content,'post_status'=>'publish']);
require_condition(get_post_status($id)==='pending','unsigned monitored content cannot publish');
$_POST['pra_approval_nonce']=wp_create_nonce('pra_editorial_approval_'.$id);
$_POST['pra_editorial_confirmed']='1';
PR_Agentes::record_editorial_approval($id,get_post($id),true);
unset($_POST['pra_editorial_confirmed']);
wp_update_post(['ID'=>$id,'post_status'=>'publish']);
require_condition(get_post_status($id)==='publish','explicit signed and unchanged content can publish');
wp_update_post(['ID'=>$id,'post_content'=>$clean_content.' Alteração subsequente.', 'post_status'=>'publish']);
require_condition(get_post_status($id)==='pending','post-edit forces new review');
wp_delete_post($id,true);
echo "OK WordPress integration smoke complete\n";

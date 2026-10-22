<?php
define('ABSPATH',__DIR__.'/');
if(in_array('--authorized',$argv,true))define('PR1_NEWSLETTER_ENABLED',true);
$GLOBALS['page']=(object)['post_type'=>'page','post_status'=>'publish','post_content'=>'Synthetic privacy fixture'];
$GLOBALS['policy_url']='https://example.test/privacy/';
function add_action(...$args){}function add_shortcode(...$args){}
function get_option($key,$fallback=false){return $key==='wp_page_for_privacy_policy'?12:[];}
function get_post($id){return $GLOBALS['page'];}
function wp_strip_all_tags($s){return strip_tags($s);}
function get_privacy_policy_url(){return $GLOBALS['policy_url'];}
function wp_die(...$args){throw new RuntimeException('blocked');}
require __DIR__.'/../wp-content/plugins/portal-regional-agentes/includes/class-pra-newsletter.php';
function check($ok,$label){if(!$ok){fwrite(STDERR,"FAIL $label\n");exit(1);}echo "OK $label\n";}
if(!defined('PR1_NEWSLETTER_ENABLED')){
 check(!PR_Newsletter::can_accept(),'newsletter disabled by default');
 check(strpos(PR_Newsletter::shortcode(),'<form')===false,'disabled shortcode has no form');
 try{PR_Newsletter::subscribe();check(false,'direct POST blocked');}
 catch(RuntimeException $e){check($e->getMessage()==='blocked','direct POST blocked before MailPoet');}
}else{
 check(PR_Newsletter::can_accept(),'explicit authorization plus published policy');
 foreach(['draft','private','trash'] as $status){
  $GLOBALS['page']->post_status=$status;
  check(!PR_Newsletter::can_accept(),'policy status blocks signup: '.$status);
 }
 $GLOBALS['page']->post_status='publish';$GLOBALS['page']->post_content=' ';
 check(!PR_Newsletter::can_accept(),'empty policy blocks signup');
 $GLOBALS['page']->post_content='Fixture';$GLOBALS['policy_url']='';
 check(!PR_Newsletter::can_accept(),'missing policy URL blocks signup');
 try{PR_Newsletter::subscribe();check(false,'missing policy POST blocked');}
 catch(RuntimeException $e){check($e->getMessage()==='blocked','missing policy POST blocked before MailPoet');}
}

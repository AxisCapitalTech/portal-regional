<?php
define('ABSPATH',__DIR__.'/');
if(in_array('--approved',$argv,true))define('PR1_EDITORIAL_NOTICE_APPROVED',true);
function esc_html($s){return htmlspecialchars($s);}function esc_url($s){return $s;}
function regional_public_brand(){return 'PR1 News';}function wp_date($format){return '2026';}
function wp_get_environment_type(){return 'staging';}function home_url($p){return 'https://example.test'.$p;}
function wp_cache_get(...$args){return [];}function get_privacy_policy_url(){return '';}
function wp_footer(){}
ob_start();require __DIR__.'/../wp-content/themes/portal-regional-tema/footer.php';$html=ob_get_clean();
$approved=defined('PR1_EDITORIAL_NOTICE_APPROVED');
foreach(['Publicidade identificada e apuração editorial responsável','Como verificamos','não é canal oficial'] as $text){
 if(str_contains($html,$text)!==$approved){fwrite(STDERR,"FAIL footer approval\n");exit(1);}
}
echo 'OK footer editorial claims '.($approved?'explicitly approved':'absent by default')."\n";

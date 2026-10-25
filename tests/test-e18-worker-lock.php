<?php
/** Offline isolated test: no WordPress, no network, no real DB. */
define('ABSPATH', __DIR__ . '/');
function add_action($a,$b) {}
$opts=[];
function add_option($k,$v,$deprecated='',$autoload=false) {global $opts; if(array_key_exists($k,$opts)) return false; $opts[$k]=$v; return true;}
function get_option($k,$default=false) {global $opts;return $opts[$k]??$default;}
function delete_option($k) {global $opts;unset($opts[$k]);}
class FakeWPDB {
 public $options='wp_options';
 public function prepare($sql,...$params) {return ['sql'=>$sql,'params'=>$params];}
 public function query($prepared) {global $opts; if(str_starts_with($prepared['sql'],'DELETE')) {[$key,$old]=$prepared['params'];if((string)($opts[$key]??'')!==(string)$old)return 0;unset($opts[$key]);return 1;} [$new,$key,$old]=$prepared['params']; if((string)($opts[$key]??'')!==(string)$old) return 0; $opts[$key]=$new;return 1;}
}
$wpdb=new FakeWPDB();
require_once __DIR__.'/../wp-content/plugins/portal-regional-agentes/includes/redacao/class-pra-openai-automation.php';
function check($ok,$label) {if(!$ok){fwrite(STDERR,"FAIL $label\n");exit(1);} echo "PASS $label\n";}
check(PRA_OpenAI_Automation::acquire_lock(),'first owner obtains lock');
check(!PRA_OpenAI_Automation::acquire_lock(),'concurrent owner blocked');
$opts[PRA_OpenAI_Automation::LOCK]=(time()-PRA_OpenAI_Automation::LOCK_TTL-1).':'.str_repeat('f',32);
check(PRA_OpenAI_Automation::acquire_lock(),'expired lock recovered with compare-and-swap');
check(!PRA_OpenAI_Automation::acquire_lock(),'new owner not displaced');
$opts[PRA_OpenAI_Automation::LOCK]='invalid';
check(!PRA_OpenAI_Automation::acquire_lock(),'corrupt lock fails closed');
check(!(new PRA_OpenAI_OptIn_Client())->enabled(),'OpenAI client disabled');
check(!PRA_OpenAI_Automation::allowed(),'worker disabled');

$opts[PRA_OpenAI_Automation::LOCK]=(time()-PRA_OpenAI_Automation::LOCK_TTL-1).':'.str_repeat('f',32);
check(PRA_OpenAI_Automation::acquire_lock(),'recovery reacquires stale lock');
$opts[PRA_OpenAI_Automation::LOCK]=(time()+1).':'.str_repeat('a',32); // emulate a newer owner
PRA_OpenAI_Automation::release_lock();
check(isset($opts[PRA_OpenAI_Automation::LOCK]),'release does not delete another owner lock');

check(is_string($opts[PRA_OpenAI_Automation::LOCK]),'ownership marker cannot collide within same second');

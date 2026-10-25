<?php
define('ABSPATH',__DIR__.'/');
require_once __DIR__.'/../wp-content/plugins/portal-regional-agentes/includes/redacao/class-pra-source-evidence.php';
$t=str_repeat('Fonte documental verificada. ',12);
$r=['url'=>'https://example.org/documento','text'=>$t,'sha256'=>hash('sha256',$t),'verification'=>'human_reviewed_source_text','verified_at'=>time()];
function ck($ok,$label){if(!$ok){fwrite(STDERR,"FAIL $label\n");exit(1);} echo "PASS $label\n";}
ck(PRA_Source_Evidence::validate($r),'evidencia integra');
foreach(['sha256'=>'000','verification'=>'ai_generated','verified_at'=>time()+3600,'url'=>'http://example.org/documento','text'=>'curto'] as $k=>$bad){$x=$r;$x[$k]=$bad;ck(!PRA_Source_Evidence::validate($x),'rejeita '.$k);}

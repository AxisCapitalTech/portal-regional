<?php
/** E8-P1: regressão da normalização com LC_CTYPE=C (PHP 8.0 / setlocale externo). Sem rede. */
define('ABSPATH', __DIR__.'/');
require_once __DIR__.'/../wp-content/plugins/portal-regional-agentes/includes/redacao/class-pra-redacao-logic.php';
require_once __DIR__.'/../wp-content/themes/portal-regional-tema/includes/pr1-aero-logic.php';
$fail=0; function ok($c,$l){global $fail; if(!$c){fwrite(STDERR,"FAIL $l\n");$fail++;} else echo "OK $l\n";}
setlocale(LC_ALL,'C'); ok(setlocale(LC_CTYPE,0)==='C','locale forçado em C');
$L='PRA_Redacao_Logic';
ok($L::norm('Denúncia de CORRUPÇÃO em Maringá')==='denuncia de corrupcao em maringa','norm() sem depender do locale');
$r=$L::classify(['title'=>'Denúncia de corrupção em Maringá','lead'=>'','body'=>'']);
$reasons=implode(' ',(array)($r['reasons']??[]));
ok(str_contains($reasons,'denuncia'),'denúncia detectada como sensível em locale C');
ok(str_contains($reasons,'corrupcao'),'corrupção detectada como sensível em locale C');
ok($r['class']==='revisao_humana','tema sensível vai para revisão humana em locale C');
ok($L::assign(['area'=>'Maringá'])==='metropolitano','Maringá atribuída por cobertura em locale C');
$n=new ReflectionMethod('PR1_Aero_Logic','norm'); $n->setAccessible(true);
ok($n->invoke(null,'Maringá')==='maringa','AERO norm(Maringá) em locale C');
ok($n->invoke(null,'São João do Ivaí')==='sao joao do ivai','AERO norm com til/acento em locale C');
echo $fail?"$fail falha(s)\n":"OK normalização independente de locale\n"; exit($fail?1:0);

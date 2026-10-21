<?php
/** Teste separado porque a constante deve existir antes do carregamento do tema. */
define('ABSPATH', __DIR__.'/');
define('PR1_AERO_HOST', 'Clima.Example.org');
function add_action(...$args) {}
function add_filter(...$args) {}
function esc_url_raw($url) {return $url;}
require __DIR__.'/../wp-content/themes/portal-regional-tema/includes/pr1-aero-bridge.php';
$actual=PR1_Aero_Bridge::app_url();
if($actual!=='https://clima.example.org/'){fwrite(STDERR,"FAIL endereço HTTPS configurado: $actual\n");exit(1);}
echo "OK domínio AERO configurado gera apenas a origem HTTPS permitida\n";

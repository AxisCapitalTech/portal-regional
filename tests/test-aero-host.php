<?php
/** Etapa 1: validar a origem externa AERO sem depender do WordPress. */
define('ABSPATH', __DIR__.'/');
function add_action(...$args) {}
function add_filter(...$args) {}
function esc_url_raw($url) {return $url;}
require __DIR__.'/../wp-content/themes/portal-regional-tema/includes/pr1-aero-bridge.php';
function verify($cond, $what) {
  if (!$cond) {fwrite(STDERR, "FAIL $what\n");exit(1);}
  echo "OK $what\n";
}
verify(PR1_Aero_Bridge::app_url()==='', 'sem PR1_AERO_HOST o painel externo fica desativado');
foreach(['clima.example.org','AERO.Example.COM','clima.exemplo.com.br'] as $value) {
  verify(PR1_Aero_Bridge::validated_host($value)===strtolower($value), 'host DNS aceito: '.$value);
}
foreach(['','localhost','127.0.0.1','192.168.0.1','https://clima.example.org',
  'clima.example.org:443','clima.example.org/caminho','clima.example.org?token=1',
  'clima.example.org#frag','foo..example.org','-foo.example.org','foo-.example.org',
  'example.c','example.org@evil.test','clima.example.org\\evil.test'] as $value) {
  verify(PR1_Aero_Bridge::validated_host($value)==='', 'host DNS inválido rejeitado: '.($value?:'(vazio)'));
}
echo "OK teste de configuração AERO concluído\n";

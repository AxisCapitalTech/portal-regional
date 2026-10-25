<?php
/** Teste isolado do fail-closed e SQL atômico; não usa MySQL nem rede. */
define('ABSPATH', __DIR__ . '/');
class FakeWPDB {
  public string $prefix='wp_';
  public int $affected=1;
  public string $sql='';
  public function prepare($sql,...$args) {
    foreach ($args as $v) $sql=preg_replace('/%[sd]/', is_string($v)?"'".addslashes($v)."'":(string)$v,$sql,1);
    return $sql;
  }
  public function query($sql) {$this->sql=$sql;return $this->affected;}
}
$wpdb=new FakeWPDB();
require_once __DIR__.'/../wp-content/plugins/portal-regional-agentes/includes/redacao/class-pra-openai-budget.php';
function check_e17($ok,$msg) {if (!$ok) {fwrite(STDERR,"FAIL: $msg\n");exit(1);}echo "PASS: $msg\n";}
check_e17(!PRA_OpenAI_Budget::reserve(),'sem orçamento configurado: bloqueia');
define('PR1_OPENAI_DAILY_BUDGET_MICRO_USD', 1000000);
define('PR1_OPENAI_MAX_COST_PER_REQUEST_MICRO_USD', 250000);
check_e17(PRA_OpenAI_Budget::reserve(),'reserva simulada com parâmetros válidos');
check_e17(str_contains($wpdb->sql,'ON DUPLICATE KEY UPDATE') && str_contains($wpdb->sql,'reserved_micro_usd <= 1000000 - 250000'),'SQL condicional atômico');
$wpdb->affected=0;
check_e17(!PRA_OpenAI_Budget::reserve(),'limite atingido: bloqueia');
$wpdb->affected=false;
check_e17(!PRA_OpenAI_Budget::reserve(),'falha no banco: bloqueia');

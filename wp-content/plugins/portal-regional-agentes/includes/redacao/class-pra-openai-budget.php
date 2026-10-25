<?php
/** Reserva conservadora de orçamento diário. Não habilita chamadas por si só. */
if (!defined('ABSPATH')) exit;
final class PRA_OpenAI_Budget {
  public static function table(): string { global $wpdb; return $wpdb->prefix . 'pra_openai_budget'; }
  public static function install(): void {
    global $wpdb;
    require_once ABSPATH . 'wp-admin/includes/upgrade.php';
    $table=self::table(); $charset=$wpdb->get_charset_collate();
    dbDelta("CREATE TABLE $table (
      budget_day date NOT NULL,
      reserved_micro_usd bigint unsigned NOT NULL DEFAULT 0,
      PRIMARY KEY  (budget_day)
    ) $charset;");
  }
  /**
   * Reserva o PIOR CUSTO por tentativa. Não estorna em erro/timeout para evitar
   * cobrança não observada. Sem tabela/configuração válida, falha fechada.
   * 1 USD = 1.000.000 micro-USD; inteiros evitam erro de arredondamento.
   */
  public static function reserve(): bool {
    global $wpdb;
    if (!defined('PR1_OPENAI_DAILY_BUDGET_MICRO_USD') || !defined('PR1_OPENAI_MAX_COST_PER_REQUEST_MICRO_USD')) return false;
    $limit=(int) PR1_OPENAI_DAILY_BUDGET_MICRO_USD;
    $cost=(int) PR1_OPENAI_MAX_COST_PER_REQUEST_MICRO_USD;
    if ($limit < 1 || $limit > 100000000000 || $cost < 1 || $cost > $limit) return false;
    $table=self::table(); $day=gmdate('Y-m-d');
    // Expressão única atômica: não reservar se exceder o limite.
    $sql=$wpdb->prepare("INSERT INTO $table (budget_day,reserved_micro_usd) VALUES (%s,%d)
      ON DUPLICATE KEY UPDATE reserved_micro_usd = IF(reserved_micro_usd <= %d - %d, reserved_micro_usd + %d, reserved_micro_usd)",
      $day,$cost,$limit,$cost,$cost);
    $affected=$wpdb->query($sql);
    return $affected === 1 || $affected === 2;
  }
}

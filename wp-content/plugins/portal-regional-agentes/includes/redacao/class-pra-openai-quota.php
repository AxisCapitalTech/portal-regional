<?php
/** PR1 News: quota persistente de tentativas por dia; instalar tabela antes de usar. */
if (!defined('ABSPATH')) exit;
final class PRA_OpenAI_Quota {
  public static function table(): string { global $wpdb; return $wpdb->prefix . 'pra_openai_quota'; }
  public static function install(): void {
    global $wpdb;
    require_once ABSPATH . 'wp-admin/includes/upgrade.php';
    $table=self::table(); $charset=$wpdb->get_charset_collate();
    dbDelta("CREATE TABLE $table (
      quota_day date NOT NULL,
      requests int unsigned NOT NULL DEFAULT 0,
      PRIMARY KEY  (quota_day)
    ) $charset;");
  }
  /** Fail-closed, atomic request reservation. Counts attempts, including API failures. */
  public static function reserve(): bool {
    global $wpdb;
    if (!defined('PR1_OPENAI_REQUESTS_PER_DAY')) return false;
    $limit=(int) PR1_OPENAI_REQUESTS_PER_DAY;
    if ($limit < 1 || $limit > 100000) return false;
    $table=self::table();
    // MySQL single statement: unique day row + conditional update. No race between workers.
    $day=gmdate('Y-m-d');
    $sql=$wpdb->prepare("INSERT INTO $table (quota_day, requests) VALUES (%s, 1)
      ON DUPLICATE KEY UPDATE requests = IF(requests < %d, requests + 1, requests)", $day, $limit);
    $affected=$wpdb->query($sql);
    // MySQL affected rows: insert=1, changed update=2, unchanged=0.
    return $affected === 1 || $affected === 2;
  }
}

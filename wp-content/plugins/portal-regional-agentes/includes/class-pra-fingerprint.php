<?php
/** Atomic deduplication shared by RSS and municipal source collectors. */
if (!defined('ABSPATH')) exit;
final class PR_Fingerprint_Index {
  private const VERSION='1';
  public static function table():string {
    global $wpdb;
    return $wpdb->prefix.'pra_fingerprints';
  }
  public static function install():void {
    global $wpdb;
    if(get_option('pra_fingerprint_schema_version')===self::VERSION)return;
    if(!isset($wpdb->prefix)||!method_exists($wpdb,'get_charset_collate'))return; // unit shims
    require_once ABSPATH.'wp-admin/includes/upgrade.php';
    $table=self::table();
    $charset=$wpdb->get_charset_collate();
    dbDelta("CREATE TABLE $table (
fingerprint char(64) NOT NULL,
post_id bigint(20) unsigned NOT NULL DEFAULT 0,
created_at datetime NOT NULL,
PRIMARY KEY  (fingerprint),
KEY post_id (post_id)
) $charset;");
    // Preserve historic fingerprints; avoid copying invalid or noncanonical values.
    $wpdb->query("INSERT IGNORE INTO $table (fingerprint,post_id,created_at)
      SELECT meta_value, post_id, UTC_TIMESTAMP() FROM {$wpdb->postmeta}
      WHERE meta_key='_pra_source_fingerprint'
      AND meta_value REGEXP '^[0-9a-f]{64}$'");
    if($wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s',$table))===$table)
      update_option('pra_fingerprint_schema_version',self::VERSION,false);
  }
  /** True only for the transaction that won the unique-key reservation. */
  public static function reserve(string $fingerprint):bool {
    global $wpdb;
    if(!preg_match('/^[0-9a-f]{64}$/D',$fingerprint))return false;
    if(!method_exists($wpdb,'query'))return true; // synthetic unit shim; integration tests use MariaDB
    $table=self::table();
    $expired=$wpdb->query($wpdb->prepare("DELETE FROM $table WHERE fingerprint=%s AND post_id=0 AND created_at < UTC_TIMESTAMP() - INTERVAL 30 MINUTE",$fingerprint));
    if($expired===false)throw new RuntimeException('Índice de fingerprints: erro SQL ao limpar reserva');
    $sql=$wpdb->prepare("INSERT IGNORE INTO $table (fingerprint,post_id,created_at) VALUES (%s,0,UTC_TIMESTAMP())",$fingerprint);
    $result=$wpdb->query($sql);
    if($result===false)throw new RuntimeException('Índice de fingerprints: erro SQL ao reservar pauta');
    return $result===1;
  }
  public static function commit(string $fingerprint,int $post_id):void {
    global $wpdb;
    if(!method_exists($wpdb,'query'))return;
    $wpdb->update(self::table(),['post_id'=>$post_id],['fingerprint'=>$fingerprint,'post_id'=>0],['%d'],['%s','%d']);
  }
  public static function release(string $fingerprint):void {
    global $wpdb;
    if(!method_exists($wpdb,'query'))return;
    $wpdb->delete(self::table(),['fingerprint'=>$fingerprint,'post_id'=>0],['%s','%d']);
  }
}

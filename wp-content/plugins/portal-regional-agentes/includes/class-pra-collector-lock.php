<?php
/** Shared lease for both collectors. CAS always reads the database, not object cache. */
if(!defined('ABSPATH'))exit;
final class PR_Collector_Lock {
  private const KEY='pra_official_lock';
  private const TTL=1500;
  private string $value;
  private string $token;
  private function __construct(string $token,string $value){$this->token=$token;$this->value=$value;}
  public static function acquire():?self {
    global $wpdb;
    $token=bin2hex(random_bytes(16));$value=$token.'|'.(time()+self::TTL);
    $inserted=$wpdb->query($wpdb->prepare("INSERT IGNORE INTO {$wpdb->options} (option_name,option_value,autoload) VALUES (%s,%s,'no')",self::KEY,$value));
    if($inserted===false)throw new RuntimeException('Erro SQL ao criar lock de coleta');
    if($inserted===1){wp_cache_delete(self::KEY,'options');return new self($token,$value);}
    $old=$wpdb->get_var($wpdb->prepare("SELECT option_value FROM {$wpdb->options} WHERE option_name=%s",self::KEY));
    if($old===null)return null;
    $parts=explode('|',(string)$old);$expires=(int)end($parts);
    if($expires>=time())return null;
    $changed=$wpdb->query($wpdb->prepare("UPDATE {$wpdb->options} SET option_value=%s WHERE option_name=%s AND option_value=%s",$value,self::KEY,(string)$old));
    if($changed===false)throw new RuntimeException('Erro SQL ao adquirir lock de coleta');
    if($changed!==1)return null;
    wp_cache_delete(self::KEY,'options');
    return new self($token,$value);
  }
  public function renew():void {
    global $wpdb;
    // Extend at least one second so affected_rows is meaningful even in the same second.
    $parts=explode('|',$this->value);$expires=max(time()+self::TTL,(int)end($parts)+1);
    $value=$this->token.'|'.$expires;
    $changed=$wpdb->query($wpdb->prepare("UPDATE {$wpdb->options} SET option_value=%s WHERE option_name=%s AND option_value=%s",$value,self::KEY,$this->value));
    if($changed!==1)throw new RuntimeException('Lock de coleta perdido; execução suspensa');
    $this->value=$value;wp_cache_delete(self::KEY,'options');
  }
  public function release():void {
    global $wpdb;
    $wpdb->query($wpdb->prepare("DELETE FROM {$wpdb->options} WHERE option_name=%s AND option_value=%s",self::KEY,$this->value));
    wp_cache_delete(self::KEY,'options');
  }
}

<?php
/** E20: gate de evidência, sem ativação de coleta ou IA. */
if (!defined('ABSPATH')) exit;
final class PRA_Source_Evidence {
  public static function validate(array $r): bool {
    $url=(string)($r['url']??''); $body=(string)($r['text']??'');
    if (!filter_var($url,FILTER_VALIDATE_URL) || strtolower((string)parse_url($url,PHP_URL_SCHEME))!=='https') return false;
    if (strlen(trim($body))<200 || strlen($body)>1000000) return false;
    $sha=(string)($r['sha256']??'');
    if (!preg_match('/^[a-f0-9]{64}$/D',$sha) || !hash_equals(hash('sha256',$body),$sha)) return false;
    if (($r['verification']??'')!=='human_reviewed_source_text') return false;
    $at=(int)($r['verified_at']??0);
    return $at>0 && $at<=time()+300;
  }
  public static function from_post(int $id): ?array {
    // E22: no multi-key reads. Old E21 attestations fail closed until re-reviewed.
    $r=get_post_meta($id,'_pra_source_evidence_e22',true);
    if (!is_array($r) || !self::validate($r)) return null;
    $actor_id=(int)($r['actor_id']??0);
    if ($actor_id <= 0 || (int)get_post_meta($id,'_pra_source_verified_by',true) !== $actor_id) return null;
    return $r;
  }
}

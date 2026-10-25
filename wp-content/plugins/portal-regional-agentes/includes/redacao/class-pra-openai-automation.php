<?php
/**
 * PR1 News: worker agendado de redação, DESLIGADO por padrão.
 * O coletor RSS existente continua responsável pelas pautas; este worker
 * só processa pautas com texto da fonte previamente validado e registrado.
 */
if (!defined('ABSPATH')) exit;
require_once __DIR__ . '/class-pra-openai-client.php';
require_once __DIR__ . '/class-pra-source-evidence.php';
final class PRA_OpenAI_Automation {
    public const HOOK = 'pra_openai_editorial_worker';
    public const LOCK = 'pra_openai_editorial_worker_lock';
    public const LOCK_TTL = 900; // 15 minutes; conservative recovery after crash
    private static ?string $owned_lock = null;
    public static function acquire_lock(): bool {
        $now = time();
        $token = $now.':'.bin2hex(random_bytes(16));
        if (add_option(self::LOCK, $token, '', false)) { self::$owned_lock = $token; return true; }
        $old = get_option(self::LOCK, false);
        if (!is_string($old) || !preg_match('/^(\d{10}):([0-9a-f]{32})$/D', $old, $parts)) return false;
        $age = (int)$parts[1];
        if ($age > $now || $age > $now - self::LOCK_TTL) return false;
        // Recovery requires compare-and-swap: never delete a newer owner's lock.
        global $wpdb;
        if (!isset($wpdb) || !isset($wpdb->options)) return false;
        $updated = $wpdb->query($wpdb->prepare(
            "UPDATE {$wpdb->options} SET option_value = %s WHERE option_name = %s AND option_value = %s",
            $token, self::LOCK, (string)$old
        ));
        if ($updated === 1) self::$owned_lock = $token;
        return $updated === 1;
    }
    public static function init(): void {
        add_action(self::HOOK, [__CLASS__, 'run']);
        add_action('init', [__CLASS__, 'ensure_schedule']);
    }
    public static function allowed(): bool {
        $cfg = (array)get_option('pra_config', []);
        $red = (array)get_option('pra_redacao', []);
        return false && defined('PR1_OPENAI_ATOMIC_USD_BUDGET_VERIFIED') && PR1_OPENAI_ATOMIC_USD_BUDGET_VERIFIED === true
            && defined('PR1_EDITORIAL_AUTOMATION_APPROVED') && PR1_EDITORIAL_AUTOMATION_APPROVED === true
            && !empty($cfg['enabled']) && !empty($cfg['ai_enabled'])
            && empty($cfg['auto_publish_enabled'])
            && empty($red['kill_switch'])
            && (new PRA_OpenAI_OptIn_Client())->enabled();
    }
    public static function ensure_schedule(): void {
        // No cron registration while disabled. Clearing previously scheduled tasks
        // makes disabling the feature effective even after a prior activation.
        if (!self::allowed()) {
            if (function_exists('wp_clear_scheduled_hook')) wp_clear_scheduled_hook(self::HOOK);
            return;
        }
        if (!wp_next_scheduled(self::HOOK)) wp_schedule_event(time()+300, 'hourly', self::HOOK);
    }
    public static function release_lock(): void {
        if (self::$owned_lock === null) return;
        global $wpdb;
        if (isset($wpdb) && isset($wpdb->options)) {
            $wpdb->query($wpdb->prepare(
                "DELETE FROM {$wpdb->options} WHERE option_name = %s AND option_value = %s",
                self::LOCK, (string) self::$owned_lock
            ));
        }
        self::$owned_lock = null;
    }
    public static function run(): void {
        if (!self::allowed()) return;
        // Atomic transient add_option lock: fails closed if another worker is active.
        $lock_key = self::LOCK;
        if (!self::acquire_lock()) return;
        try {
            $ids = get_posts([
                'post_type'=>'post', 'post_status'=>'pending', 'posts_per_page'=>5,
                'fields'=>'ids', 'orderby'=>'date', 'order'=>'ASC',
                'meta_query'=>[['key'=>'_pra_source_url','compare'=>'EXISTS']],
            ]);
            foreach ($ids as $id) {
                if (!self::allowed()) break;
                $id=(int)$id;
                if (get_post_meta($id,'_pra_openai_worker_processed',true)) continue;
                $evidence=PRA_Source_Evidence::from_post($id);
                // Sem verificação humana explícita e hash consistente, não processar.
                if ($evidence === null) continue;
                $url=$evidence['url']; $verified=$evidence['text'];
                $title=get_the_title($id);
                $result=PRA_Redacao::process(
                    ['title'=>$title,'editoria'=>'Cidades','source_url'=>$url],
                    [['url'=>$url,'org'=>(string)get_post_meta($id,'_pra_source_name',true),'text'=>$verified]],
                    new PRA_OpenAI_OptIn_Client()
                );
                $status=(string)($result['status']??'falha');
                // Não marcar falhas transitórias como concluídas.
                if (in_array($status,['pendente','escalada','duplicada','rejeitada'],true)) {
                    update_post_meta($id,'_pra_openai_worker_processed',$status);
                }
                PRA_Redacao::log('worker_result',['source_post_id'=>$id,'status'=>$status]);
            }
        } finally {
            self::release_lock();
        }
    }
}
PRA_OpenAI_Automation::init();

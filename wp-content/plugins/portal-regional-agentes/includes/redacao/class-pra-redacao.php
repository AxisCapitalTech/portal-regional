<?php
/**
 * Redação Grok — orquestração no WordPress.
 * Sempre cria post_status=pending com _pra_source_url (ativa prevent_unreviewed_publish) e marcador
 * "NÃO PUBLICAR SEM REVISÃO". Kill switch LIGADO por padrão. Sem cron, sem rede nesta versão.
 */
if (!defined('ABSPATH')) exit;
require_once __DIR__ . '/class-pra-redacao-logic.php';
require_once __DIR__ . '/class-pra-llm-client.php';
final class PRA_Redacao {
  public const OPT = 'pra_redacao';
  public const LOG = 'pra_redacao_log';
  public const LEDGER = 'pra_redacao_ledger';
  public const CAP = 'edit_others_posts';
  public const MARK = 'NÃO PUBLICAR SEM REVISÃO';
  /** Preço por 1M tokens — A CONFIRMAR na página oficial da xAI antes de qualquer uso. */
  public const PRICE_IN = 0.0; public const PRICE_OUT = 0.0;

  public static function init(): void {
    add_action('admin_menu', [__CLASS__, 'menu']);
    add_action('post_updated', function ($id, $after, $before) {
      if ($after->post_type === 'post' && get_post_meta($id, '_pra_red_state', true) && !wp_is_post_revision($id)
          && ($after->post_content !== $before->post_content || $after->post_title !== $before->post_title)) self::history((int) $id, 'editado');
    }, 10, 3);
    foreach (['reject', 'approve', 'suspend', 'resume', 'kill'] as $a) add_action('admin_post_pra_red_' . $a, [__CLASS__, 'action_' . $a]);
  }
  public static function settings(): array {
    return wp_parse_args((array) get_option(self::OPT, []), ['kill_switch' => 1, 'suspended' => [], 'limits' => PRA_Redacao_Logic::DEFAULT_LIMITS]);
  }
  public static function log(string $event, array $ctx = []): void {
    $l = (array) get_option(self::LOG, []);
    $l[] = ['t' => time(), 'event' => sanitize_key($event), 'user' => get_current_user_id(), 'ctx' => $ctx];
    update_option(self::LOG, array_slice($l, -300), false);
  }
  public static function ledger(string $agent): array {
    $all = (array) get_option(self::LEDGER, []); $d = wp_date('Y-m-d');
    return (array) ($all[$d][$agent] ?? ['calls' => 0, 'tokens' => 0, 'usd' => 0.0]);
  }
  private static function charge(string $agent, int $tin, int $tout): void {
    $all = (array) get_option(self::LEDGER, []); $d = wp_date('Y-m-d');
    $c = self::ledger($agent);
    $c['calls']++; $c['tokens'] += $tin + $tout; $c['usd'] += ($tin * self::PRICE_IN + $tout * self::PRICE_OUT) / 1e6;
    $all = [$d => array_merge((array) ($all[$d] ?? []), [$agent => $c])];
    update_option(self::LEDGER, $all, false);
  }

  /**
   * Pipeline completo para UMA pauta. Retorna resultado auditável; só cria rascunho pending.
   * $source_texts são os textos efetivamente consultados (aqui, fornecidos pelo chamador).
   */
  public static function process(array $pauta, array $source_texts, PRA_LLM_Client $client): array {
    $cfg = (array) get_option('pra_config', []); $st = self::settings();
    $agent = PRA_Redacao_Logic::assign($pauta, (array) $st['suspended']);
    if (!$agent) { self::log('sem_agente', ['pauta' => $pauta['title'] ?? '']); return ['status' => 'humano', 'reason' => 'sem_agente']; }
    [$ok, $why] = PRA_Redacao_Logic::may_run($cfg, self::kill_switch_effective(), $client->enabled(), $agent, (array) $st['suspended']);
    if (!$ok) { self::log('bloqueado', ['agent' => $agent, 'reason' => $why]); return ['status' => 'bloqueado', 'reason' => $why, 'agent' => $agent]; }
    $fp = PRA_Redacao_Logic::fingerprint($pauta);
    // Check both the atomic index and legacy postmeta BEFORE spending tokens.
    require_once dirname(__DIR__).'/class-pra-fingerprint.php';
    PR_Fingerprint_Index::install();
    global $wpdb;
    $indexed = $wpdb->get_var($wpdb->prepare('SELECT fingerprint FROM '.PR_Fingerprint_Index::table().' WHERE fingerprint=%s LIMIT 1', $fp));
    $legacy = $wpdb->get_var($wpdb->prepare("SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key=%s AND meta_value=%s LIMIT 1", '_pra_red_fingerprint', $fp));
    if ($indexed || $legacy) { self::log('duplicada', ['agent'=>$agent]); return ['status'=>'duplicada','agent'=>$agent]; }

    $est = 6000;
    if (!PRA_Redacao_Logic::within_limits(self::ledger($agent), $est, 0.0, (array) $st['limits']))
      { self::log('limite_custo', ['agent' => $agent]); return ['status' => 'bloqueado', 'reason' => 'limite_custo', 'agent' => $agent]; }
    $r = $client->draft($pauta, PRA_Redacao_Logic::AGENTS[$agent], $source_texts);
    self::charge($agent, (int) $r['tokens_in'], (int) $r['tokens_out']);
    if (empty($r['ok']) || !is_array($r['story'] ?? null)) { self::log('falha_modelo', ['agent' => $agent, 'error' => $r['error'] ?? '']); return ['status' => 'falha', 'agent' => $agent]; }
    $story = $r['story']; $story['status'] = 'pending';
    $story['provenance'] = array_merge((array) ($story['provenance'] ?? []), ['agent' => $agent, 'model' => $client->model(), 'generated_at' => gmdate('c'), 'pauta_fingerprint' => $fp]);
    $verified_source_texts = array_map(static function ($src): string {
      return is_array($src) ? (string) ($src['text'] ?? '') : (string) $src;
    }, array_values($source_texts));
    $chk = PRA_Redacao_Logic::check($story, $verified_source_texts);
    self::log('checagem', ['agent' => $agent, 'decision' => $chk['decision'], 'reasons' => $chk['reasons']]);
    if ($chk['decision'] === 'rejeitada') return ['status' => 'rejeitada', 'agent' => $agent, 'check' => $chk];
    $story['editoria'] = (string)($pauta['editoria'] ?? $pauta['area'] ?? '');
    $id = self::create_pending($story, $chk, $fp);
    if ($id === -1) { self::log('duplicada',['agent'=>$agent]); return ['status'=>'duplicada','agent'=>$agent]; }
    if (!$id) return ['status'=>'falha','agent'=>$agent];
    return ['status' => $chk['decision'] === 'escalada_humano' ? 'escalada' : 'pendente', 'post_id' => $id, 'agent' => $agent, 'check' => $chk];
  }

  public static function render_content(array $s): string {
    $h = '<p><strong>' . esc_html(self::MARK) . '</strong> — rascunho de agente; exige revisão humana.</p>';
    $h .= '<p class="subtitulo"><em>' . esc_html($s['subtitle']) . '</em></p><p><strong>' . esc_html($s['lead']) . '</strong></p>';
    foreach (preg_split('/\n{2,}/', trim((string) $s['body'])) as $p) $h .= '<p>' . esc_html($p) . '</p>';
    $h .= '<h3>Fontes</h3><ul>';
    foreach ($s['sources'] as $f) $h .= '<li><a href="' . esc_url($f['url']) . '">' . esc_html($f['org']) . '</a> — consultado em ' . esc_html($f['accessed_at']) . '</li>';
    return $h . '</ul>';
  }
  private static function persona_author(?string $persona): int {
    if(!$persona || !isset(PRA_Redacao_Logic::PERSONAS[$persona]))return 0;
    $login='pr1_persona_'.$persona;
    $existing=get_user_by('login',$login);
    if($existing){
      $user=new WP_User((int)$existing->ID);
      if($user->has_cap('publish_posts'))$user->set_role('contributor');
      return (int)$existing->ID;
    }
    $id=wp_insert_user(['user_login'=>$login,'user_pass'=>wp_generate_password(64,true,true),
      'user_email'=>$login.'@example.invalid','display_name'=>PRA_Redacao_Logic::PERSONAS[$persona]['name'],
      'role'=>'contributor','show_admin_bar_front'=>'false']);
    if(is_wp_error($id)){
      if($id->get_error_code()==='existing_user_login'){
        $raced=get_user_by('login',$login);
        if($raced){$user=new WP_User((int)$raced->ID);$user->set_role('contributor');return (int)$raced->ID;}
      }
      return 0;
    }
    return (int)$id;
  }
  public static function create_pending(array $s, array $chk, string $fp): int {
    require_once dirname(__DIR__).'/class-pra-fingerprint.php';
    PR_Fingerprint_Index::install();
    if(!PR_Fingerprint_Index::reserve($fp))return -1;
    $persona=PRA_Redacao_Logic::assign_persona($s);
    $classification=PRA_Redacao_Logic::classify($s);
    $author=self::persona_author($persona);
    $id = wp_insert_post(['post_type' => 'post', 'post_status' => 'pending', 'post_title' => wp_strip_all_tags($s['title']),
      'post_excerpt' => wp_strip_all_tags($s['seo']['meta_description']), 'post_content' => self::render_content($s), 'post_author'=>$author], true);
    if (is_wp_error($id) || !$id) {PR_Fingerprint_Index::release($fp);return 0;}
    PR_Fingerprint_Index::commit($fp,(int)$id);
    update_post_meta($id, '_pra_source_url', esc_url_raw($s['sources'][0]['url']));
    update_post_meta($id, '_pra_red_fingerprint', $fp);
    update_post_meta($id, '_pra_red_persona', $persona ?: 'editor_humano');
    update_post_meta($id, '_pra_red_class', $classification['class']);
    update_post_meta($id, '_pra_red_class_reason', $classification['reasons']);
    update_post_meta($id, '_pra_red_story', wp_slash(wp_json_encode($s, JSON_UNESCAPED_UNICODE)));
    update_post_meta($id, '_pra_red_check', wp_slash(wp_json_encode($chk, JSON_UNESCAPED_UNICODE)));
    update_post_meta($id, '_pra_red_state', $chk['decision'] === 'escalada_humano' ? 'escalada' : 'pendente');
    self::history($id, 'criado', $s['provenance']['agent']);
    return (int) $id;
  }
  public static function history(int $id, string $what, string $who = ''): void {
    $h = get_post_meta($id, '_pra_red_history', true); $h = is_array($h) ? $h : [];
    $h[] = ['t' => time(), 'what' => $what, 'who' => $who !== '' ? $who : (string) get_current_user_id()];
    update_post_meta($id, '_pra_red_history', $h);
  }

  /* ---------------- Painel ---------------- */
  public static function menu(): void {
    add_management_page('Redação Grok', 'Redação Grok', self::CAP, 'pra-redacao', [__CLASS__, 'page']);
  }
  private static function btn(string $action, string $label, array $fields, string $cls = 'button'): string {
    $f = '<form method="post" action="' . esc_url(admin_url('admin-post.php')) . '" style="display:inline">';
    $f .= '<input type="hidden" name="action" value="pra_red_' . esc_attr($action) . '">' . wp_nonce_field('pra_red_' . $action, '_wpnonce', true, false);
    foreach ($fields as $k => $v) $f .= '<input type="hidden" name="' . esc_attr($k) . '" value="' . esc_attr($v) . '">';
    return $f . '<button class="' . esc_attr($cls) . '">' . esc_html($label) . '</button></form> ';
  }
  public static function page(): void {
    if (!current_user_can(self::CAP)) wp_die('Acesso negado.');
    $st = self::settings(); $cfg = (array) get_option('pra_config', []);
    echo '<div class="wrap"><h1>Redação Grok — agentes editoriais</h1>';
    echo '<p><strong>Estado:</strong> kill switch ' . ($st['kill_switch'] ? '<b style="color:#b32d2e">LIGADO (nada roda)</b>' : 'desligado') .
      ' · ai_enabled=' . (int) ($cfg['ai_enabled'] ?? 0) . ' · coleta enabled=' . (int) ($cfg['enabled'] ?? 0) . ' · cliente: ' . esc_html((new PRA_Grok_Disabled_Client())->model()) . '</p>';
    echo '<p>Todo texto vira rascunho <code>pending</code>. Publicar continua exigindo a confirmação editorial no editor (hash SHA-256).</p>';
    if (current_user_can('manage_options')) echo self::btn('kill', $st['kill_switch'] ? 'Kill switch: manter ligado' : 'Ligar kill switch (parar tudo)', ['on' => '1'], 'button button-primary');
    echo '<h2>Agentes</h2><table class="widefat striped"><thead><tr><th>Agente</th><th>Função</th><th>Cobertura</th><th>Hoje (chamadas/tokens/US$)</th><th>Estado</th><th>Ação</th></tr></thead><tbody>';
    foreach (PRA_Redacao_Logic::AGENTS as $id => $a) {
      $l = self::ledger($id); $s = in_array($id, (array) $st['suspended'], true);
      echo '<tr><td>' . esc_html($a['name']) . '</td><td>' . esc_html($a['role']) . '</td><td>' . esc_html(implode(', ', $a['coverage'])) . '</td><td>' .
        esc_html($l['calls'] . ' / ' . $l['tokens'] . ' / ' . number_format((float) $l['usd'], 4)) . '</td><td>' . ($s ? 'suspenso' : 'ativo') . '</td><td>' .
        (current_user_can('manage_options') ? self::btn($s ? 'resume' : 'suspend', $s ? 'Reativar' : 'Suspender', ['agent' => $id]) : '') . '</td></tr>';
    }
    echo '</tbody></table><h2>Fila</h2><table class="widefat striped"><thead><tr><th>Matéria</th><th>Agente</th><th>Estado</th><th>Checagem</th><th>Histórico</th><th>Ações</th></tr></thead><tbody>';
    $q = get_posts(['post_type' => 'post', 'post_status' => ['pending', 'draft'], 'meta_key' => '_pra_red_state', 'numberposts' => 50]);
    if (!$q) echo '<tr><td colspan="6">Fila vazia.</td></tr>';
    foreach ($q as $p) {
      $story = json_decode((string) get_post_meta($p->ID, '_pra_red_story', true), true) ?: [];
      $chk = json_decode((string) get_post_meta($p->ID, '_pra_red_check', true), true) ?: [];
      $h = get_post_meta($p->ID, '_pra_red_history', true); $h = array_filter(is_array($h) ? $h : [], 'is_array');
      echo '<tr><td><a href="' . esc_url(get_edit_post_link($p->ID)) . '">' . esc_html($p->post_title) . '</a><br><small>' . esc_html($p->post_status) . '</small></td><td>' .
        esc_html($story['provenance']['agent'] ?? '') . '</td><td>' . esc_html((string) get_post_meta($p->ID, '_pra_red_state', true)) . '</td><td>' .
        esc_html(($chk['decision'] ?? '') . ' · ' . (string)get_post_meta($p->ID,'_pra_red_persona',true) . ' · ' . (string)get_post_meta($p->ID,'_pra_red_class',true) . ' · ' . implode(', ',(array)get_post_meta($p->ID,'_pra_red_class_reason',true)) . (!empty($chk['sensitive']) ? ' · sensível: ' . implode(', ', $chk['sensitive']) : '')) . '</td><td><small>' .
        esc_html(implode(' → ', array_map(fn($x) => wp_date('d/m H:i', $x['t']) . ' ' . $x['what'], $h))) . '</small></td><td>' .
        '<a class="button" href="' . esc_url(get_edit_post_link($p->ID)) . '">Editar</a> ' .
        self::btn('approve', 'Marcar revisado', ['post' => $p->ID]) . self::btn('reject', 'Rejeitar', ['post' => $p->ID]) . '</td></tr>';
    }
    echo '</tbody></table><h2>Registro (últimos 20)</h2><ol>';
    foreach (array_slice(array_reverse((array) get_option(self::LOG, [])), 0, 20) as $e)
      echo '<li>' . esc_html(wp_date('d/m H:i:s', $e['t']) . ' · ' . $e['event'] . ' · ' . wp_json_encode($e['ctx'], JSON_UNESCAPED_UNICODE)) . '</li>';
    echo '</ol></div>';
  }
  private static function guard(string $action, string $cap): void {
    if (!current_user_can($cap)) wp_die('Acesso negado.', 403);
    check_admin_referer('pra_red_' . $action);
  }
  private static function back(string $msg): void { wp_safe_redirect(add_query_arg('pra_red', $msg, admin_url('tools.php?page=pra-redacao'))); exit; }
  private static function post_id(): int {
    $id = absint($_POST['post'] ?? 0);
    if (!$id || !get_post_meta($id, '_pra_red_state', true) || !current_user_can('edit_post', $id)) wp_die('Matéria inválida.', 403);
    return $id;
  }
  /** Rejeitar: volta a rascunho, nunca apaga. */
  public static function action_reject(): void {
    self::guard('reject', self::CAP); $id = self::post_id();
    wp_update_post(['ID' => $id, 'post_status' => 'draft']);
    update_post_meta($id, '_pra_red_state', 'rejeitada'); self::history($id, 'rejeitada'); self::log('rejeitada', ['post' => $id]);
    self::back('rejeitada');
  }
  /** "Marcar revisado" NÃO publica nem grava hash: a aprovação continua no editor (record_editorial_approval). */
  public static function action_approve(): void {
    self::guard('approve', self::CAP); $id = self::post_id();
    update_post_meta($id, '_pra_red_state', 'revisado_aguardando_publicacao'); self::history($id, 'revisado'); self::log('revisado', ['post' => $id]);
    self::back('revisado');
  }
  private static function set_suspended(string $agent, bool $on): void {
    if (!isset(PRA_Redacao_Logic::AGENTS[$agent])) wp_die('Agente inválido.', 400);
    $st = self::settings(); $s = array_values(array_diff((array) $st['suspended'], [$agent]));
    if ($on) $s[] = $agent;
    $st['suspended'] = $s; update_option(self::OPT, $st, false); self::log($on ? 'suspenso' : 'reativado', ['agent' => $agent]);
  }
  public static function action_suspend(): void { self::guard('suspend', 'manage_options'); self::set_suspended(sanitize_key($_POST['agent'] ?? ''), true); self::back('suspenso'); }
  public static function action_resume(): void { self::guard('resume', 'manage_options'); self::set_suspended(sanitize_key($_POST['agent'] ?? ''), false); self::back('reativado'); }
  /** O painel só LIGA o kill switch. Desligar exige constante em wp-config (decisão humana fora do painel). */
  public static function action_kill(): void {
    self::guard('kill', 'manage_options'); $st = self::settings(); $st['kill_switch'] = 1; update_option(self::OPT, $st, false); self::log('kill_switch_ligado'); self::back('kill');
  }
  public static function kill_switch_effective(): bool {
    $st = self::settings();
    return (bool) $st['kill_switch'] || !(defined('PRA_REDACAO_KILL_SWITCH_OFF') && PRA_REDACAO_KILL_SWITCH_OFF === true);
  }
}
PRA_Redacao::init();

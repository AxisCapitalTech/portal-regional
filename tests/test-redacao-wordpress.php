<?php
/**
 * Redação Grok — integração WordPress/MariaDB ISOLADA. Rodar SOMENTE com:
 *   wp eval-file tests/test-redacao-wordpress.php --path=<wordpress de teste>
 * Usa FIXTURES fictícias e um cliente de teste local (sem rede). Liga ai_enabled=1 só durante o teste,
 * neste banco isolado, e restaura ai_enabled=0 e kill switch ligado no fim. Apaga os posts de teste.
 */
if (!defined('ABSPATH') || !class_exists('PRA_Redacao')) { fwrite(STDERR, "FAIL WordPress/plugin não carregado\n"); exit(1); }
$F = require __DIR__ . '/fixtures/redacao/fixtures.php';
$GLOBALS['pra_fail'] = 0; function ok($c, $l) { if (!$c) { fwrite(STDERR, "FAIL $l\n"); $GLOBALS['pra_fail']++; } else echo "OK $l\n"; }
final class PRA_Fixture_Client implements PRA_LLM_Client { // FIXTURE: devolve matéria pronta, sem rede
  public function __construct(private array $story) {}
  public function enabled(): bool { return true; }
  public function model(): string { return 'fixture-local'; }
  public function draft(array $p, array $a, array $s): array { return ['ok' => true, 'story' => $this->story, 'tokens_in' => 1500, 'tokens_out' => 900]; }
}
class PRA_Die extends Exception {}
add_filter('wp_die_handler', fn() => function ($m) { throw new PRA_Die(is_string($m) ? $m : 'die'); });
add_filter('wp_redirect', function ($l) { throw new PRA_Die('redirect:' . $l); }, 1);
$orig_cfg = get_option('pra_config'); $orig_red = get_option(PRA_Redacao::OPT, null); $orig_log = get_option(PRA_Redacao::LOG, null);
delete_option(PRA_Redacao::LEDGER); delete_option(PRA_Redacao::OPT);
$ids_before = get_posts(['post_type' => 'post', 'post_status' => 'any', 'fields' => 'ids', 'numberposts' => -1]);
$good = new PRA_Fixture_Client($F['good']);
$admin = get_user_by('login', 'testadmin'); wp_set_current_user($admin->ID);

ok((int) $orig_cfg['ai_enabled'] === 0 && (int) $orig_cfg['enabled'] === 0, 'estado inicial: enabled=0 e ai_enabled=0');
$r = PRA_Redacao::process($F['pauta'], $F['source_texts'], $good);
ok($r['status'] === 'bloqueado' && $r['reason'] === 'kill_switch', 'padrão: kill switch ligado bloqueia tudo');
define('PRA_REDACAO_KILL_SWITCH_OFF', true);
update_option(PRA_Redacao::OPT, ['kill_switch' => 0, 'suspended' => [], 'limits' => PRA_Redacao_Logic::DEFAULT_LIMITS]);
$r = PRA_Redacao::process($F['pauta'], $F['source_texts'], $good);
ok($r['reason'] === 'ai_desativada', 'kill switch desligado mas ai_enabled=0 continua bloqueando');
update_option('pra_config', array_merge($orig_cfg, ['ai_enabled' => 1]));
$r = PRA_Redacao::process($F['pauta'], $F['source_texts'], new PRA_Grok_Disabled_Client());
ok($r['reason'] === 'cliente_desativado', 'stub Grok desativado bloqueia (nenhuma chamada)');
$r = PRA_Redacao::process($F['pauta'], $F['source_texts'], $good);
$id = (int) ($r['post_id'] ?? 0); $p = get_post($id);
ok($r['status'] === 'pendente' && $p && $p->post_status === 'pending', 'fixture válida vira rascunho PENDING');
ok(str_contains($p->post_content, PRA_Redacao::MARK) && get_post_meta($id, '_pra_source_url', true) !== '', 'marcador NÃO PUBLICAR e _pra_source_url gravados');
wp_update_post(['ID' => $id, 'post_status' => 'publish']);
ok(get_post_status($id) === 'pending', 'prevent_unreviewed_publish impede publicar o rascunho do agente');
ok(PRA_Redacao::process($F['pauta'], $F['source_texts'], $good)['status'] === 'duplicada', 'mesma pauta de novo = duplicada');
$fake = $F['good']; $fake['facts'][] = ['text' => 'prefeitura vai distribuir 50 mil cestas básicas', 'source' => 0];
$n = count(get_posts(['post_type' => 'post', 'post_status' => 'any', 'fields' => 'ids', 'numberposts' => -1]));
$r = PRA_Redacao::process(['title' => '[FIXTURE] pauta B', 'url' => 'https://www.arapongas.pr.gov.br/fixture-b', 'area' => 'Apucarana'], $F['source_texts'], new PRA_Fixture_Client($fake));
ok($r['status'] === 'rejeitada' && $n === count(get_posts(['post_type' => 'post', 'post_status' => 'any', 'fields' => 'ids', 'numberposts' => -1])), 'fake news rejeitada e nenhum post criado');
$sens = $F['good']; $sens['body'] .= "\n\nO caso envolve uma investigação de corrupção.";
$r = PRA_Redacao::process(['title' => '[FIXTURE] pauta C', 'url' => 'https://www.arapongas.pr.gov.br/fixture-c', 'area' => 'Arapongas'], $F['source_texts'], new PRA_Fixture_Client($sens));
ok($r['status'] === 'escalada' && get_post_status($r['post_id']) === 'pending' && get_post_meta($r['post_id'], '_pra_red_state', true) === 'escalada', 'tema sensível: pending + escalada ao humano');
update_option(PRA_Redacao::OPT, ['kill_switch' => 0, 'suspended' => [], 'limits' => ['max_calls_day' => 2, 'max_tokens_day' => 60000, 'max_usd_day' => 0.5]]);
$r = PRA_Redacao::process(['title' => '[FIXTURE] pauta D', 'url' => 'https://www.arapongas.pr.gov.br/fixture-d', 'area' => 'Arapongas'], $F['source_texts'], $good);
$calls = PRA_Redacao::ledger('reporter_regional')['calls'];
ok($r['reason'] === 'limite_custo' && $calls >= 2 && PRA_Redacao::ledger('reporter_regional')['tokens'] === $calls * 2400, "limite diário por agente bloqueia ($calls chamadas, tokens contabilizados)");
update_option(PRA_Redacao::OPT, ['kill_switch' => 0, 'suspended' => ['reporter_regional'], 'limits' => PRA_Redacao_Logic::DEFAULT_LIMITS]);
ok(PRA_Redacao::process(['title' => '[FIXTURE] pauta E', 'url' => 'https://x.gov.br/e', 'area' => 'Arapongas'], [], $good)['reason'] === 'sem_agente', 'agente suspenso: pauta vai para humano');
// Segurança: capability e nonce
$sub = wp_insert_user(['user_login' => 'pra_sub_' . wp_generate_password(6, false), 'user_pass' => wp_generate_password(20), 'role' => 'subscriber']);
wp_set_current_user($sub);
$_POST = ['post' => $id, '_wpnonce' => wp_create_nonce('pra_red_reject')];
try { PRA_Redacao::action_reject(); ok(false, 'assinante bloqueado'); } catch (PRA_Die $e) { ok(str_contains($e->getMessage(), 'Acesso negado') && get_post_status($id) === 'pending', 'assinante não consegue rejeitar (capability)'); }
wp_set_current_user($admin->ID);
$_POST = ['post' => $id, '_wpnonce' => 'invalido']; $_REQUEST = $_POST;
try { PRA_Redacao::action_reject(); ok(false, 'nonce'); } catch (PRA_Die $e) { ok(!str_starts_with($e->getMessage(), 'redirect:') && get_post_status($id) === 'pending', 'nonce inválido bloqueado'); }
$_POST = ['post' => $id, '_wpnonce' => wp_create_nonce('pra_red_approve')]; $_REQUEST = $_POST;
try { PRA_Redacao::action_approve(); } catch (PRA_Die $e) {}
ok(get_post_meta($id, '_pra_red_state', true) === 'revisado_aguardando_publicacao' && get_post_status($id) === 'pending' && !get_post_meta($id, '_pra_editorial_approved_hash', true), '"Marcar revisado" não publica nem grava hash de aprovação');
$_POST = ['agent' => 'checagem', '_wpnonce' => wp_create_nonce('pra_red_suspend')]; $_REQUEST = $_POST;
try { PRA_Redacao::action_suspend(); } catch (PRA_Die $e) {}
ok(in_array('checagem', PRA_Redacao::settings()['suspended'], true), 'suspender agente com nonce + manage_options');
wp_update_post(['ID' => $id, 'post_content' => get_post($id)->post_content . '<p>Revisão humana (fixture).</p>']);
$h = array_column((array) get_post_meta($id, '_pra_red_history', true), 'what');
ok($h[0] === 'criado' && in_array('editado', $h, true) && in_array('revisado', $h, true) && count(wp_get_post_revisions($id)) >= 1, 'histórico: criado → revisado → editado + revisões do WP');
$_POST = ['post' => $id, '_wpnonce' => wp_create_nonce('pra_red_reject')]; $_REQUEST = $_POST;
try { PRA_Redacao::action_reject(); } catch (PRA_Die $e) {}
ok(get_post_status($id) === 'draft' && get_post_meta($id, '_pra_red_state', true) === 'rejeitada', 'rejeitar devolve a rascunho (não apaga)');
$_POST = ['on' => '1', '_wpnonce' => wp_create_nonce('pra_red_kill')]; $_REQUEST = $_POST;
try { PRA_Redacao::action_kill(); } catch (PRA_Die $e) {}
ok(PRA_Redacao::settings()['kill_switch'] === 1, 'botão do painel liga o kill switch');
ok(count(array_filter((array) get_option(PRA_Redacao::LOG, []), fn($e) => $e['event'] === 'checagem')) >= 3, 'registro (log) das checagens');
ob_start(); PRA_Redacao::page(); $html = ob_get_clean();
ok(str_contains($html, 'Fila') && str_contains($html, '[FIXTURE]') && str_contains($html, 'pra_red_reject') && substr_count($html, 'name="_wpnonce"') >= 5, 'painel renderiza fila, ações e nonces');
wp_set_current_user($sub);
try { PRA_Redacao::page(); ok(false, 'painel'); } catch (PRA_Die $e) { ok(str_contains($e->getMessage(), 'Acesso negado'), 'painel negado para assinante'); }
wp_set_current_user($admin->ID);
// Restaurar
if (getenv('PRA_KEEP_FIXTURE_POSTS') !== '1') {
  foreach (get_posts(['post_type' => 'post', 'post_status' => 'any', 'fields' => 'ids', 'numberposts' => -1]) as $pid) if (!in_array($pid, $ids_before, true)) wp_delete_post($pid, true);
}
require_once ABSPATH . 'wp-admin/includes/user.php'; wp_delete_user($sub);
update_option('pra_config', $orig_cfg);
$orig_red === null ? delete_option(PRA_Redacao::OPT) : update_option(PRA_Redacao::OPT, $orig_red);
$orig_log === null ? delete_option(PRA_Redacao::LOG) : update_option(PRA_Redacao::LOG, $orig_log);
$c = get_option('pra_config');
ok((int) $c['ai_enabled'] === 0 && (int) $c['enabled'] === 0 && PRA_Redacao::settings()['kill_switch'] === 1, 'restaurado: enabled=0, ai_enabled=0, kill switch ligado');
if ($GLOBALS['pra_fail']) { fwrite(STDERR, $GLOBALS['pra_fail']." falha(s)\n"); exit(1); } echo "OK Redação Grok: integração WordPress\n";

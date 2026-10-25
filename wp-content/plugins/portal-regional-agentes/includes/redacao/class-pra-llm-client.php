<?php
/** Cliente de modelo de linguagem atrás de interface. Nesta versão só existe o stub desativado: nenhuma chamada de rede. */
if (!defined('ABSPATH')) exit;
interface PRA_LLM_Client {
  public function enabled(): bool;
  public function model(): string;
  /** @return array{ok:bool, story?:array, tokens_in:int, tokens_out:int, error?:string} */
  public function draft(array $pauta, array $agent, array $source_texts): array;
}
/** Grok (xAI) — DESATIVADO. Implementação real exige autorização, chave em wp-config e revisão de custos. */
final class PRA_Grok_Disabled_Client implements PRA_LLM_Client {
  public function enabled(): bool { return false; }
  public function model(): string { return 'grok (desativado)'; }
  public function draft(array $pauta, array $agent, array $source_texts): array {
    return ['ok' => false, 'tokens_in' => 0, 'tokens_out' => 0, 'error' => 'cliente_desativado'];
  }
}

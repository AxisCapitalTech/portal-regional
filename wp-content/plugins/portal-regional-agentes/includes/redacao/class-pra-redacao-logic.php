<?php
/**
 * Redação Grok — lógica pura (sem WordPress, sem rede).
 * Agentes, atribuição de pautas, deduplicação, esquema obrigatório, checagem, escalonamento,
 * limites de custo e kill switch. Nenhuma decisão aqui publica nada.
 */
if (!defined('ABSPATH')) exit;
final class PRA_Redacao_Logic {
  public const AGENTS = [
    'reporter_regional' => ['name' => 'Repórter Regional', 'coverage' => ['arapongas', 'apucarana'], 'role' => 'redige'],
    'metropolitano'     => ['name' => 'Repórter Metropolitano', 'coverage' => ['londrina', 'maringa'], 'role' => 'redige'],
    'parana_brasil'     => ['name' => 'Repórter Paraná/Brasil', 'coverage' => ['parana', 'brasil'], 'role' => 'redige'],
    'checagem'          => ['name' => 'Checagem', 'coverage' => ['*'], 'role' => 'verifica'],
    'editor_producao'   => ['name' => 'Editor de Produção', 'coverage' => ['*'], 'role' => 'monta rascunho pendente'],
  ];
  public const PERSONAS = [
    'fabio_roberto' => ['name'=>'Fábio Roberto','editorias'=>['politica','parana','brasil','mundo'],'ficticia'=>true],
    'leticia_alvarez' => ['name'=>'Letícia Alvarez','editorias'=>['cotidiano','tempo','cidades','regiao','arapongas','londrina','maringa','apucarana'],'ficticia'=>true],
    'alberto_torres' => ['name'=>'Alberto Torres','editorias'=>['esportes'],'ficticia'=>true],
  ];
  public static function assign_persona(array $pauta): ?string {
    $area=self::norm((string)($pauta['editoria']??$pauta['area']??''));
    foreach(self::PERSONAS as $key=>$persona) if(in_array($area,$persona['editorias'],true))return $key;
    return null;
  }
  /** Classificação conservadora; jamais concede autorização para publicar. */
  public static function classify(array $story): array {
    $text=self::norm(implode(' ',array_map('strval',[(string)($story['title']??''),(string)($story['lead']??''),(string)($story['body']??'')])));
    $area=self::norm((string)($story['editoria']??$story['area']??''));
    $reasons=[];
    foreach(array_merge(self::SENSITIVE,['morte','morreu','falecimento','guerra','conflito','bombardeio','feridos','vitimas','menores','hospital','doenca','risco','fraude','acusacao','denuncia','eleicoes','politica','presidente','ministro','governador','prefeito']) as $word){
      if(preg_match('/\b'.preg_quote(self::norm($word),'/').'\b/u',$text))$reasons[]='sensivel:'.$word;
    }
    if(!empty($story['contradictory'])||!empty($story['insufficient'])||empty($story['sources'])||empty($story['facts']))$reasons[]='verificacao_insuficiente';
    if(in_array($area,['politica','mundo','brasil','parana'],true))$reasons[]='editoria_requer_revisao';
    $allowed=false;
    if($area==='tempo'){$allowed=!empty($story['weather_licensed'])&&!empty($story['verified']);if(!$allowed)$reasons[]='licenca_ou_verificacao_pendente';}
    if($area==='esportes'){$allowed=!empty($story['official_result'])&&!empty($story['verified']);if(!$allowed)$reasons[]='resultado_nao_confirmado';}
    if(in_array($area,['servicos','calendario'],true)){$allowed=!empty($story['verified'])&&!empty($story['official_source']);if(!$allowed)$reasons[]='fonte_nao_confirmada';}
    if(!$allowed)$reasons[]='fora_da_lista_fechada';
    $reasons=array_values(array_unique($reasons));
    return ['class'=>$reasons?'revisao_humana':'auto_candidata','reasons'=>$reasons];
  }
  /** Limites padrão por agente (dia). Custos em US$ estimados; preços "a confirmar". */
  public const DEFAULT_LIMITS = ['max_calls_day' => 20, 'max_tokens_day' => 60000, 'max_usd_day' => 0.50];
  public const SIMILARITY_MAX = 0.60;   // acima disso = cópia/plágio da fonte
  public const DEDUP_MIN = 0.80;        // pautas com títulos ≥ 80% iguais = duplicadas
  public const SENSITIVE = ['homicidio','assassinato','estupro','abuso sexual','suicidio','crianca','adolescente','menor de idade',
    'eleicao','candidato','prefeito','vereador','processo judicial','investigacao','prisao','preso','acusado','denuncia',
    'saude mental','violencia domestica','racismo','corrupcao','improbidade','procuradoria'];
  public const ALLOWED_SOURCE_HOSTS = ['gov.br','pr.gov.br','leg.br','jus.br','mp.br','ebc.com.br','inmet.gov.br','arapongas.pr.gov.br',
    'londrina.pr.gov.br','maringa.pr.gov.br','apucarana.pr.gov.br','aen.pr.gov.br'];

  public static function norm(string $s): string {
    // E8-P1: transliteração independente de locale (iconv//TRANSLIT gera '?' com LC_CTYPE=C).
    $s = strtr($s, ['á'=>'a','à'=>'a','â'=>'a','ã'=>'a','ä'=>'a','é'=>'e','è'=>'e','ê'=>'e','ë'=>'e','í'=>'i','ì'=>'i','î'=>'i','ï'=>'i',
      'ó'=>'o','ò'=>'o','ô'=>'o','õ'=>'o','ö'=>'o','ú'=>'u','ù'=>'u','û'=>'u','ü'=>'u','ç'=>'c','ñ'=>'n',
      'Á'=>'A','À'=>'A','Â'=>'A','Ã'=>'A','Ä'=>'A','É'=>'E','È'=>'E','Ê'=>'E','Ë'=>'E','Í'=>'I','Ì'=>'I','Î'=>'I','Ï'=>'I',
      'Ó'=>'O','Ò'=>'O','Ô'=>'O','Õ'=>'O','Ö'=>'O','Ú'=>'U','Ù'=>'U','Û'=>'U','Ü'=>'U','Ç'=>'C','Ñ'=>'N']);
    $s = function_exists('iconv') ? (string) @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $s) : $s;
    return trim(preg_replace('/\s+/', ' ', preg_replace('/[^a-z0-9 ]/', ' ', strtolower($s))));
  }
  private static function shingles(string $s, int $n = 3): array {
    $w = array_values(array_filter(explode(' ', self::norm($s)), fn($x) => strlen($x) > 2));
    if (count($w) < $n) return $w ? [implode(' ', $w) => 1] : [];
    $out = [];
    for ($i = 0; $i <= count($w) - $n; $i++) $out[implode(' ', array_slice($w, $i, $n))] = 1;
    return $out;
  }
  /** Jaccard de trigramas de palavras (0..1). */
  public static function similarity(string $a, string $b, int $n = 3): float {
    $x = self::shingles($a, $n); $y = self::shingles($b, $n);
    if (!$x || !$y) return 0.0;
    $i = count(array_intersect_key($x, $y));
    return $i / (count($x) + count($y) - $i);
  }

  /** Atribui pauta ao repórter pela cobertura. Cidade desconhecida → null (humano decide). */
  public static function assign(array $pauta, array $suspended = []): ?string {
    $area = self::norm((string) ($pauta['area'] ?? ''));
    foreach (self::AGENTS as $id => $a) {
      if ($a['role'] !== 'redige' || in_array($id, $suspended, true)) continue;
      if (in_array($area, $a['coverage'], true)) return $id;
    }
    return null;
  }
  public static function fingerprint(array $pauta): string {
    return hash('sha256', self::norm((string) ($pauta['title'] ?? '')) . '|' . strtolower(trim((string) ($pauta['url'] ?? ''))));
  }
  /** Remove duplicadas (mesma URL ou título muito parecido) mantendo a primeira. */
  public static function dedup(array $pautas, array $known_titles = []): array {
    $kept = []; $dupes = []; $urls = [];
    foreach ($pautas as $p) {
      $u = strtolower(trim((string) ($p['url'] ?? ''))); $t = (string) ($p['title'] ?? '');
      $dup = ($u !== '' && isset($urls[$u]));
      foreach (array_merge($known_titles, array_column($kept, 'title')) as $k) {
        if (!$dup && self::similarity($t, (string) $k, 1) >= self::DEDUP_MIN) $dup = true;
      }
      if ($dup) { $dupes[] = $p; continue; }
      $urls[$u] = 1; $kept[] = $p;
    }
    return ['kept' => $kept, 'duplicates' => $dupes];
  }

  public static function host_allowed(string $url): bool {
    $h = strtolower((string) parse_url($url, PHP_URL_HOST));
    if ($h === '' || strtolower((string) parse_url($url, PHP_URL_SCHEME)) !== 'https') return false;
    foreach (self::ALLOWED_SOURCE_HOSTS as $a) if ($h === $a || str_ends_with($h, '.' . $a)) return true;
    return false;
  }

  /** Esquema obrigatório da matéria. Retorna lista de erros (vazia = ok). */
  public static function validate_schema(array $s): array {
    $e = [];
    foreach (['title' => 10, 'subtitle' => 10, 'lead' => 30, 'body' => 200] as $k => $min)
      if (mb_strlen(trim((string) ($s[$k] ?? ''))) < $min) $e[] = "campo_$k";
    if (mb_strlen((string) ($s['title'] ?? '')) > 110) $e[] = 'titulo_longo';
    $src = $s['sources'] ?? null;
    if (!is_array($src) || !$src) $e[] = 'sem_fontes';
    else foreach ($src as $i => $f) {
      if (!is_array($f) || empty($f['url']) || empty($f['org']) || empty($f['accessed_at'])) { $e[] = "fonte_{$i}_incompleta"; continue; }
      if (!self::host_allowed((string) $f['url'])) $e[] = "fonte_{$i}_nao_autorizada";
      if (!preg_match('/^\d{4}-\d{2}-\d{2}/', (string) $f['accessed_at'])) $e[] = "fonte_{$i}_data_invalida";
    }
    if (!is_array($s['facts'] ?? null) || !$s['facts']) $e[] = 'sem_fatos_confirmados';
    if (!is_array($s['claims'] ?? null)) $e[] = 'campo_alegacoes';
    foreach (['seo_title', 'meta_description', 'focus_keyword'] as $k) if (trim((string) ($s['seo'][$k] ?? '')) === '') $e[] = "seo_$k";
    foreach (['agent', 'model', 'generated_at', 'pauta_fingerprint'] as $k) if (trim((string) ($s['provenance'][$k] ?? '')) === '') $e[] = "proveniencia_$k";
    if (($s['status'] ?? '') !== 'pending') $e[] = 'status_deve_ser_pending';
    return $e;
  }

  /**
   * Checagem. $source_texts: [indice => texto real da fonte consultada].
   * Rejeita: sem fontes, fato sem fonte, fato não encontrado na fonte, citação inventada, plágio,
   * clima apresentado como alerta oficial ou dado velho como atual. Temas sensíveis → humano.
   */
  public static function check(array $s, array $source_texts): array {
    $reasons = self::validate_schema($s); $escalate = [];
    $nsrc = is_array($s['sources'] ?? null) ? count($s['sources']) : 0;
    foreach ((array) ($s['facts'] ?? []) as $i => $f) {
      $ix = $f['source'] ?? null;
      if (!is_int($ix) || $ix < 0 || $ix >= $nsrc) { $reasons[] = "fato_{$i}_sem_fonte"; continue; }
      $txt = (string) ($source_texts[$ix] ?? '');
      if ($txt === '' || self::similarity((string) ($f['text'] ?? ''), $txt, 1) < 0.0001 || !self::supported((string) ($f['text'] ?? ''), $txt))
        $reasons[] = "fato_{$i}_nao_sustentado";
    }
    $all = implode("\n", $source_texts);
    $body = (string) ($s['body'] ?? '') . "\n" . (string) ($s['lead'] ?? '');
    if (preg_match_all('/[“"]([^”"]{12,})[”"]/u', $body, $m)) {
      foreach ($m[1] as $q) if (mb_stripos(self::norm($all), self::norm($q)) === false) $reasons[] = 'citacao_inventada';
    }
    foreach ($source_texts as $i => $t) if (self::similarity((string) ($s['body'] ?? ''), (string) $t) > self::SIMILARITY_MAX) $reasons[] = "plagio_fonte_$i";
    $hay = self::norm(($s['title'] ?? '') . ' ' . $body);
    foreach (self::SENSITIVE as $w) if (preg_match('/\b' . preg_quote($w, '/') . '\b/', $hay)) $escalate[] = $w;
    if (!empty($s['weather'])) {
      $w = $s['weather'];
      if (($w['state'] ?? '') !== 'live' && preg_match('/\b(agora|neste momento|atualmente)\b/', $hay)) $reasons[] = 'clima_desatualizado_como_atual';
      if (preg_match('/\balerta oficial\b|\baviso oficial\b/', $hay) && ($w['alert_source'] ?? '') !== 'INMET') $reasons[] = 'clima_como_alerta_oficial';
    }
    $reasons = array_values(array_unique($reasons));
    $decision = $reasons ? 'rejeitada' : ($escalate ? 'escalada_humano' : 'aprovada_checagem');
    return ['decision' => $decision, 'reasons' => $reasons, 'sensitive' => array_values(array_unique($escalate))];
  }
  /** Fato sustentado: ≥ 60% das palavras relevantes e todos os números aparecem na fonte. */
  public static function supported(string $fact, string $source): bool {
    $src = ' ' . self::norm($source) . ' ';
    preg_match_all('/\d+(?:[.,]\d+)?/', $fact, $nums);
    foreach ($nums[0] as $n) if (strpos($source, $n) === false) return false;
    $w = array_values(array_filter(explode(' ', self::norm($fact)), fn($x) => strlen($x) > 3));
    if (!$w) return false;
    $hit = 0; foreach ($w as $x) if (strpos($src, ' ' . $x . ' ') !== false) $hit++;
    return $hit / count($w) >= 0.6;
  }

  /** Limite por agente/dia. $ledger = ['calls'=>n,'tokens'=>n,'usd'=>f]. */
  public static function within_limits(array $ledger, int $next_tokens, float $next_usd, array $limits = self::DEFAULT_LIMITS): bool {
    return ($ledger['calls'] ?? 0) + 1 <= $limits['max_calls_day']
      && ($ledger['tokens'] ?? 0) + $next_tokens <= $limits['max_tokens_day']
      && ($ledger['usd'] ?? 0) + $next_usd <= $limits['max_usd_day'] + 1e-9;
  }
  /** IA só roda com: ai_enabled=1, kill switch desligado, cliente ativo e agente não suspenso. */
  public static function may_run(array $cfg, bool $kill_switch, bool $client_enabled, string $agent, array $suspended): array {
    if ($kill_switch) return [false, 'kill_switch'];
    if ((int) ($cfg['ai_enabled'] ?? 0) !== 1) return [false, 'ai_desativada'];
    if (!$client_enabled) return [false, 'cliente_desativado'];
    if (in_array($agent, $suspended, true)) return [false, 'agente_suspenso'];
    return [true, 'ok'];
  }
}

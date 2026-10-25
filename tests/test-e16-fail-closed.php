<?php
// Independent no-WordPress, no-network smoke test of fail-closed automation.
define('ABSPATH', __DIR__.'/');
function add_action(...$args) { return true; }
function get_option($name, $default=[]) { return ['enabled'=>1,'ai_enabled'=>1,'auto_publish_enabled'=>0,'kill_switch'=>0]; }
define('PR1_EDITORIAL_AUTOMATION_APPROVED', true);
define('PR1_OPENAI_ATOMIC_USD_BUDGET_VERIFIED', true);
define('PR1_OPENAI_EXPLICIT_OPT_IN', true);
define('PR1_OPENAI_BUDGET_APPROVED', true);
define('PR1_OPENAI_API_KEY', str_repeat('x', 30));
define('PR1_OPENAI_MODEL', 'test-model');
define('PR1_OPENAI_MAX_OUTPUT_TOKENS', 1000);
define('PR1_OPENAI_REQUESTS_PER_DAY', 10);
require_once __DIR__.'/../wp-content/plugins/portal-regional-agentes/includes/redacao/class-pra-openai-automation.php';
if ((new PRA_OpenAI_OptIn_Client())->enabled() || PRA_OpenAI_Automation::allowed()) {fwrite(STDERR,"FAIL: enabled despite budget guard\n");exit(1);}
echo "PASS: API and scheduler disabled even with opt-in flags supplied\n";

<?php
/** Isolated WordPress/MariaDB only. Real processes; all HTTP intercepted locally. */
if(!isset($argv[1])||!is_file($argv[1])){fwrite(STDERR,"Missing isolated wp-load.php\n");exit(1);}
define('DISABLE_WP_CRON',true);require $argv[1];
if(($argv[2]??'')==='worker'){
 $dir=$argv[3];$slot=$argv[4];touch($dir.'/ready-'.$slot);$deadline=microtime(true)+12;
 while(!file_exists($dir.'/go')){if(microtime(true)>$deadline)exit(2);usleep(10000);}
 $lock=PR_Collector_Lock::acquire();file_put_contents($dir.'/result-'.$slot,$lock?'won':'lost');
 while(!file_exists($dir.'/result-0')||!file_exists($dir.'/result-1')){if(microtime(true)>$deadline)exit(3);usleep(10000);}
 if($lock){$lock->renew();$lock->release();}exit;
}
function regression($ok,$name){if(!$ok)throw new RuntimeException($name);echo "OK $name\n";}
$dir=sys_get_temp_dir().'/pra-lock-'.bin2hex(random_bytes(8));mkdir($dir,0700);$workers=[];
$fp=hash('sha256',$dir);$table=PR_Fingerprint_Index::table();$renamed=false;
$oldConfig=get_option('pra_config');$oldOfficial=get_option('pra_official_options');
$oldReport=get_option('pra_report');$oldOfficialReport=get_option('pra_official_report');
$oldHealth=get_option('pra_official_health');
$http=function($pre,$args,$url){
 $GLOBALS['fixture_http'][]=$url;
 if(str_ends_with((string)parse_url($url,PHP_URL_PATH),'/robots.txt'))$body="User-agent: *\n".(!empty($GLOBALS['deny_robots'])?"Disallow: /":"Allow: /");
 elseif(str_contains($url,'feed='))$body='<rss><channel><item><title>Fixture local de regressão do índice ausente</title><link>https://192.0.2.1/fixture-local</link><pubDate>'.gmdate(DATE_RSS).'</pubDate></item></channel></rss>';
 else return new WP_Error('fixture_only','Network disabled in regression');
 return ['headers'=>[],'body'=>$body,'response'=>['code'=>200,'message'=>'OK'],'cookies'=>[]];
};
// Treat the TEST-NET fixture as the isolated site's own host: no DNS/network required.
$fixtureHome=static fn()=> 'https://192.0.2.1';
add_filter('pre_option_home',$fixtureHome);
add_filter('pre_http_request',$http,10,3);
try{
 PR_Fingerprint_Index::install();
 $wpdb->query($wpdb->prepare("INSERT INTO $table (fingerprint,post_id,created_at) VALUES (%s,0,UTC_TIMESTAMP()-INTERVAL 31 MINUTE)",$fp));
 regression(PR_Fingerprint_Index::reserve($fp),'expired orphan reservation is reclaimed');
 regression(!PR_Fingerprint_Index::reserve($fp),'recent reservation remains exclusive');
 PR_Fingerprint_Index::release($fp);
 delete_option('pra_official_lock');add_option('pra_official_lock',time()-60,'',false);
 for($i=0;$i<2;$i++){
  $pipes=[];$process=proc_open([PHP_BINARY,__FILE__,$argv[1],'worker',$dir,(string)$i],[0=>['pipe','r'],1=>['pipe','w'],2=>['pipe','w']],$pipes);
  if(!is_resource($process))throw new RuntimeException('Cannot start worker');fclose($pipes[0]);$workers[]=[$process,$pipes];
 }
 $deadline=microtime(true)+12;
 while(!file_exists($dir.'/ready-0')||!file_exists($dir.'/ready-1')){if(microtime(true)>$deadline)throw new RuntimeException('Workers not ready');usleep(10000);}
 touch($dir.'/go');
 foreach($workers as [$process,$pipes]){stream_get_contents($pipes[1]);$error=stream_get_contents($pipes[2]);fclose($pipes[1]);fclose($pipes[2]);if(proc_close($process)!==0)throw new RuntimeException('Worker failure: '.$error);}
 $workers=[];$results=[file_get_contents($dir.'/result-0'),file_get_contents($dir.'/result-1')];sort($results);
 regression($results===['lost','won'],'two real PHP processes reclaim expired lease: exactly one winner');
 $old=PR_Collector_Lock::acquire();regression($old!==null,'lease acquired');
 $wpdb->update($wpdb->options,['option_value'=>'expired-owner|1'],['option_name'=>'pra_official_lock']);wp_cache_delete('pra_official_lock','options');
 $next=PR_Collector_Lock::acquire();regression($next!==null,'expired lease transferred');$old->release();
 regression(PR_Collector_Lock::acquire()===null,'previous owner cannot release successor lease');$next->release();
 // N-01: inject loss of ownership after acquire, before the first renew().
 // The health option is read only after the lease has been acquired.
 update_option('pra_official_options',['enabled'=>0,'aen'=>0,'arapongas'=>0,'londrina'=>0,'maringa'=>0,'apucarana'=>0]);
 $foreignLease='foreign-owner|'.(time()+3600);
 $stealLock=static function($pre)use($wpdb,$foreignLease){
  $wpdb->update($wpdb->options,['option_value'=>$foreignLease],['option_name'=>'pra_official_lock']);
  wp_cache_delete('pra_official_lock','options');
  return $pre;
 };
 add_filter('pre_option_pra_official_health',$stealLock,10,1);
 try {PR_Official::collect(true);}finally{remove_filter('pre_option_pra_official_health',$stealLock,10);}
 $aborted=(array)get_option('pra_official_report');
 regression(($aborted['aborted']??'')==='lock perdido','official collector returns normally and logs lost lease');
 $preserved=(string)$wpdb->get_var($wpdb->prepare("SELECT option_value FROM {$wpdb->options} WHERE option_name=%s",'pra_official_lock'));
 regression($preserved===$foreignLease,'lost lease never deletes successor owner');
 delete_option('pra_official_lock');

 update_option('pra_config',['enabled'=>0,'ai_enabled'=>0,'sources'=>"Fixture|https://192.0.2.1/?feed=rss2|Brasil"]);
 delete_transient('pra_robots_'.md5('192.0.2.1'));$GLOBALS['deny_robots']=true;$GLOBALS['fixture_http']=[];
 PR_Agentes::collect(true);$report=get_option('pra_report');
 regression(count($GLOBALS['fixture_http'])===1 && str_ends_with($GLOBALS['fixture_http'][0],'/robots.txt') && $report['created']===0,'generic RSS Disallow / never requests feed');
 $GLOBALS['deny_robots']=false;delete_transient('pra_robots_'.md5('192.0.2.1'));
 regression($wpdb->query("RENAME TABLE $table TO {$table}_regression_backup")!==false,'temporarily rename fingerprint table');$renamed=true;
 $previous=$wpdb->suppress_errors(true);
 PR_Agentes::collect(true);$report=get_option('pra_report');
 regression($report['duplicates']===0 && str_contains(implode(' ',$report['errors']),'erro SQL'),'generic RSS missing table is reported as SQL error, not duplicate');
 update_option('pra_official_options',['enabled'=>0,'arapongas'=>0,'londrina'=>1,'maringa'=>0,'apucarana'=>0,'aen'=>0]);
 // Official URL validation must still apply: fixture is local but uses the allowed origin.
 $officialHttp=function($pre,$args,$url)use($http){$res=$http($pre,$args,$url);if(is_array($res))$res['body']=str_replace('https://192.0.2.1/fixture-local','https://blog.londrina.pr.gov.br/?p=999999999',$res['body']);return $res;};
 add_filter('pre_http_request',$officialHttp,11,3);delete_transient('pra_robots_'.md5('blog.londrina.pr.gov.br'));
 PR_Official::collect(true);$report=get_option('pra_official_report');
 regression($report['duplicates']===0 && str_contains($report['sources']['londrina']['status'],'erro SQL'),'official collector missing table is reported as SQL error');
 $wpdb->suppress_errors($previous);
 regression(empty(get_option('pra_config')['enabled'])&&empty(get_option('pra_official_options')['enabled']),'both collectors remain disabled');
}catch(Throwable $e){fwrite(STDERR,'FAIL '.$e->getMessage()."\n");$failed=true;}
finally{
 foreach($workers as [$process,$pipes])if(is_resource($process)){proc_terminate($process);proc_close($process);}
 if($renamed)$wpdb->query("RENAME TABLE {$table}_regression_backup TO $table");
 PR_Fingerprint_Index::release($fp);delete_option('pra_official_lock');
 update_option('pra_config',$oldConfig);update_option('pra_official_options',$oldOfficial);
 update_option('pra_report',$oldReport);update_option('pra_official_report',$oldOfficialReport);
 update_option('pra_official_health',$oldHealth);
 remove_filter('pre_http_request',$http,10);remove_filter('pre_option_home',$fixtureHome);
 foreach(glob($dir.'/*') as $file)unlink($file);rmdir($dir);
}
exit(empty($failed)?0:1);

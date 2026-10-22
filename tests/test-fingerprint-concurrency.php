<?php
/** Real database regression: two PHP processes contend for one UNIQUE key. */
if(!isset($argv[1])||!is_file($argv[1])){fwrite(STDERR,"Missing isolated WordPress wp-load.php\n");exit(1);}
define('DISABLE_WP_CRON',true);
require $argv[1];
if(($argv[2]??'')==='worker'){
 $dir=$argv[3];$fp=$argv[4];$slot=$argv[5];
 touch($dir.'/ready-'.$slot);
 $deadline=microtime(true)+10;
 while(!file_exists($dir.'/go')){
  if(microtime(true)>$deadline)exit(2);
  usleep(10000);
 }
 echo PR_Fingerprint_Index::reserve($fp)?'won':'lost';
 exit;
}
PR_Fingerprint_Index::install();
$dir=sys_get_temp_dir().'/pr1-race-'.bin2hex(random_bytes(8));
mkdir($dir,0700);$fp=hash('sha256',$dir);$workers=[];$legacy_id=0;
try{
 for($i=0;$i<2;$i++){
  $pipes=[];
  $process=proc_open([PHP_BINARY,__FILE__,$argv[1],'worker',$dir,$fp,(string)$i],
   [0=>['pipe','r'],1=>['pipe','w'],2=>['pipe','w']],$pipes);
  if(!is_resource($process))throw new RuntimeException('Cannot start worker');
  fclose($pipes[0]);$workers[]=[$process,$pipes];
 }
 $deadline=microtime(true)+10;
 while(!file_exists($dir.'/ready-0')||!file_exists($dir.'/ready-1')){
  if(microtime(true)>$deadline)throw new RuntimeException('Workers not ready');
  usleep(10000);
 }
 touch($dir.'/go');$results=[];
 foreach($workers as [$process,$pipes]){
  $results[]=trim(stream_get_contents($pipes[1]));$err=stream_get_contents($pipes[2]);
  fclose($pipes[1]);fclose($pipes[2]);
  if(proc_close($process)!==0)throw new RuntimeException('Worker failed: '.$err);
 }
 $workers=[];sort($results);
 if($results!==['lost','won'])throw new RuntimeException('Expected one winner: '.json_encode($results));
 echo "OK two real PHP processes: exactly one fingerprint reservation\n";
 $legacy_id=wp_insert_post(['post_title'=>'Synthetic migration fixture','post_status'=>'pending'],true);
 if(is_wp_error($legacy_id))throw new RuntimeException('Fixture creation failed');
 $legacy_fp=hash('sha256',$dir.'-legacy');
 update_post_meta($legacy_id,'_pra_source_fingerprint',$legacy_fp);
 delete_option('pra_fingerprint_schema_version');
 PR_Fingerprint_Index::install();
 if(PR_Fingerprint_Index::reserve($legacy_fp))throw new RuntimeException('Legacy fingerprint was not migrated');
 if(get_post_meta($legacy_id,'_pra_source_fingerprint',true)!==$legacy_fp)throw new RuntimeException('Legacy metadata changed');
 echo "OK migration preserves legacy metadata and prevents duplicate reservation\n";
}catch(Throwable $e){fwrite(STDERR,"FAIL ".$e->getMessage()."\n");$failed=true;}
finally{
 foreach($workers as [$process,$pipes]){if(is_resource($process)){proc_terminate($process);proc_close($process);}}
 PR_Fingerprint_Index::release($fp);
 if($legacy_id && !is_wp_error($legacy_id)){
  $wpdb->delete(PR_Fingerprint_Index::table(),['post_id'=>$legacy_id],['%d']);
  wp_delete_post($legacy_id,true);
 }
 foreach(glob($dir.'/*') as $file)unlink($file);rmdir($dir);
}
exit(empty($failed)?0:1);

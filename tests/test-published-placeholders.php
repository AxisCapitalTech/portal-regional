<?php
/** Run through WP-CLI eval-file on isolated WordPress, or standalone for fixtures. */
function pr1_placeholder_pages(array $pages):array {
 $invalid=[];
 foreach($pages as $page){
  if($page->post_status!=='publish')continue;
  if(str_contains($page->post_content,'[')||stripos($page->post_content,'A SER DEFINIDO')!==false)$invalid[]=(int)$page->ID;
 }
 return $invalid;
}
$pages=[];
foreach([['draft','[PREENCHER]'],['publish','[PREENCHER]'],['publish','A SER DEFINIDO'],['publish','Conteúdo revisado']] as $i=>[$status,$content])$pages[]=(object)['ID'=>$i+1,'post_status'=>$status,'post_content'=>$content];
if(pr1_placeholder_pages($pages)!==[2,3]){fwrite(STDERR,"FAIL placeholder regression\n");exit(1);}
echo "OK fixtures reject published placeholders and allow drafts\n";
if(function_exists('get_posts')){
 $invalid=pr1_placeholder_pages(get_posts(['post_type'=>'page','post_status'=>'publish','numberposts'=>-1]));
 if($invalid){fwrite(STDERR,'FAIL published pages contain placeholders; IDs: '.implode(',',$invalid)."\n");exit(1);}
 echo "OK isolated WordPress published pages contain no placeholders\n";
}

/* Persiste preferências acessíveis no navegador, sem rastrear conteúdo lido. */
(()=>{'use strict';
const controls=[['[data-pr1-contrast]','pr1-high-contrast'],['[data-pr1-type]','pr1-large-type']];
for(const [selector,cls] of controls){
 const el=document.querySelector(selector);if(!el)continue;
 let enabled=false;
 try{enabled=window.localStorage.getItem('pr1-pref-'+cls)==='1';}catch(_){}
 document.body.classList.toggle(cls,enabled);el.setAttribute('aria-pressed',String(enabled));
 el.addEventListener('click',()=>{
  enabled=document.body.classList.toggle(cls);
  el.setAttribute('aria-pressed',String(enabled));
  try{window.localStorage.setItem('pr1-pref-'+cls,enabled?'1':'0');}catch(_){}
 });
}
})();

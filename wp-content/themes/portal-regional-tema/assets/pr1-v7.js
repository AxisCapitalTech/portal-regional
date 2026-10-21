/* Preferências locais, sem serviços externos. */
(()=>{'use strict';
const controls=[['[data-pr1-contrast]','pr1-high-contrast'],['[data-pr1-type]','pr1-large-type']];
for(const [selector,cls] of controls){
 const el=document.querySelector(selector);
 if(!el)continue;
 const key='pr1-reading-'+cls;
 try{if(localStorage.getItem(key)==='on')document.body.classList.add(cls);}catch(_){}
 el.setAttribute('aria-pressed',String(document.body.classList.contains(cls)));
 el.addEventListener('click',()=>{
  const enabled=document.body.classList.toggle(cls);
  el.setAttribute('aria-pressed',String(enabled));
  try{localStorage.setItem(key,enabled?'on':'off');}catch(_){}
 });
}
})();

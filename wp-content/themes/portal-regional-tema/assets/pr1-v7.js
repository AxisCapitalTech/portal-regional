/* PR1 News V7: preferência de leitura local à página, sem dependências externas. */
(()=>{'use strict';
const controls=[['[data-pr1-contrast]','pr1-high-contrast'],['[data-pr1-type]','pr1-large-type']];
for(const [selector,cls] of controls){const el=document.querySelector(selector);if(!el)continue;
el.addEventListener('click',()=>{const enabled=document.body.classList.toggle(cls);el.setAttribute('aria-pressed',String(enabled));});}
})();

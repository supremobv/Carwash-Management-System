document.addEventListener('DOMContentLoaded',()=>{
 document.querySelector('[data-menu]')?.addEventListener('click',()=>document.querySelector('.sidebar').classList.toggle('open'));
 document.querySelectorAll('[data-print]').forEach(b=>b.addEventListener('click',()=>window.print()));
 document.querySelectorAll('form[data-confirm]').forEach(f=>f.addEventListener('submit',e=>{if(!window.confirm(f.dataset.confirm))e.preventDefault()}));
 document.querySelectorAll('form').forEach(f=>f.addEventListener('submit',()=>{if(f.checkValidity())setTimeout(()=>f.querySelectorAll('button[type=submit]').forEach(b=>{b.disabled=true;b.textContent='Saving…'}),0)}));
 const checkboxes=document.querySelectorAll('[data-price]');const summary=document.querySelector('[data-estimate]');
 const update=()=>{if(!summary)return;let subtotal=0;checkboxes.forEach(c=>{if(c.checked)subtotal+=Number(c.dataset.price)});const discount=Math.round(Number(document.querySelector('[name=discount]')?.value||0)*100);const net=Math.max(0,subtotal-discount);const tax=Math.round(net*Number(summary.dataset.tax)/100);summary.textContent=summary.dataset.currency+' '+((net+tax)/100).toLocaleString(undefined,{minimumFractionDigits:2,maximumFractionDigits:2});};
 checkboxes.forEach(c=>c.addEventListener('change',update));document.querySelector('[name=discount]')?.addEventListener('input',update);update();
});

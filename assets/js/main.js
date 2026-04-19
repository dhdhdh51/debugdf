/**
 * School ERP v2.0 — Main JavaScript
 */
(function () {
  'use strict';

  /* Sidebar */
  const sidebar=document.getElementById('appSidebar'),overlay=document.getElementById('sidebarOverlay'),hamburger=document.getElementById('hamburgerBtn');
  function setHamburgerIcon(open){
    const i=hamburger?.querySelector('i');
    if(i)i.className=open?'bi bi-x fs-5':'bi bi-list fs-5';
  }
  function openSidebar(){
    if(!sidebar)return;
    sidebar.classList.add('open');
    overlay?.classList.add('open');
    document.body.style.overflow='hidden';
    setHamburgerIcon(true);
  }
  function closeSidebar(){
    if(!sidebar)return;
    sidebar.classList.remove('open');
    overlay?.classList.remove('open');
    document.body.style.overflow='';
    setHamburgerIcon(false);
  }
  hamburger?.addEventListener('click',()=>sidebar?.classList.contains('open')?closeSidebar():openSidebar());
  overlay?.addEventListener('click',closeSidebar);
  document.addEventListener('keydown',e=>{if(e.key==='Escape')closeSidebar();});
  // Close sidebar when a nav link is tapped on mobile
  sidebar?.querySelectorAll('.sidebar-link').forEach(link=>{
    link.addEventListener('click',()=>{if(window.innerWidth<992)closeSidebar();});
  });

  /* Auto-dismiss flash */
  document.querySelectorAll('.alert.alert-success,.alert.alert-info').forEach(el=>{
    setTimeout(()=>{el.style.transition='opacity 0.4s';el.style.opacity='0';setTimeout(()=>el.remove(),400);},5000);
  });

  /* Active sidebar */
  const cur=window.location.pathname.replace(/\/+$/,'');
  document.querySelectorAll('.sidebar-link').forEach(link=>{
    const href=(link.getAttribute('href')||'').replace(/\/+$/,'');
    if(!href)return;
    if(cur===href||(href.length>1&&cur.startsWith(href)))link.classList.add('active');
  });

  /* Section dropdown AJAX */
  document.querySelectorAll('#classSelect').forEach(sel=>{
    const target=document.getElementById('sectionSelect');
    if(!target)return;
    sel.addEventListener('change',function(){
      const cid=this.value;target.innerHTML='<option value="">Loading...</option>';target.disabled=true;
      if(!cid){target.innerHTML='<option value="">Select Section</option>';target.disabled=false;return;}
      fetch('/admin/ajax/get-sections.php?class_id='+encodeURIComponent(cid))
        .then(r=>r.json()).then(data=>{
          target.innerHTML='<option value="">Select Section</option>';
          data.forEach(s=>{target.innerHTML+=`<option value="${s.id}">${s.name}</option>`;});
          target.disabled=false;
        }).catch(()=>{target.innerHTML='<option value="">Error</option>';target.disabled=false;});
    });
  });

  /* Password toggles */
  document.querySelectorAll('[id^="togglePwd"]').forEach(btn=>{
    btn.addEventListener('click',function(){
      const pw=this.previousElementSibling||document.getElementById('password')||document.getElementById('pwd');
      if(!pw)return;
      pw.type=pw.type==='password'?'text':'password';
      const i=this.querySelector('i');if(i){i.className=pw.type==='password'?'bi bi-eye':'bi bi-eye-slash';}
    });
  });

  /* Confirm links */
  document.querySelectorAll('[data-confirm]').forEach(el=>{
    el.addEventListener('click',function(e){if(!confirm(this.dataset.confirm||'Are you sure?'))e.preventDefault();});
  });

  /* Photo preview */
  document.querySelectorAll('input[type="file"][accept*="image"]').forEach(inp=>{
    inp.addEventListener('change',function(){
      const file=this.files[0];if(!file||!file.type.startsWith('image/'))return;
      const preview=document.getElementById('photoPreview')||document.getElementById(this.dataset.photoPreview||'');
      if(!preview)return;
      const r=new FileReader();r.onload=e=>{preview.src=e.target.result;};r.readAsDataURL(file);
    });
  });

  /* Attendance buttons */
  window.setAtt=function(id,status){
    const radio=document.getElementById(`att_${id}_${status}`);if(radio)radio.checked=true;
    const group=document.querySelector(`.att-btn-group[data-id="${id}"]`);if(!group)return;
    const cols={Present:'#22c55e',Absent:'#ef4444',Late:'#f5a623',Holiday:'#06b6d4'};
    group.querySelectorAll('.att-btn').forEach(b=>{
      const s=b.dataset.status;
      b.style.background=s===status?cols[s]:'rgba(255,255,255,0.04)';
      b.style.color=s===status?'#0a0f1e':cols[s];
      b.style.borderColor=cols[s];b.style.fontWeight=s===status?'700':'400';
    });
  };
  window.markAll=function(status){document.querySelectorAll('.att-btn-group').forEach(g=>window.setAtt(g.dataset.id,status));};

  /* Grade calculator */
  function calcGrade(pct){
    if(pct>=90)return['A+','#22c55e'];if(pct>=80)return['A','#22c55e'];
    if(pct>=70)return['B+','#4f8ef7'];if(pct>=60)return['B','#4f8ef7'];
    if(pct>=50)return['C+','#06b6d4'];if(pct>=40)return['C','#06b6d4'];
    if(pct>=33)return['D','#f5a623'];return['F','#ef4444'];
  }
  document.querySelectorAll('.marks-input').forEach(inp=>{
    const row=inp.closest('tr');if(!row)return;
    const badge=row.querySelector('.grade-badge');
    function refresh(){
      const max=parseFloat(document.getElementById('maxMarks')?.value||'100')||100;
      const val=parseFloat(inp.value);if(!badge)return;
      if(isNaN(val)||inp.value===''){badge.textContent='—';badge.style.color='var(--text-muted)';return;}
      const pct=Math.min(100,(val/max)*100);const[g,col]=calcGrade(pct);
      badge.textContent=g;badge.style.color=col;
    }
    inp.addEventListener('input',refresh);refresh();
  });

  /* Tooltip */
  if(typeof bootstrap!=='undefined'&&bootstrap.Tooltip)
    document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(el=>new bootstrap.Tooltip(el,{trigger:'hover'}));
})();

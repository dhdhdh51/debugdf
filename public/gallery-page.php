<?php
require_once dirname(__DIR__).'/config/config.php';
$page_title='Gallery'; $active_nav='gallery';
$gallery=$pdo->query("SELECT * FROM gallery WHERE is_active=1 ORDER BY sort_order ASC,id DESC")->fetchAll();
include INCLUDES_PATH.'public_header.php';
?>
<div style="padding-top:80px;">
<div class="page-hero text-center text-white"><div class="container"><h1 style="font-family:'Playfair Display',serif;font-size:clamp(2rem,4vw,3rem);font-weight:800;margin-bottom:12px;">Photo Gallery</h1><p style="color:rgba(255,255,255,0.6);">A glimpse of campus life, events, and achievements.</p></div></div>
<section class="py-5" style="background:#0f1729;min-height:60vh;"><div class="container">
  <?php if(empty($gallery)):?><div class="text-center py-5" style="color:rgba(255,255,255,0.3);"><i class="bi bi-images" style="font-size:4rem;display:block;margin-bottom:16px;"></i><h4>Gallery Coming Soon</h4></div>
  <?php else:?><div class="row g-3">
    <?php foreach($gallery as $img):?><div class="col-6 col-md-4 col-lg-3">
      <div style="border-radius:14px;overflow:hidden;aspect-ratio:1;cursor:pointer;position:relative;" onclick="openLb('<?=UPLOADS_URL.'/'.htmlspecialchars($img['image'],ENT_QUOTES)?>','<?=htmlspecialchars($img['title']??'',ENT_QUOTES)?>')">
        <img src="<?=UPLOADS_URL.'/'.htmlspecialchars($img['image'],ENT_QUOTES)?>" alt="<?=htmlspecialchars($img['title']??'',ENT_QUOTES)?>" loading="lazy" style="width:100%;height:100%;object-fit:cover;transition:transform 0.4s;" onmouseover="this.style.transform='scale(1.08)'" onmouseout="this.style.transform='scale(1)'">
        <div style="position:absolute;inset:0;background:rgba(245,166,35,0.7);display:flex;align-items:center;justify-content:center;opacity:0;transition:opacity 0.3;color:#0a0f1e;font-size:2rem;" onmouseover="this.style.opacity=1" onmouseout="this.style.opacity=0"><i class="bi bi-zoom-in"></i></div>
      </div>
    </div><?php endforeach;?>
  </div><?php endif;?>
</div></section>
</div>
<div id="lb" onclick="if(event.target===this)closeLb()" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,0.93);z-index:9999;align-items:center;justify-content:center;flex-direction:column;">
  <span onclick="closeLb()" style="position:absolute;top:24px;right:32px;color:#fff;font-size:2.5rem;cursor:pointer;">&times;</span>
  <img id="lb-img" src="" alt="" style="max-width:90vw;max-height:85vh;border-radius:12px;object-fit:contain;">
</div>
<script>function openLb(src,cap){document.getElementById('lb-img').src=src;document.getElementById('lb').style.display='flex';document.body.style.overflow='hidden';}function closeLb(){document.getElementById('lb').style.display='none';document.body.style.overflow='';}</script>
<?php include INCLUDES_PATH.'public_footer.php';?>

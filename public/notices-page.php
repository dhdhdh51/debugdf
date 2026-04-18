<?php
require_once dirname(__DIR__).'/config/config.php';
$page_title='Notices & Events'; $active_nav='notices';
$cat=sanitize($_GET['cat']??'all'); $valid=['all','general','exam','event','holiday','admission'];
if(!in_array($cat,$valid)) $cat='all';
$where='WHERE is_active=1'; $params=[];
if($cat!=='all'){$where.=' AND category=?';$params[]=$cat;}
$stmt=$pdo->prepare("SELECT * FROM notices $where ORDER BY created_at DESC");$stmt->execute($params);$notices=$stmt->fetchAll();
$cat_colors=['general'=>'#4f8ef7','exam'=>'#f5a623','event'=>'#22c55e','holiday'=>'#ef4444','admission'=>'#06b6d4'];
include INCLUDES_PATH.'public_header.php';
?>
<div style="padding-top:80px;">
<div class="page-hero text-center text-white"><div class="container"><h1 style="font-family:'Playfair Display',serif;font-size:clamp(2rem,4vw,3rem);font-weight:800;margin-bottom:12px;">Notices &amp; Events</h1><p style="color:rgba(255,255,255,0.6);">Stay updated with the latest school announcements.</p></div></div>
<section class="py-5" style="background:#0f1729;min-height:60vh;"><div class="container">
  <div class="row g-4">
    <div class="col-lg-3">
      <div class="card sticky-top" style="top:80px;">
        <div class="card-header" style="background:linear-gradient(135deg,#f5a623,#c47f0a);color:#0a0f1e;font-weight:700;">Filter</div>
        <div style="padding:8px;">
          <?php foreach(['all'=>'All Notices','general'=>'General','exam'=>'Exams','event'=>'Events','holiday'=>'Holidays','admission'=>'Admissions'] as $val=>$lbl):?>
          <a href="?cat=<?=$val?>" style="display:block;padding:8px 12px;border-radius:8px;text-decoration:none;margin-bottom:4px;color:<?=$cat===$val?'#f5a623':'rgba(255,255,255,0.6)'?>;background:<?=$cat===$val?'rgba(245,166,35,0.1)':'transparent'?>;font-size:0.85rem;font-weight:<?=$cat===$val?'600':'400'?>;"><?=$lbl?></a>
          <?php endforeach;?>
        </div>
      </div>
    </div>
    <div class="col-lg-9">
      <?php if(empty($notices)):?><div class="text-center py-5" style="color:rgba(255,255,255,0.3);"><i class="bi bi-bell-slash" style="font-size:3rem;display:block;margin-bottom:12px;"></i><h4>No notices found</h4></div>
      <?php else:foreach($notices as $n): $nc=$cat_colors[$n['category']]??'#4f8ef7';?>
      <div style="background:rgba(255,255,255,0.03);border:1px solid rgba(255,255,255,0.07);border-left:3px solid <?=$nc?>;border-radius:0 12px 12px 0;padding:18px 20px;margin-bottom:12px;transition:all 0.2s;" onmouseover="this.style.background='rgba(255,255,255,0.05)'" onmouseout="this.style.background='rgba(255,255,255,0.03)'">
        <div class="d-flex align-items-start gap-14">
          <div style="flex:1;">
            <span style="font-size:0.68rem;text-transform:uppercase;letter-spacing:0.1em;color:<?=$nc?>;font-weight:700;"><?=htmlspecialchars($n['category'])?></span>
            <h5 style="color:#fff;font-weight:600;margin:6px 0 6px;font-size:1rem;"><?=htmlspecialchars($n['title'])?></h5>
            <?php if($n['body']):?><p style="color:rgba(255,255,255,0.5);font-size:0.85rem;margin:0;"><?=htmlspecialchars(mb_substr($n['body'],0,150))?><?=mb_strlen($n['body'])>150?'…':''?></p><?php endif;?>
          </div>
          <span style="font-size:0.75rem;color:rgba(255,255,255,0.3);white-space:nowrap;flex-shrink:0;"><?=date('d M Y',strtotime($n['created_at']))?></span>
        </div>
      </div>
      <?php endforeach;endif;?>
    </div>
  </div>
</div></section>
</div>
<?php include INCLUDES_PATH.'public_footer.php';?>

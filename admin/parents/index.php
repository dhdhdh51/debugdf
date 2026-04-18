<?php
require_once dirname(__DIR__, 2) . '/config/config.php';
require_once INCLUDES_PATH . 'auth_check.php';
auth_guard('admin');
$page_title='Parents'; $breadcrumb=[['label'=>'Dashboard','url'=>SITE_URL.'/admin/'],['label'=>'Parents','active'=>true]];
$page_action='<div class="d-flex gap-2"><a href="'.SITE_URL.'/admin/parents/add.php" class="btn btn-primary btn-sm"><i class="bi bi-person-plus me-1"></i>Add Parent</a><a href="'.SITE_URL.'/admin/students/link-parent.php" class="btn btn-outline-primary btn-sm"><i class="bi bi-link-45deg me-1"></i>Link Students</a></div>';
$search=sanitize($_GET['q']??''); $per_page=15; $page_num=sanitize_int($_GET['page']??1);
$where=['1=1']; $params=[];
if($search){$where[]="(p.name LIKE ? OR p.email LIKE ? OR p.phone LIKE ?)";$like="%{$search}%";$params=[$like,$like,$like];}
$wh=implode(' AND ',$where);
$ct=$pdo->prepare("SELECT COUNT(*) FROM parents p WHERE $wh");$ct->execute($params);
$total=(int)$ct->fetchColumn(); $pag=paginate($total,$per_page,$page_num);
$stmt=$pdo->prepare("SELECT p.*,(SELECT COUNT(*) FROM students s WHERE s.parent_id=p.id) as children_count FROM parents p WHERE $wh ORDER BY p.created_at DESC LIMIT ? OFFSET ?");
$params[]=$per_page;$params[]=$pag['offset'];$stmt->execute($params);$parents=$stmt->fetchAll();
include INCLUDES_PATH.'header.php';
?>
<div class="card mb-3"><div class="card-body py-2">
  <form method="GET" class="d-flex gap-2">
    <div class="input-group input-group-sm" style="max-width:300px;"><span class="input-group-text"><i class="bi bi-search"></i></span><input type="text" name="q" class="form-control" placeholder="Search parents..." value="<?=sanitize($search)?>"></div>
    <button type="submit" class="btn btn-primary btn-sm">Search</button>
    <a href="<?=SITE_URL?>/admin/parents/" class="btn btn-outline-secondary btn-sm">Clear</a>
  </form>
</div></div>
<div class="card"><div class="card-body p-0"><div class="table-responsive">
  <table class="table"><thead><tr><th>#</th><th>Name</th><th>Relation</th><th>Phone</th><th>Email</th><th>Children</th><th>Status</th><th class="text-end">Actions</th></tr></thead>
    <tbody>
      <?php if(empty($parents)):?><tr><td colspan="8" class="text-center py-5" style="color:var(--text-muted);"><i class="bi bi-people" style="font-size:2.5rem;display:block;margin-bottom:12px;"></i>No parents found.</td></tr>
      <?php else:foreach($parents as $i=>$p):?>
      <tr>
        <td class="text-muted"><?=$pag['offset']+$i+1?></td>
        <td class="fw-semibold"><?=sanitize($p['name'])?></td>
        <td><?=sanitize($p['relation']??'—')?></td>
        <td><?=sanitize($p['phone']??'—')?></td>
        <td style="font-size:0.8rem;color:var(--text-muted);"><?=sanitize($p['email']??'—')?></td>
        <td><span class="badge bg-primary"><?=$p['children_count']?> child<?=$p['children_count']!=1?'ren':''?></span></td>
        <td><span class="badge bg-<?=$p['status']==='active'?'success':'secondary'?>"><?=ucfirst($p['status'])?></span></td>
        <td class="text-end"><div class="btn-group btn-group-sm">
          <a href="<?=SITE_URL?>/admin/parents/add.php?edit=<?=$p['id']?>" class="btn btn-outline-warning"><i class="bi bi-pencil"></i></a>
          <a href="<?=SITE_URL?>/admin/parents/delete.php?id=<?=$p['id']?>" class="btn btn-outline-danger" onclick="return confirm('Delete parent?')"><i class="bi bi-trash"></i></a>
        </div></td>
      </tr>
      <?php endforeach;endif;?>
    </tbody>
  </table>
</div></div></div>
<?=pagination_links($pag,SITE_URL.'/admin/parents/?q='.urlencode($search))?>
<?php include INCLUDES_PATH.'footer.php';?>

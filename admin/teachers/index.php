<?php
require_once dirname(__DIR__,2).'/config/config.php';
require_once INCLUDES_PATH.'auth_check.php';
auth_guard('admin');
$page_title='Teachers';$breadcrumb=[['label'=>'Dashboard','url'=>SITE_URL.'/admin/'],['label'=>'Teachers','active'=>true]];
$page_action='<a href="'.SITE_URL.'/admin/teachers/add.php" class="btn btn-primary btn-sm"><i class="bi bi-person-plus me-1"></i>Add Teacher</a>';
$search=sanitize($_GET['q']??'');$per_page=15;$page_num=sanitize_int($_GET['page']??1);
$where=['1=1'];$params=[];
if($search){$where[]="(t.name LIKE ? OR t.teacher_id LIKE ? OR t.email LIKE ?)";$like="%{$search}%";$params=[$like,$like,$like];}
$wh=implode(' AND ',$where);
$ct=$pdo->prepare("SELECT COUNT(*) FROM teachers t WHERE $wh");$ct->execute($params);$total=(int)$ct->fetchColumn();
$pag=paginate($total,$per_page,$page_num);
$stmt=$pdo->prepare("SELECT t.*,c.name as class_name,s.name as subject_name FROM teachers t LEFT JOIN classes c ON t.class_id=c.id LEFT JOIN subjects s ON t.subject_id=s.id WHERE $wh ORDER BY t.created_at DESC LIMIT ? OFFSET ?");
$params[]=$per_page;$params[]=$pag['offset'];$stmt->execute($params);$teachers=$stmt->fetchAll();
include INCLUDES_PATH.'header.php';
?>
<div class="card mb-3"><div class="card-body py-2"><form method="GET" class="d-flex gap-2"><div class="input-group input-group-sm" style="max-width:300px;"><span class="input-group-text"><i class="bi bi-search"></i></span><input type="text" name="q" class="form-control" placeholder="Search teachers..." value="<?=sanitize($search)?>"></div><button type="submit" class="btn btn-primary btn-sm">Search</button><a href="<?=SITE_URL?>/admin/teachers/" class="btn btn-outline-secondary btn-sm">Clear</a></form></div></div>
<div class="card"><div class="card-body p-0"><div class="table-responsive"><table class="table table-hover align-middle mb-0">
<thead><tr><th>#</th><th>Photo</th><th>ID</th><th>Name</th><th>Subject</th><th>Class</th><th>Phone</th><th>Status</th><th class="text-end">Actions</th></tr></thead>
<tbody>
<?php if(empty($teachers)):?><tr><td colspan="9" class="text-center py-5" style="color:var(--text-muted);">No teachers found.</td></tr>
<?php else:foreach($teachers as $i=>$t):?>
<tr><td style="font-size:0.8rem;color:var(--text-muted);"><?=$pag['offset']+$i+1?></td>
<td><?php if($t['photo']):?><img src="<?=get_upload_url($t['photo'])?>" class="rounded-circle" width="36" height="36" style="object-fit:cover;"><?php else:?><div style="width:36px;height:36px;border-radius:50%;background:linear-gradient(135deg,#22c55e,#16a34a);display:flex;align-items:center;justify-content:center;font-size:0.85rem;font-weight:700;color:#fff;"><?=strtoupper(substr($t['name'],0,1))?></div><?php endif;?></td>
<td><code style="font-size:0.75rem;"><?=sanitize($t['teacher_id']??'N/A')?></code></td>
<td style="font-weight:600;color:#fff;"><?=sanitize($t['name'])?></td>
<td style="font-size:0.82rem;"><?=sanitize($t['subject_name']??'—')?></td>
<td style="font-size:0.82rem;"><?=sanitize($t['class_name']??'—')?></td>
<td style="font-size:0.82rem;color:var(--text-muted);"><?=sanitize($t['phone']??'—')?></td>
<td><span class="badge bg-<?=$t['status']==='active'?'success':'secondary'?>"><?=ucfirst($t['status'])?></span></td>
<td class="text-end"><div class="btn-group btn-group-sm">
<a href="<?=SITE_URL?>/admin/teachers/edit.php?id=<?=$t['id']?>" class="btn btn-outline-warning" title="Edit"><i class="bi bi-pencil"></i></a>
<a href="<?=SITE_URL?>/admin/teachers/delete.php?id=<?=$t['id']?>" class="btn btn-outline-danger" title="Delete" onclick="return confirm('Delete?')"><i class="bi bi-trash"></i></a>
</div></td></tr>
<?php endforeach;endif;?>
</tbody></table></div></div></div>
<?=pagination_links($pag,SITE_URL.'/admin/teachers/?q='.urlencode($search))?>
<?php include INCLUDES_PATH.'footer.php';?>

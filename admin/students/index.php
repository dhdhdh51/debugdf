<?php
require_once dirname(__DIR__,2).'/config/config.php';
require_once INCLUDES_PATH.'auth_check.php';
auth_guard('admin');
$page_title='Students';
$breadcrumb=[['label'=>'Dashboard','url'=>SITE_URL.'/admin/'],['label'=>'Students','active'=>true]];
$page_action='<a href="'.SITE_URL.'/admin/students/add.php" class="btn btn-primary btn-sm"><i class="bi bi-person-plus me-1"></i>Add Student</a>';
$search=sanitize($_GET['q']??''); $class_id=sanitize_int($_GET['class_id']??0); $status=sanitize($_GET['status']??'');
$per_page=15; $page_num=sanitize_int($_GET['page']??1);
$where=['1=1']; $params=[];
if($search){$where[]="(s.name LIKE ? OR s.student_id LIKE ? OR s.email LIKE ? OR s.phone LIKE ?)";$like="%{$search}%";$params=array_merge($params,[$like,$like,$like,$like]);}
if($class_id){$where[]="s.class_id=?";$params[]=$class_id;}
if($status){$where[]="s.status=?";$params[]=$status;}
$wh=implode(' AND ',$where);
$ct=$pdo->prepare("SELECT COUNT(*) FROM students s WHERE $wh");$ct->execute($params);$total=(int)$ct->fetchColumn();
$pag=paginate($total,$per_page,$page_num);
$stmt=$pdo->prepare("SELECT s.*,c.name as class_name,sec.name as section_name FROM students s LEFT JOIN classes c ON s.class_id=c.id LEFT JOIN sections sec ON s.section_id=sec.id WHERE $wh ORDER BY s.created_at DESC LIMIT ? OFFSET ?");
$params[]=$per_page;$params[]=$pag['offset'];$stmt->execute($params);$students=$stmt->fetchAll();
$classes=get_classes();
include INCLUDES_PATH.'header.php';
?>
<div class="card mb-3"><div class="card-body">
<form method="GET" class="row g-2 align-items-end">
<div class="col-12 col-sm-5"><div class="input-group input-group-sm"><span class="input-group-text"><i class="bi bi-search"></i></span><input type="text" name="q" class="form-control" placeholder="Name, ID, email..." value="<?=sanitize($search)?>"></div></div>
<div class="col-6 col-sm-3"><select name="class_id" class="form-select form-select-sm"><option value="">All Classes</option><?php foreach($classes as $cl):?><option value="<?=$cl['id']?>" <?=$class_id==$cl['id']?'selected':''?>><?=sanitize($cl['name'])?></option><?php endforeach;?></select></div>
<div class="col-6 col-sm-2"><select name="status" class="form-select form-select-sm"><option value="">All</option><option value="active" <?=$status==='active'?'selected':''?>>Active</option><option value="inactive" <?=$status==='inactive'?'selected':''?>>Inactive</option></select></div>
<div class="col-auto"><button type="submit" class="btn btn-primary btn-sm me-1">Filter</button><a href="<?=SITE_URL?>/admin/students/" class="btn btn-outline-secondary btn-sm">Clear</a></div>
<div class="col-auto ms-auto"><a href="<?=SITE_URL?>/admin/students/export.php?<?=http_build_query($_GET)?>" class="btn btn-sm btn-outline-secondary"><i class="bi bi-file-earmark-excel me-1"></i>CSV</a></div>
</form></div></div>
<div class="card"><div class="card-body p-0"><div class="table-responsive"><table class="table table-hover align-middle mb-0">
<thead><tr><th>#</th><th>Photo</th><th>Student ID</th><th>Name</th><th>Class</th><th>Phone</th><th>Status</th><th class="text-end">Actions</th></tr></thead>
<tbody>
<?php if(empty($students)):?><tr><td colspan="8" class="text-center py-5" style="color:var(--text-muted);"><i class="bi bi-people" style="font-size:2.5rem;display:block;margin-bottom:12px;"></i>No students found.</td></tr>
<?php else:foreach($students as $i=>$s):?>
<tr>
<td style="color:var(--text-muted);font-size:0.8rem;"><?=$pag['offset']+$i+1?></td>
<td><?php if($s['photo']):?><img src="<?=get_upload_url($s['photo'])?>" class="rounded-circle" width="36" height="36" style="object-fit:cover;"><?php else:?><div style="width:36px;height:36px;border-radius:50%;background:linear-gradient(135deg,#4f8ef7,#7b5ea7);display:flex;align-items:center;justify-content:center;font-size:0.85rem;font-weight:700;color:#fff;"><?=strtoupper(substr($s['name'],0,1))?></div><?php endif;?></td>
<td><code><?=sanitize($s['student_id'])?></code></td>
<td style="font-weight:600;color:#fff;"><?=sanitize($s['name'])?></td>
<td style="font-size:0.82rem;color:var(--text-muted);"><?=sanitize(($s['class_name']??'—').($s['section_name']?' — '.$s['section_name']:''))?></td>
<td style="font-size:0.82rem;color:var(--text-muted);"><?=sanitize($s['phone']??'—')?></td>
<td><span class="badge bg-<?=$s['status']==='active'?'success':'secondary'?>"><?=ucfirst($s['status'])?></span></td>
<td class="text-end"><div class="btn-group btn-group-sm">
<a href="<?=SITE_URL?>/admin/students/view.php?id=<?=$s['id']?>" class="btn btn-outline-primary" title="View"><i class="bi bi-eye"></i></a>
<a href="<?=SITE_URL?>/admin/students/id-card.php?id=<?=$s['id']?>" class="btn btn-outline-secondary" title="ID Card"><i class="bi bi-card-text"></i></a>
<a href="<?=SITE_URL?>/admin/students/edit.php?id=<?=$s['id']?>" class="btn btn-outline-warning" title="Edit"><i class="bi bi-pencil"></i></a>
<a href="<?=SITE_URL?>/admin/students/delete.php?id=<?=$s['id']?>" class="btn btn-outline-danger" title="Delete" onclick="return confirm('Delete this student?')"><i class="bi bi-trash"></i></a>
</div></td></tr>
<?php endforeach;endif;?>
</tbody></table></div></div></div>
<?=pagination_links($pag,SITE_URL.'/admin/students/?q='.urlencode($search).'&class_id='.$class_id.'&status='.$status)?>
<?php include INCLUDES_PATH.'footer.php';?>

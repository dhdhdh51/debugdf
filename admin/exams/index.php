<?php
require_once dirname(__DIR__, 2) . '/config/config.php';
require_once INCLUDES_PATH . 'auth_check.php';
auth_guard('admin');
$page_title='Exams'; $breadcrumb=[['label'=>'Dashboard','url'=>SITE_URL.'/admin/'],['label'=>'Exams','active'=>true]];
$page_action='<a href="'.SITE_URL.'/admin/exams/add.php" class="btn btn-primary btn-sm"><i class="bi bi-plus-circle me-1"></i>Create Exam</a>';
if(isset($_GET['delete'])){$eid=sanitize_int($_GET['delete']);$pdo->prepare("DELETE FROM exams WHERE id=?")->execute([$eid]);set_flash('success','Exam deleted.');redirect(SITE_URL.'/admin/exams/');}
if(isset($_GET['status'])&&isset($_GET['id'])){$eid=sanitize_int($_GET['id']);$st=sanitize($_GET['status']);if(in_array($st,['upcoming','ongoing','completed'])) $pdo->prepare("UPDATE exams SET status=? WHERE id=?")->execute([$st,$eid]);redirect(SITE_URL.'/admin/exams/');}
$exams=$pdo->query("SELECT e.*,c.name as class_name,(SELECT COUNT(*) FROM marks WHERE exam_id=e.id) as marks_entered,(SELECT COUNT(*) FROM results WHERE exam_id=e.id) as results_count FROM exams e LEFT JOIN classes c ON e.class_id=c.id ORDER BY e.created_at DESC")->fetchAll();
include INCLUDES_PATH.'header.php';
?>
<div class="card">
  <div class="card-body p-0">
    <div class="table-responsive">
      <table class="table">
        <thead><tr><th>#</th><th>Exam Name</th><th>Type</th><th>Class</th><th>Dates</th><th>Status</th><th>Marks</th><th class="text-end">Actions</th></tr></thead>
        <tbody>
          <?php if(empty($exams)):?><tr><td colspan="8" class="text-center py-5" style="color:var(--text-muted);"><i class="bi bi-clipboard2-data" style="font-size:2.5rem;display:block;margin-bottom:12px;"></i>No exams yet.</td></tr>
          <?php else:foreach($exams as $i=>$e):?>
          <tr>
            <td class="text-muted"><?=$i+1?></td>
            <td class="fw-semibold"><?=sanitize($e['name'])?></td>
            <td><span class="badge bg-info"><?=sanitize($e['type'])?></span></td>
            <td><?=sanitize($e['class_name']??'All')?></td>
            <td class="text-muted" style="font-size:0.8rem;"><?=format_date($e['start_date'])?> – <?=format_date($e['end_date'])?></td>
            <td>
              <div class="dropdown">
                <button class="btn btn-sm btn-outline-<?=match($e['status']){'upcoming'=>'warning','ongoing'=>'primary','completed'=>'success',default=>'secondary'}?> dropdown-toggle" type="button" data-bs-toggle="dropdown"><?=ucfirst($e['status'])?></button>
                <ul class="dropdown-menu"><?php foreach(['upcoming','ongoing','completed'] as $st):?><li><a class="dropdown-item" href="?status=<?=$st?>&id=<?=$e['id']?>"><?=ucfirst($st)?></a></li><?php endforeach;?></ul>
              </div>
            </td>
            <td style="font-size:0.8rem;"><?=$e['marks_entered']?> marks<br><span style="color:var(--text-muted);"><?=$e['results_count']?> results</span></td>
            <td class="text-end">
              <div class="btn-group btn-group-sm">
                <a href="<?=SITE_URL?>/admin/marks/?exam_id=<?=$e['id']?>" class="btn btn-outline-primary" title="Enter Marks"><i class="bi bi-pencil-square"></i></a>
                <a href="<?=SITE_URL?>/admin/results/?exam_id=<?=$e['id']?>" class="btn btn-outline-success" title="Results"><i class="bi bi-award"></i></a>
                <a href="<?=SITE_URL?>/admin/exams/add.php?id=<?=$e['id']?>" class="btn btn-outline-warning"><i class="bi bi-pencil"></i></a>
                <a href="?delete=<?=$e['id']?>" class="btn btn-outline-danger" onclick="return confirm('Delete exam?')"><i class="bi bi-trash"></i></a>
              </div>
            </td>
          </tr>
          <?php endforeach;endif;?>
        </tbody>
      </table>
    </div>
  </div>
</div>
<?php include INCLUDES_PATH.'footer.php';?>

<?php
require_once dirname(__DIR__, 2) . '/config/config.php';
require_once INCLUDES_PATH . 'auth_check.php';
require_once INCLUDES_PATH . 'mailer.php';
auth_guard('admin');
$page_title='Fee Management';
$breadcrumb=[['label'=>'Dashboard','url'=>SITE_URL.'/admin/'],['label'=>'Fees','active'=>true]];
$page_action='<a href="'.SITE_URL.'/admin/fees/add.php" class="btn btn-primary btn-sm"><i class="bi bi-plus-circle me-1"></i>Add Fee</a>';

if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['mark_paid'])) {
    csrf_protect();
    $fid = sanitize_int($_POST['fee_id'] ?? 0);
    if ($fid) {
        $pdo->prepare("UPDATE fees SET status='paid' WHERE id=?")->execute([$fid]);
        $stmt=$pdo->prepare("SELECT f.*,s.name as sn,s.email as se FROM fees f JOIN students s ON f.student_id=s.id WHERE f.id=?");
        $stmt->execute([$fid]); $fee=$stmt->fetch();
        if ($fee && $fee['se']) SchoolMailer::sendPaymentConfirmation($fee['se'],$fee['sn'],$fee['invoice_no'],(float)$fee['amount']);
        set_flash('success','Fee marked as paid.');
    }
    redirect(SITE_URL.'/admin/fees/');
}
$filter=sanitize($_GET['status']??''); $search=sanitize($_GET['q']??''); $class_id=sanitize_int($_GET['class_id']??0);
$per_page=15; $page_num=sanitize_int($_GET['page']??1);
$where=['1=1']; $params=[];
if ($filter){$where[]="f.status=?";$params[]=$filter;}
if ($class_id){$where[]="s.class_id=?";$params[]=$class_id;}
if ($search){$where[]="(s.name LIKE ? OR f.invoice_no LIKE ?)";$like="%{$search}%";$params=array_merge($params,[$like,$like]);}
$wh=implode(' AND ',$where);
$ct=$pdo->prepare("SELECT COUNT(*) FROM fees f JOIN students s ON f.student_id=s.id WHERE $wh");$ct->execute($params);
$total=(int)$ct->fetchColumn(); $pag=paginate($total,$per_page,$page_num);
$stmt=$pdo->prepare("SELECT f.*,s.name as student_name,s.student_id as stu_id,c.name as class_name FROM fees f JOIN students s ON f.student_id=s.id LEFT JOIN classes c ON s.class_id=c.id WHERE $wh ORDER BY f.created_at DESC LIMIT ? OFFSET ?");
$params[]=$per_page;$params[]=$pag['offset'];$stmt->execute($params);$fees=$stmt->fetchAll();
$classes=get_classes();
$summary=$pdo->query("SELECT SUM(amount) as total, SUM(CASE WHEN status='paid' THEN amount ELSE 0 END) as paid, SUM(CASE WHEN status='pending' THEN amount ELSE 0 END) as pending FROM fees")->fetch();
include INCLUDES_PATH.'header.php';
?>
<div class="row g-3 mb-3">
  <div class="col-4"><div class="card text-center py-3"><div class="fs-4 fw-bold"><?= currency_format((float)($summary['total']??0)) ?></div><div style="font-size:0.78rem;color:var(--text-muted);">Total Fees</div></div></div>
  <div class="col-4"><div class="card text-center py-3"><div class="fs-4 fw-bold" style="color:#22c55e;"><?= currency_format((float)($summary['paid']??0)) ?></div><div style="font-size:0.78rem;color:var(--text-muted);">Collected</div></div></div>
  <div class="col-4"><div class="card text-center py-3"><div class="fs-4 fw-bold" style="color:#ef4444;"><?= currency_format((float)($summary['pending']??0)) ?></div><div style="font-size:0.78rem;color:var(--text-muted);">Pending</div></div></div>
</div>
<div class="card mb-3"><div class="card-body py-2">
  <form method="GET" class="row g-2 align-items-center">
    <div class="col-12 col-sm-4"><div class="input-group input-group-sm"><span class="input-group-text"><i class="bi bi-search"></i></span><input type="text" name="q" class="form-control" placeholder="Search..." value="<?= sanitize($search) ?>"></div></div>
    <div class="col-6 col-sm-2"><select name="status" class="form-select form-select-sm"><option value="">All Status</option><?php foreach(['pending','paid','overdue','partial'] as $st):?><option value="<?= $st ?>" <?= $filter===$st?'selected':'' ?>><?= ucfirst($st) ?></option><?php endforeach;?></select></div>
    <div class="col-6 col-sm-2"><select name="class_id" class="form-select form-select-sm"><option value="">All Classes</option><?php foreach($classes as $cl):?><option value="<?= $cl['id'] ?>" <?= $class_id==$cl['id']?'selected':'' ?>><?= sanitize($cl['name']) ?></option><?php endforeach;?></select></div>
    <div class="col-auto"><button type="submit" class="btn btn-primary btn-sm me-1">Filter</button><a href="<?= SITE_URL ?>/admin/fees/" class="btn btn-outline-secondary btn-sm">Clear</a></div>
  </form>
</div></div>
<div class="card"><div class="card-body p-0"><div class="table-responsive">
  <table class="table">
    <thead><tr><th>#</th><th>Invoice</th><th>Student</th><th>Type</th><th>Amount</th><th>Due</th><th>Status</th><th class="text-end">Actions</th></tr></thead>
    <tbody>
      <?php if(empty($fees)):?><tr><td colspan="8" class="text-center py-5" style="color:var(--text-muted);">No fees found.</td></tr>
      <?php else:foreach($fees as $i=>$f):?>
      <tr>
        <td class="text-muted" style="font-size:0.8rem;"><?= $pag['offset']+$i+1 ?></td>
        <td><code style="color:#22c55e;"><?= sanitize($f['invoice_no']) ?></code></td>
        <td><div class="fw-semibold"><?= sanitize($f['student_name']) ?></div><code style="font-size:0.7rem;color:var(--text-muted);"><?= sanitize($f['stu_id']) ?></code></td>
        <td><?= sanitize($f['fee_type']) ?></td>
        <td class="fw-semibold"><?= currency_format((float)$f['amount']) ?></td>
        <td style="font-size:0.8rem;" class="<?= $f['due_date']&&$f['due_date']<date('Y-m-d')&&$f['status']==='pending'?'fw-bold':'text-muted' ?>"><?= format_date($f['due_date']) ?></td>
        <td><span class="badge bg-<?= match($f['status']){'paid'=>'success','overdue'=>'danger','partial'=>'info',default=>'warning'} ?>"><?= ucfirst($f['status']) ?></span></td>
        <td class="text-end">
          <?php if($f['status']!=='paid'):?>
          <form method="POST" class="d-inline" onsubmit="return confirm('Mark as paid?')"><?= csrf_field() ?><input type="hidden" name="mark_paid" value="1"><input type="hidden" name="fee_id" value="<?= $f['id'] ?>"><button type="submit" class="btn btn-success btn-sm me-1"><i class="bi bi-check-circle"></i></button></form>
          <?php endif;?>
          <a href="<?= SITE_URL ?>/admin/fees/add.php?delete=<?= $f['id'] ?>" class="btn btn-outline-danger btn-sm" onclick="return confirm('Delete?')"><i class="bi bi-trash"></i></a>
        </td>
      </tr>
      <?php endforeach;endif;?>
    </tbody>
  </table>
</div></div></div>
<?= pagination_links($pag, SITE_URL.'/admin/fees/?status='.$filter.'&q='.urlencode($search).'&class_id='.$class_id) ?>
<?php include INCLUDES_PATH.'footer.php';?>

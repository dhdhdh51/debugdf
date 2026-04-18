<?php
if (!defined('ROOT_PATH')) die('Direct access not allowed.');

function create_notification(int|null $user_id, string $role, string $title, string $message, string $type='info'): void {
    global $pdo;
    $pdo->prepare("INSERT INTO notifications (user_id,role,title,message,type) VALUES (?,?,?,?,?)")->execute([$user_id,$role,$title,$message,$type]);
}
function notify_role(string $role, string $title, string $message, string $type='info'): void {
    global $pdo;
    if ($role==='all') { $pdo->prepare("INSERT INTO notifications (user_id,role,title,message,type) VALUES (NULL,'all',?,?,?)")->execute([$title,$message,$type]); }
    else {
        $users=$pdo->prepare("SELECT id FROM users WHERE role=? AND status='active'"); $users->execute([$role]);
        $ins=$pdo->prepare("INSERT INTO notifications (user_id,role,title,message,type) VALUES (?,?,?,?,?)");
        foreach ($users->fetchAll() as $u) $ins->execute([$u['id'],$role,$title,$message,$type]);
    }
}
function get_unread_count(): int {
    global $pdo;
    if (!is_logged_in()) return 0;
    $stmt=$pdo->prepare("SELECT COUNT(*) FROM notifications WHERE is_read=0 AND (user_id=? OR role=? OR role='all')");
    $stmt->execute([(int)$_SESSION['user_id'],get_user_role()]); return (int)$stmt->fetchColumn();
}
function get_notifications(int $limit=10): array {
    global $pdo;
    if (!is_logged_in()) return [];
    $stmt=$pdo->prepare("SELECT * FROM notifications WHERE (user_id=? OR role=? OR role='all') ORDER BY created_at DESC LIMIT ?");
    $stmt->execute([(int)$_SESSION['user_id'],get_user_role(),$limit]); return $stmt->fetchAll();
}
function mark_notification_read(int $id): void { global $pdo; $pdo->prepare("UPDATE notifications SET is_read=1 WHERE id=?")->execute([$id]); }
function mark_all_read(): void {
    global $pdo; if (!is_logged_in()) return;
    $pdo->prepare("UPDATE notifications SET is_read=1 WHERE (user_id=? OR role=? OR role='all') AND is_read=0")->execute([(int)$_SESSION['user_id'],get_user_role()]);
}
function render_notification_bell(): string {
    $count=get_unread_count(); $notifs=get_notifications(8);
    $badge=$count>0?'<span class="notif-badge">'.($count>99?'99+':$count).'</span>':'';
    $items='';
    if (empty($notifs)) { $items='<li><div class="dropdown-item-text text-center py-3" style="color:var(--text-muted);font-size:0.82rem;">No notifications</div></li>'; }
    else foreach ($notifs as $n) {
        $nicons=['success'=>'check-circle','warning'=>'exclamation-triangle','danger'=>'x-circle','info'=>'info-circle'];
        $ni=$nicons[$n['type']]??'info-circle';
        $ncolors=['success'=>'#22c55e','warning'=>'#f5a623','danger'=>'#ef4444','info'=>'#4f8ef7'];
        $nc=$ncolors[$n['type']]??'#4f8ef7';
        $items.='<li><a class="dropdown-item notif-item py-2" href="'.SITE_URL.'/notifications/read.php?id='.$n['id'].'" style="'.(!$n['is_read']?'background:rgba(255,255,255,0.03);':'').'">
            <div style="display:flex;align-items:flex-start;gap:10px;">
            <i class="bi bi-'.$ni.'" style="color:'.$nc.';font-size:0.9rem;margin-top:2px;flex-shrink:0;"></i>
            <div><div style="font-size:0.8rem;color:#fff;">'.\htmlspecialchars($n['title']).'</div>
            <div style="font-size:0.72rem;color:var(--text-muted);">'.date('d M, h:i A',strtotime($n['created_at'])).'</div></div></div></a></li>';
    }
    $markAll=is_logged_in()?'<li><a class="dropdown-item text-center py-1" href="'.SITE_URL.'/notifications/mark-all.php" style="font-size:0.72rem;color:var(--gold);">Mark all read</a></li><li><hr class="dropdown-divider my-0"></li>':'';
    return '<li class="nav-item dropdown me-1"><a class="nav-link position-relative notif-btn" href="#" role="button" data-bs-toggle="dropdown"><i class="bi bi-bell-fill fs-5"></i>'.$badge.'</a>
    <ul class="dropdown-menu dropdown-menu-end shadow" style="min-width:300px;max-height:420px;overflow-y:auto;">
    <li><div class="dropdown-item-text d-flex justify-content-between align-items-center py-2"><span style="font-weight:700;color:#fff;font-size:0.875rem;">Notifications</span></div></li>
    <li><hr class="dropdown-divider my-0"></li>'.$markAll.$items.'</ul></li>';
}

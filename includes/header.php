<?php
/**
 * includes/header.php — Ultra Premium Dashboard Header
 * Dark Navy + Gold theme for all authenticated panels
 */
if (!defined('ROOT_PATH')) die('Direct access not allowed.');

$site_name = get_setting('site_name', 'School ERP');
$site_logo = get_setting('site_logo', '');
$page_title= $page_title ?? $site_name;
$role      = get_user_role();

$nav_items = match($role) {
    'admin' => [
        ['section' => 'Main'],
        ['icon'=>'speedometer2',    'label'=>'Dashboard',       'url'=>'/admin/'],
        ['section' => 'People'],
        ['icon'=>'people-fill',     'label'=>'Students',        'url'=>'/admin/students/'],
        ['icon'=>'person-badge',    'label'=>'Teachers',        'url'=>'/admin/teachers/'],
        ['icon'=>'people',          'label'=>'Parents',         'url'=>'/admin/parents/'],
        ['section' => 'Academics'],
        ['icon'=>'mortarboard',     'label'=>'Classes',         'url'=>'/admin/classes/'],
        ['icon'=>'book',            'label'=>'Subjects',        'url'=>'/admin/subjects/'],
        ['icon'=>'clipboard2-data', 'label'=>'Exams',           'url'=>'/admin/exams/'],
        ['icon'=>'pencil-square',   'label'=>'Marks',           'url'=>'/admin/marks/'],
        ['icon'=>'award',           'label'=>'Results',         'url'=>'/admin/results/'],
        ['icon'=>'calendar-check',  'label'=>'Attendance',      'url'=>'/admin/attendance/'],
        ['section' => 'Admissions & Fees'],
        ['icon'=>'file-earmark-person','label'=>'Admissions',   'url'=>'/admin/admissions/'],
        ['icon'=>'receipt',         'label'=>'Fees',            'url'=>'/admin/fees/'],
        ['section' => 'Website'],
        ['icon'=>'layout-text-window-reverse','label'=>'Landing Page','url'=>'/admin/landing/'],
        ['icon'=>'chat-quote',      'label'=>'Testimonials',    'url'=>'/admin/testimonials/'],
        ['section' => 'System'],
        ['icon'=>'bell',            'label'=>'Notifications',   'url'=>'/admin/notifications/'],
        ['icon'=>'gear',            'label'=>'Settings',        'url'=>'/admin/settings/'],
        ['icon'=>'cash-stack',      'label'=>'Admission Fee',   'url'=>'/admin/settings/admission-fee.php'],
    ],
    'student' => [
        ['section' => 'Student Portal'],
        ['icon'=>'speedometer2',   'label'=>'Dashboard',  'url'=>'/student/'],
        ['icon'=>'person-circle',  'label'=>'My Profile', 'url'=>'/student/profile.php'],
        ['icon'=>'card-text',      'label'=>'ID Card',    'url'=>'/student/id-card.php'],
        ['icon'=>'calendar-check', 'label'=>'Attendance', 'url'=>'/student/attendance.php'],
        ['icon'=>'award',          'label'=>'Results',    'url'=>'/student/results.php'],
        ['icon'=>'receipt',        'label'=>'Fee Payment','url'=>'/student/fees.php'],
    ],
    'teacher' => [
        ['section' => 'Teacher Portal'],
        ['icon'=>'speedometer2',   'label'=>'Dashboard',     'url'=>'/teacher/'],
        ['icon'=>'calendar-check', 'label'=>'Attendance',    'url'=>'/teacher/attendance.php'],
        ['icon'=>'pencil-square',  'label'=>'Enter Marks',   'url'=>'/teacher/marks.php'],
        ['icon'=>'award',          'label'=>'Report Cards',  'url'=>'/teacher/report-cards.php'],
    ],
    'parent' => [
        ['section' => 'Parent Portal'],
        ['icon'=>'speedometer2',  'label'=>'Dashboard', 'url'=>'/parent/'],
        ['icon'=>'person-circle', 'label'=>'My Child',  'url'=>'/parent/child.php'],
        ['icon'=>'receipt',       'label'=>'Fee Status', 'url'=>'/parent/fees.php'],
    ],
    default => [],
};

$current_path = $_SERVER['REQUEST_URI'] ?? '';
$unread = get_unread_count();
$notifs = get_notifications(8);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="description" content="<?= sanitize(get_setting('meta_description','')) ?>">
  <title><?= sanitize($page_title) ?> — <?= sanitize($site_name) ?></title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600;700;800&family=DM+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
  <link href="<?= ASSETS_URL ?>/css/style.css" rel="stylesheet">
  <?php if (!empty($extra_head)) echo $extra_head; ?>
</head>
<body>

<!-- ── Sidebar overlay (click to close) ── -->
<div id="sidebarOverlay" onclick="closeSidebar()"
     style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.5);z-index:1030;cursor:pointer;"></div>

<!-- ── Navbar ── -->
<nav class="app-navbar">
  <!-- Hamburger -->
  <button type="button" class="hamburger-btn" id="hamburgerBtn"
          onclick="toggleSidebar()">
    <i class="bi bi-list fs-5" id="hamburgerIcon"></i>
  </button>

  <!-- Brand -->
  <a href="<?= SITE_URL ?>/<?= $role ?>/" class="d-flex align-items-center gap-2 text-decoration-none" style="flex-shrink:0;">
    <?php if ($site_logo): ?>
      <img src="<?= get_upload_url($site_logo) ?>" height="32" class="rounded-2" alt="Logo">
    <?php else: ?>
      <div class="brand-icon">🎓</div>
    <?php endif; ?>
    <span class="brand d-none d-sm-flex" style="font-size:1rem;color:var(--text-main);font-weight:700;">
      <?= sanitize($site_name) ?>
    </span>
  </a>

  <!-- Spacer -->
  <div class="flex-grow-1"></div>

  <!-- Right actions -->
  <div class="d-flex align-items-center gap-2">

    <!-- Notification bell -->
    <div class="dropdown">
      <button class="notif-btn" data-bs-toggle="dropdown">
        <i class="bi bi-bell fs-5"></i>
        <?php if ($unread > 0): ?>
        <span class="notif-badge"><?= $unread > 99 ? '99+' : $unread ?></span>
        <?php endif; ?>
      </button>
      <div class="dropdown-menu dropdown-menu-end" style="min-width:300px;max-height:420px;overflow-y:auto;">
        <div class="dropdown-item-text d-flex justify-content-between align-items-center py-2">
          <span style="font-weight:700;color:var(--text-main);font-size:0.875rem;">Notifications</span>
          <?php if ($unread > 0): ?>
          <a href="<?= SITE_URL ?>/notifications/mark-all.php"
             style="font-size:0.72rem;color:var(--primary);">Mark all read</a>
          <?php endif; ?>
        </div>
        <div class="dropdown-divider"></div>
        <?php if (empty($notifs)): ?>
        <div class="dropdown-item-text text-center py-3" style="color:var(--text-muted);font-size:0.82rem;">
          <i class="bi bi-bell-slash d-block mb-1 fs-4"></i>No notifications
        </div>
        <?php else: foreach ($notifs as $n):
          $nicons=['success'=>'check-circle','warning'=>'exclamation-triangle','danger'=>'x-circle','info'=>'info-circle'];
          $ncolors=['success'=>'#16a34a','warning'=>'#d97706','danger'=>'#dc2626','info'=>'#1a6dcc'];
          $ni = $nicons[$n['type']] ?? 'info-circle';
          $nc = $ncolors[$n['type']] ?? '#4f8ef7';
        ?>
        <a class="dropdown-item notif-item py-2"
           href="<?= SITE_URL ?>/notifications/read.php?id=<?= $n['id'] ?>"
           style="<?= !$n['is_read'] ? 'background:var(--primary-light);' : '' ?>">
          <div style="display:flex;align-items:flex-start;gap:10px;">
            <i class="bi bi-<?= $ni ?> mt-1 flex-shrink-0" style="color:<?= $nc ?>;font-size:0.9rem;"></i>
            <div>
              <div style="font-size:0.8rem;color:var(--text-main);font-weight:<?= !$n['is_read']?'600':'400'?>;"><?= sanitize($n['title']) ?></div>
              <div style="font-size:0.72rem;color:var(--text-muted);"><?= date('d M, h:i A',strtotime($n['created_at'])) ?></div>
            </div>
          </div>
        </a>
        <?php endforeach; endif; ?>
      </div>
    </div>

    <!-- User menu -->
    <div class="dropdown">
      <div class="user-avatar" data-bs-toggle="dropdown" style="cursor:pointer;">
        <?= strtoupper(substr($_SESSION['user_name'] ?? 'U', 0, 1)) ?>
      </div>
      <ul class="dropdown-menu dropdown-menu-end">
        <li>
          <div class="dropdown-item-text">
            <div style="font-weight:700;font-size:0.875rem;color:var(--text-main);"><?= sanitize($_SESSION['user_name'] ?? '') ?></div>
            <div style="font-size:0.72rem;color:var(--primary);text-transform:capitalize;"><?= $role ?></div>
          </div>
        </li>
        <li><div class="dropdown-divider"></div></li>
        <?php if ($role === 'admin'): ?>
        <li><a class="dropdown-item" href="<?= SITE_URL ?>/admin/settings/">
          <i class="bi bi-gear me-2"></i>Settings
        </a></li>
        <?php endif; ?>
        <li><a class="dropdown-item danger" href="<?= SITE_URL ?>/auth/logout.php">
          <i class="bi bi-box-arrow-right me-2"></i>Logout
        </a></li>
      </ul>
    </div>
  </div>
</nav>

<!-- ── Sidebar ── -->
<aside class="app-sidebar" id="appSidebar">

  <!-- Mobile header with close button -->
  <div class="sidebar-mobile-header">
    <span><?= sanitize($site_name) ?></span>
    <button type="button" onclick="closeSidebar()">✕</button>
  </div>

  <nav style="overflow-y:auto;flex:1;padding-bottom:20px;">
    <?php
    $prev_was_section = false;
    foreach ($nav_items as $item):
      if (isset($item['section'])):
    ?>
    <div class="sidebar-section <?= $prev_was_section ? 'mt-2' : '' ?>"><?= $item['section'] ?></div>
    <?php
        $prev_was_section = true;
        continue;
      endif;
      $is_active = (
        $item['url'] === '/admin/' || $item['url'] === '/student/' ||
        $item['url'] === '/teacher/' || $item['url'] === '/parent/'
      )
        ? (rtrim(parse_url($current_path, PHP_URL_PATH), '/') === rtrim($item['url'], '/'))
        : str_contains($current_path, $item['url']);
      $prev_was_section = false;
    ?>
    <a href="<?= SITE_URL . $item['url'] ?>"
       class="sidebar-link <?= $is_active ? 'active' : '' ?>">
      <i class="bi bi-<?= $item['icon'] ?>"></i>
      <span><?= $item['label'] ?></span>
    </a>
    <?php endforeach; ?>
    <div class="mt-3 mx-2" style="border-top:1px solid rgba(255,255,255,0.05);padding-top:12px;">
      <a href="<?= SITE_URL ?>/auth/logout.php" class="sidebar-link danger" onclick="closeSidebar()">
        <i class="bi bi-box-arrow-right"></i><span>Logout</span>
      </a>
    </div>
  </nav>

</aside>

<script>
// Global sidebar functions — inline onclick, no event listeners needed
function toggleSidebar() {
  var s = document.getElementById('appSidebar');
  var o = document.getElementById('sidebarOverlay');
  var i = document.getElementById('hamburgerIcon');
  if (!s) return;
  if (s.classList.contains('sidebar-open')) {
    s.classList.remove('sidebar-open');
    o.style.display = 'none';
    i.className = 'bi bi-list fs-5';
  } else {
    s.classList.add('sidebar-open');
    o.style.display = 'block';
    i.className = 'bi bi-x-lg fs-5';
  }
}
function closeSidebar() {
  var s = document.getElementById('appSidebar');
  var o = document.getElementById('sidebarOverlay');
  var i = document.getElementById('hamburgerIcon');
  if (s) s.classList.remove('sidebar-open');
  if (o) o.style.display = 'none';
  if (i) i.className = 'bi bi-list fs-5';
}
</script>

<!-- ── Main Content ── -->
<main class="main-content" id="mainContent">
  <div class="container-fluid px-3 px-lg-4">

    <!-- Page header row -->
    <div class="d-flex align-items-center justify-content-between mb-3 flex-wrap gap-2">
      <div>
        <h5 style="font-weight:700;color:var(--text-main);margin:0;">
          <?= sanitize($page_title) ?>
        </h5>
        <?php if (!empty($breadcrumb)): ?>
        <nav aria-label="breadcrumb">
          <ol class="breadcrumb small mb-0 mt-1">
            <?php foreach ($breadcrumb as $bc): ?>
            <li class="breadcrumb-item <?= isset($bc['active']) ? 'active' : '' ?>">
              <?php if (isset($bc['url'])): ?>
                <a href="<?= $bc['url'] ?>"><?= sanitize($bc['label']) ?></a>
              <?php else: ?>
                <?= sanitize($bc['label']) ?>
              <?php endif; ?>
            </li>
            <?php endforeach; ?>
          </ol>
        </nav>
        <?php endif; ?>
      </div>
      <?php if (!empty($page_action)) echo $page_action; ?>
    </div>

    <!-- Flash message -->
    <?= get_flash() ?>

<?php
/**
 * School ERP — Core Helper Functions  v2.0
 */
if (!defined('ROOT_PATH')) die('Direct access not allowed.');

function sanitize(string $data): string { return htmlspecialchars(trim($data), ENT_QUOTES|ENT_HTML5, 'UTF-8'); }
function sanitize_int(mixed $val): int { return (int)filter_var($val, FILTER_SANITIZE_NUMBER_INT); }
function sanitize_float(mixed $val): float { return (float)filter_var($val, FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION); }
function sanitize_email(string $email): string { return filter_var(trim($email), FILTER_SANITIZE_EMAIL); }
function validate_email(string $email): bool { return filter_var($email, FILTER_VALIDATE_EMAIL) !== false; }

function csrf_token(): string {
    if (empty($_SESSION['csrf_token'])) $_SESSION['csrf_token'] = bin2hex(random_bytes(CSRF_TOKEN_LENGTH));
    return $_SESSION['csrf_token'];
}
function csrf_field(): string { return '<input type="hidden" name="csrf_token" value="'.csrf_token().'">'; }
function verify_csrf(string $token): bool { return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token); }
function csrf_protect(): void {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $token = $_POST['csrf_token'] ?? '';
        if (!verify_csrf($token)) { http_response_code(403); die('CSRF token mismatch. <a href="javascript:history.back()">Go back</a>'); }
    }
}

function is_logged_in(): bool { return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']); }
function get_user_role(): string { return $_SESSION['user_role'] ?? ''; }
function redirect(string $url): never { header('Location: '.$url); exit; }

function generate_student_id(): string {
    global $pdo; $year = date('Y');
    $stmt = $pdo->query("SELECT COUNT(*) FROM students WHERE student_id LIKE 'SCH{$year}-%'");
    $next = (int)$stmt->fetchColumn() + 1;
    return 'SCH'.$year.'-'.str_pad($next, 3, '0', STR_PAD_LEFT);
}
function generate_teacher_id(): string {
    global $pdo; $year = date('Y');
    $stmt = $pdo->query("SELECT COUNT(*) FROM teachers");
    $next = (int)$stmt->fetchColumn() + 1;
    return 'TCH'.$year.'-'.str_pad($next, 3, '0', STR_PAD_LEFT);
}
function generate_application_id(): string {
    global $pdo; $year = date('Y');
    $stmt = $pdo->query("SELECT COUNT(*) FROM admissions");
    $next = (int)$stmt->fetchColumn() + 1;
    return 'APP'.$year.'-'.str_pad($next, 4, '0', STR_PAD_LEFT);
}
function generate_invoice_no(): string {
    global $pdo; $year = date('Y');
    $stmt = $pdo->query("SELECT COUNT(*) FROM fees");
    $next = (int)$stmt->fetchColumn() + 1;
    return 'INV'.$year.'-'.str_pad($next, 5, '0', STR_PAD_LEFT);
}

function calculate_grade(float $pct): string {
    return match(true) {
        $pct>=90=>'A+',$pct>=80=>'A',$pct>=70=>'B+',$pct>=60=>'B',
        $pct>=50=>'C+',$pct>=40=>'C',$pct>=33=>'D',default=>'F'
    };
}
function get_grade_color(string $grade): string {
    return match($grade){'A+','A'=>'success','B+','B'=>'primary','C+','C'=>'info','D'=>'warning',default=>'danger'};
}
function get_pass_percentage(): float { return (float)get_setting('pass_percentage',(string)PASS_PERCENTAGE); }

function upload_file(array $file, string $dir, array $allowed_types=[]): string|false {
    if ($file['error'] !== UPLOAD_ERR_OK || $file['size'] > MAX_FILE_SIZE) return false;
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $types = empty($allowed_types) ? ALLOWED_IMAGES : $allowed_types;
    if (!in_array($ext, $types, true)) return false;
    if (in_array($ext,['jpg','jpeg','png','gif','webp']) && @getimagesize($file['tmp_name'])===false) return false;
    $upload_dir = UPLOADS_PATH.rtrim($dir,'/').'/';
    if (!is_dir($upload_dir)) mkdir($upload_dir, 0755, true);
    $filename = bin2hex(random_bytes(8)).'_'.time().'.'.$ext;
    $dest = $upload_dir.$filename;
    if (move_uploaded_file($file['tmp_name'],$dest)) return $dir.'/'.$filename;
    return false;
}
function delete_upload(string $path): void {
    $full = UPLOADS_PATH.ltrim($path,'/');
    if (file_exists($full)) unlink($full);
}
function get_upload_url(string $path): string { return UPLOADS_URL.'/'.ltrim($path,'/'); }

function set_flash(string $type, string $msg): void { $_SESSION['flash'] = ['type'=>$type,'msg'=>$msg]; }
function get_flash(): string {
    if (!isset($_SESSION['flash'])) return '';
    $f = $_SESSION['flash']; unset($_SESSION['flash']);
    $cls = match($f['type']){'success'=>'alert-success','error'=>'alert-danger','warning'=>'alert-warning',default=>'alert-info'};
    return '<div class="alert '.$cls.' alert-dismissible fade show" role="alert">'.sanitize($f['msg']).'<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>';
}

function generate_otp(): string { return str_pad((string)random_int(100000,999999),6,'0',STR_PAD_LEFT); }
function save_otp(string $email, string $otp): void {
    global $pdo;
    $pdo->prepare("UPDATE otps SET is_used=1 WHERE email=?")->execute([$email]);
    $expires = date('Y-m-d H:i:s', time()+OTP_EXPIRY_MINUTES*60);
    $pdo->prepare("INSERT INTO otps (email,otp,expires_at) VALUES (?,?,?)")->execute([$email,$otp,$expires]);
}
function verify_otp(string $email, string $otp): bool {
    global $pdo;
    $stmt = $pdo->prepare("SELECT id FROM otps WHERE email=? AND otp=? AND is_used=0 AND expires_at>NOW() LIMIT 1");
    $stmt->execute([$email,$otp]);
    $row = $stmt->fetch();
    if ($row) { $pdo->prepare("UPDATE otps SET is_used=1 WHERE id=?")->execute([$row['id']]); return true; }
    return false;
}

function paginate(int $total, int $per_page, int $current): array {
    $pages   = (int)ceil($total/$per_page);
    $current = max(1,min($current,$pages));
    return ['total'=>$total,'per_page'=>$per_page,'current'=>$current,'pages'=>$pages,'offset'=>($current-1)*$per_page];
}
function pagination_links(array $p, string $base_url): string {
    if ($p['pages']<=1) return '';
    $sep = str_contains($base_url,'?') ? '&' : '?';
    $html='<nav><ul class="pagination pagination-sm flex-wrap">';
    for ($i=1;$i<=$p['pages'];$i++) {
        $active=$i===$p['current']?'active':'';
        $html.='<li class="page-item '.$active.'">'.'<a class="page-link" href="'.$base_url.$sep.'page='.$i.'">'.$i.'</a></li>';
    }
    return $html.'</ul></nav>';
}

function format_date(string|null $date, string $format='d M Y'): string {
    if (empty($date)||$date==='0000-00-00') return '—';
    return date($format, strtotime($date));
}

function get_dashboard_stats(): array {
    global $pdo;
    return [
        'total_students'  => (int)$pdo->query("SELECT COUNT(*) FROM students WHERE status='active'")->fetchColumn(),
        'total_teachers'  => (int)$pdo->query("SELECT COUNT(*) FROM teachers WHERE status='active'")->fetchColumn(),
        'total_parents'   => (int)$pdo->query("SELECT COUNT(*) FROM parents WHERE status='active'")->fetchColumn(),
        'total_admissions'=> (int)$pdo->query("SELECT COUNT(*) FROM admissions WHERE status='pending'")->fetchColumn(),
        'total_fees_due'  => (float)$pdo->query("SELECT COALESCE(SUM(amount),0) FROM fees WHERE status='pending'")->fetchColumn(),
        'total_fees_paid' => (float)$pdo->query("SELECT COALESCE(SUM(amount),0) FROM fees WHERE status='paid'")->fetchColumn(),
    ];
}

function currency_format(float $amount): string { return get_setting('currency_symbol','₹').number_format($amount,2); }

function get_classes(): array { global $pdo; return $pdo->query("SELECT * FROM classes ORDER BY id")->fetchAll(); }
function get_sections_by_class(int $class_id): array {
    global $pdo; $s=$pdo->prepare("SELECT * FROM sections WHERE class_id=? ORDER BY name");
    $s->execute([$class_id]); return $s->fetchAll();
}
function get_subjects(int $class_id=0): array {
    global $pdo;
    if ($class_id>0) { $s=$pdo->prepare("SELECT * FROM subjects WHERE class_id=? OR class_id IS NULL ORDER BY name"); $s->execute([$class_id]); }
    else $s=$pdo->query("SELECT * FROM subjects ORDER BY name");
    return $s->fetchAll();
}
function get_student_by_user_id(int $user_id): array|false {
    global $pdo;
    $s=$pdo->prepare("SELECT s.*,c.name as class_name,sec.name as section_name FROM students s LEFT JOIN classes c ON s.class_id=c.id LEFT JOIN sections sec ON s.section_id=sec.id WHERE s.user_id=? LIMIT 1");
    $s->execute([$user_id]); return $s->fetch();
}
function get_teacher_by_user_id(int $user_id): array|false {
    global $pdo;
    $s=$pdo->prepare("SELECT t.*,c.name as class_name,sub.name as subject_name FROM teachers t LEFT JOIN classes c ON t.class_id=c.id LEFT JOIN subjects sub ON t.subject_id=sub.id WHERE t.user_id=? LIMIT 1");
    $s->execute([$user_id]); return $s->fetch();
}
function get_parent_by_user_id(int $user_id): array|false {
    global $pdo; $s=$pdo->prepare("SELECT * FROM parents WHERE user_id=? LIMIT 1");
    $s->execute([$user_id]); return $s->fetch();
}
function attendance_summary(int $student_id, string $month=''): array {
    global $pdo;
    $where='student_id=?'; $params=[$student_id];
    if ($month) { $where.=" AND DATE_FORMAT(date,'%Y-%m')=?"; $params[]=$month; }
    $s=$pdo->prepare("SELECT COUNT(*) as total,SUM(status='Present') as present,SUM(status='Absent') as absent,SUM(status='Late') as late FROM attendance WHERE $where");
    $s->execute($params); $r=$s->fetch();
    $r['percentage'] = $r['total']>0 ? round(($r['present']/$r['total'])*100,1) : 0;
    return $r;
}

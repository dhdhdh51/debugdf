<?php
require_once dirname(__DIR__,2).'/config/config.php';
require_once INCLUDES_PATH.'auth_check.php';
auth_guard('admin');
$search=sanitize($_GET['q']??'');$class_id=sanitize_int($_GET['class_id']??0);$status=sanitize($_GET['status']??'');
$where=['1=1'];$params=[];
if($search){$where[]="(s.name LIKE ? OR s.student_id LIKE ? OR s.email LIKE ?)";$like="%{$search}%";$params=array_merge($params,[$like,$like,$like]);}
if($class_id){$where[]="s.class_id=?";$params[]=$class_id;}
if($status){$where[]="s.status=?";$params[]=$status;}
$stmt=$pdo->prepare("SELECT s.student_id,s.name,s.email,s.phone,s.gender,s.dob,s.blood_group,c.name as class_name,sec.name as section_name,s.admission_date,s.status FROM students s LEFT JOIN classes c ON s.class_id=c.id LEFT JOIN sections sec ON s.section_id=sec.id WHERE ".implode(' AND ',$where)." ORDER BY s.created_at DESC");
$stmt->execute($params);$rows=$stmt->fetchAll();
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=students_'.date('Y-m-d').'.csv');
$out=fopen('php://output','w');
fputcsv($out,['Student ID','Name','Email','Phone','Gender','DOB','Blood Group','Class','Section','Admission Date','Status']);
foreach($rows as $r)fputcsv($out,[$r['student_id'],$r['name'],$r['email']??'',$r['phone']??'',$r['gender']??'',$r['dob']??'',$r['blood_group']??'',$r['class_name']??'',$r['section_name']??'',$r['admission_date']??'',$r['status']]);
fclose($out);exit;

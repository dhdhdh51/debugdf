<?php
/**
 * School ERP — Pure PHP SMTP Mailer (no Composer)
 */
if (!defined('ROOT_PATH')) die('Direct access not allowed.');

class SchoolMailer {
    private string $host, $username, $password, $encryption, $fromEmail, $fromName;
    private int $port;
    private array $recipients=[], $errors=[];
    private string $subject='', $body='', $altBody='';

    public function __construct() {
        $this->host       = get_setting('smtp_host','smtp.gmail.com');
        $this->port       = (int)get_setting('smtp_port','587');
        $this->username   = get_setting('smtp_user','');
        $this->password   = get_setting('smtp_pass','');
        $this->encryption = strtolower(get_setting('smtp_encryption','tls'));
        $this->fromEmail  = get_setting('smtp_from','noreply@school.com');
        $this->fromName   = get_setting('smtp_from_name',get_setting('site_name','School ERP'));
    }
    public function addAddress(string $email, string $name=''): void { $this->recipients[]=['email'=>$email,'name'=>$name]; }
    public function setSubject(string $subject): void { $this->subject=$subject; }
    public function setBody(string $html, string $plain=''): void { $this->body=$html; $this->altBody=$plain?:strip_tags($html); }
    public function send(): bool {
        if (empty($this->username)||empty($this->host)) { $this->errors[]='SMTP not configured.'; return false; }
        foreach ($this->recipients as $to) { if (!$this->sendOne($to['email'],$to['name'])) return false; }
        return true;
    }
    public function getErrors(): array { return $this->errors; }

    private function sendOne(string $toEmail, string $toName): bool {
        $socket=$this->connect(); if ($socket===false) return false;
        try {
            $boundary=md5(uniqid((string)time(),true));
            $message=$this->buildMessage($toEmail,$toName,$boundary);
            $this->sendCommand($socket,"EHLO ".($_SERVER['HTTP_HOST']??'localhost'));
            $this->readResponse($socket);
            if ($this->encryption==='tls'&&$this->port!==465) {
                $this->sendCommand($socket,"STARTTLS"); $this->readResponse($socket);
                stream_socket_enable_crypto($socket,true,STREAM_CRYPTO_METHOD_TLSv1_2_CLIENT);
                $this->sendCommand($socket,"EHLO ".($_SERVER['HTTP_HOST']??'localhost'));
                $this->readResponse($socket);
            }
            $this->sendCommand($socket,"AUTH LOGIN"); $this->readResponse($socket);
            $this->sendCommand($socket,base64_encode($this->username)); $this->readResponse($socket);
            $this->sendCommand($socket,base64_encode($this->password));
            $auth=$this->readResponse($socket);
            if (!str_starts_with($auth,'2')) throw new RuntimeException('Auth failed: '.$auth);
            $this->sendCommand($socket,"MAIL FROM:<{$this->fromEmail}>"); $this->readResponse($socket);
            $this->sendCommand($socket,"RCPT TO:<{$toEmail}>"); $this->readResponse($socket);
            $this->sendCommand($socket,"DATA"); $this->readResponse($socket);
            fwrite($socket,$message."\r\n.\r\n"); $dataResp=$this->readResponse($socket);
            if (!str_starts_with($dataResp,'2')) throw new RuntimeException('DATA error: '.$dataResp);
            $this->sendCommand($socket,"QUIT"); fclose($socket); return true;
        } catch (RuntimeException $e) {
            $this->errors[]=$e->getMessage(); if (is_resource($socket)) fclose($socket);
            error_log('[SchoolMailer] '.$e->getMessage()); return false;
        }
    }
    private function connect(): mixed {
        $context=stream_context_create(['ssl'=>['verify_peer'=>false,'verify_peer_name'=>false,'allow_self_signed'=>true]]);
        $target=($this->encryption==='ssl'||$this->port===465)?"ssl://{$this->host}:{$this->port}":"tcp://{$this->host}:{$this->port}";
        $socket=@stream_socket_client($target,$errno,$errstr,15,STREAM_CLIENT_CONNECT,$context);
        if ($socket===false) { $this->errors[]="SMTP Connection failed: {$errstr} ({$errno})"; return false; }
        stream_set_timeout($socket,15); $this->readResponse($socket); return $socket;
    }
    private function sendCommand(mixed $socket, string $cmd): void { fwrite($socket,$cmd."\r\n"); }
    private function readResponse(mixed $socket): string {
        $response='';
        while ($line=fgets($socket,515)) { $response.=$line; if (strlen($line)<4||$line[3]===' ') break; }
        return trim($response);
    }
    private function buildMessage(string $toEmail, string $toName, string $boundary): string {
        $toH=$toName?'=?UTF-8?B?'.base64_encode($toName).'?= <'.$toEmail.'>'  :$toEmail;
        $frH=$this->fromName?'=?UTF-8?B?'.base64_encode($this->fromName).'?= <'.$this->fromEmail.'>'  :$this->fromEmail;
        $sub='=?UTF-8?B?'.base64_encode($this->subject).'?=';
        $headers="From: {$frH}\r\nTo: {$toH}\r\nSubject: {$sub}\r\nMIME-Version: 1.0\r\nContent-Type: multipart/alternative; boundary=\"{$boundary}\"\r\nDate: ".date('r')."\r\n";
        $body="--{$boundary}\r\nContent-Type: text/plain; charset=UTF-8\r\nContent-Transfer-Encoding: base64\r\n\r\n".chunk_split(base64_encode($this->altBody))."\r\n--{$boundary}\r\nContent-Type: text/html; charset=UTF-8\r\nContent-Transfer-Encoding: base64\r\n\r\n".chunk_split(base64_encode($this->body))."\r\n--{$boundary}--";
        return $headers."\r\n".$body;
    }

    public static function emailTemplate(string $title, string $content, string $footer=''): string {
        $site=get_setting('site_name','School ERP'); $year=date('Y');
        $ft=$footer?:get_setting('footer_text',"© {$year} {$site}");
        return "<!DOCTYPE html><html><head><meta charset=\"UTF-8\"><style>body{font-family:Arial,sans-serif;background:#0a0f1e;margin:0;padding:20px}.wrapper{max-width:600px;margin:0 auto;background:#0f1729;border:1px solid rgba(245,166,35,0.2);border-radius:16px;overflow:hidden}.header{background:linear-gradient(135deg,#f5a623,#c47f0a);padding:20px;text-align:center;color:#0a0f1e}.header h2{margin:0;font-size:22px;font-weight:800}.body{padding:28px;color:#e8eaf6;font-size:15px;line-height:1.7}.btn{display:inline-block;padding:12px 28px;background:linear-gradient(135deg,#f5a623,#c47f0a);color:#0a0f1e;text-decoration:none;border-radius:10px;margin:10px 0;font-weight:700}.otp-box{font-size:32px;letter-spacing:8px;font-weight:800;color:#f5a623;text-align:center;padding:20px;background:rgba(245,166,35,0.1);border:1px solid rgba(245,166,35,0.2);border-radius:12px;margin:20px 0}.footer{background:rgba(255,255,255,0.03);padding:16px;text-align:center;font-size:12px;color:rgba(255,255,255,0.3)}</style></head><body><div class=\"wrapper\"><div class=\"header\"><h2>{$site}</h2></div><div class=\"body\"><h3>{$title}</h3>{$content}</div><div class=\"footer\">{$ft}</div></div></body></html>";
    }

    public static function sendOTP(string $email, string $name, string $otp): bool {
        $m=new self(); $m->addAddress($email,$name); $m->setSubject('Password Reset OTP - '.get_setting('site_name','School ERP'));
        $m->setBody(self::emailTemplate('Password Reset OTP',"<p>Hi <strong>".htmlspecialchars($name)."</strong>,</p><p>Your OTP is:</p><div class=\"otp-box\">{$otp}</div><p>Valid for ".OTP_EXPIRY_MINUTES." minutes.</p>"));
        return $m->send();
    }
    public static function sendAdmissionConfirmation(string $email, string $name, string $appId): bool {
        $m=new self(); $m->addAddress($email,$name); $m->setSubject('Application Received - '.get_setting('site_name','School ERP'));
        $m->setBody(self::emailTemplate('Application Received',"<p>Dear ".htmlspecialchars($name).",</p><p>Your application ID is: <strong>{$appId}</strong></p><p>We will review and notify you.</p>"));
        return $m->send();
    }
    public static function sendAdmissionStatus(string $email, string $name, string $status, string $remarks=''): bool {
        $m=new self(); $m->addAddress($email,$name); $m->setSubject("Admission ".strtoupper($status)." - ".get_setting('site_name','School ERP'));
        $col=$status==='approved'?'#22c55e':'#ef4444';
        $m->setBody(self::emailTemplate("Admission ".ucfirst($status),"<p>Dear ".htmlspecialchars($name).",</p><p>Your admission has been <span style=\"color:{$col};font-weight:700;\">".strtoupper($status)."</span>.</p>".($remarks?"<p>Remarks: ".htmlspecialchars($remarks)."</p>":"")));
        return $m->send();
    }
    public static function sendWelcome(string $email, string $name, string $role, string $password): bool {
        $m=new self(); $m->addAddress($email,$name); $m->setSubject('Welcome to '.get_setting('site_name','School ERP'));
        $loginUrl=SITE_URL.'/auth/login.php';
        $m->setBody(self::emailTemplate('Account Created',"<p>Dear ".htmlspecialchars($name).",</p><p><strong>Role:</strong> ".ucfirst($role)."<br><strong>Email:</strong> ".htmlspecialchars($email)."<br><strong>Password:</strong> ".htmlspecialchars($password)."</p><a href=\"{$loginUrl}\" class=\"btn\">Login Now</a>"));
        return $m->send();
    }
    public static function sendFeeInvoice(string $email, string $name, string $invoiceNo, float $amount, string $dueDate): bool {
        $m=new self(); $m->addAddress($email,$name); $m->setSubject("Fee Invoice {$invoiceNo}");
        $sym=get_setting('currency_symbol','₹');
        $m->setBody(self::emailTemplate('Fee Invoice',"<p>Dear ".htmlspecialchars($name).",</p><table style=\"width:100%;border-collapse:collapse;margin:15px 0\"><tr><td style=\"padding:8px;border:1px solid rgba(255,255,255,0.1)\">Invoice</td><td>{$invoiceNo}</td></tr><tr><td style=\"padding:8px;border:1px solid rgba(255,255,255,0.1)\">Amount</td><td>{$sym}".number_format($amount,2)."</td></tr><tr><td style=\"padding:8px;border:1px solid rgba(255,255,255,0.1)\">Due</td><td>".htmlspecialchars($dueDate)."</td></tr></table>"));
        return $m->send();
    }
    public static function sendPaymentConfirmation(string $email, string $name, string $txnId, float $amount): bool {
        $m=new self(); $m->addAddress($email,$name); $m->setSubject('Payment Successful');
        $sym=get_setting('currency_symbol','₹');
        $m->setBody(self::emailTemplate('Payment Successful',"<p>Dear ".htmlspecialchars($name).",</p><p>Payment of <strong>{$sym}".number_format($amount,2)."</strong> received.</p><p>Txn ID: {$txnId}</p>"));
        return $m->send();
    }
    public static function sendResultPublished(string $email, string $name, string $examName): bool {
        $m=new self(); $m->addAddress($email,$name); $m->setSubject("Result Published - {$examName}");
        $m->setBody(self::emailTemplate('Result Published',"<p>Dear ".htmlspecialchars($name).",</p><p>Results for <strong>".htmlspecialchars($examName)."</strong> are now available.</p><a href=\"".SITE_URL."/student/results.php\" class=\"btn\">View Results</a>"));
        return $m->send();
    }
    public static function sendAbsenceAlert(string $parentEmail, string $parentName, string $studentName, string $date): bool {
        $m=new self(); $m->addAddress($parentEmail,$parentName); $m->setSubject('Absence Alert - '.get_setting('site_name','School ERP'));
        $m->setBody(self::emailTemplate('Absence Alert',"<p>Dear ".htmlspecialchars($parentName).",</p><p><strong>".htmlspecialchars($studentName)."</strong> was marked <span style=\"color:#ef4444;font-weight:700;\">ABSENT</span> on ".htmlspecialchars($date).".</p>"));
        return $m->send();
    }
    public static function sendCustomNotification(string $email, string $name, string $subject, string $message): bool {
        $m=new self(); $m->addAddress($email,$name); $m->setSubject($subject);
        $m->setBody(self::emailTemplate($subject,"<p>Dear ".htmlspecialchars($name).",</p>".nl2br(htmlspecialchars($message))));
        return $m->send();
    }
}

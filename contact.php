<?php
// contact.php
// UTF-8 headers
header('Content-Type: application/json; charset=utf-8');

// Only allow POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  http_response_code(405);
  echo json_encode(['ok' => false, 'msg' => 'Method not allowed']);
  exit;
}

// Basic rate-limiting by time (optional)
session_start();
if (isset($_SESSION['last_submit']) && time() - $_SESSION['last_submit'] < 10) {
  http_response_code(429);
  echo json_encode(['ok' => false, 'msg' => 'Please wait a few seconds before sending again.']);
  exit;
}
$_SESSION['last_submit'] = time();

// Honeypot (spam trap)
if (!empty($_POST['company'])) {
  http_response_code(200);
  echo json_encode(['ok' => true, 'msg' => 'Sent']); // pretend success
  exit;
}

// Collect & sanitize
$name    = trim($_POST['name'] ?? '');
$email   = trim($_POST['email'] ?? '');
$message = trim($_POST['message'] ?? '');

if (mb_strlen($name) < 2 || mb_strlen($name) > 100) {
  http_response_code(422);
  echo json_encode(['ok' => false, 'msg' => 'الاسم غير صالح.']);
  exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL) || mb_strlen($email) > 150) {
  http_response_code(422);
  echo json_encode(['ok' => false, 'msg' => 'البريد الإلكتروني غير صالح.']);
  exit;
}

if (mb_strlen($message) < 10 || mb_strlen($message) > 4000) {
  http_response_code(422);
  echo json_encode(['ok' => false, 'msg' => 'الرسالة قصيرة أو طويلة جدًا.']);
  exit;
}

// Compose mail
$to = 'data28448802@gmail.com'; // your target inbox
$subject = 'رسالة جديدة من نموذج الموقع';
$body  = "الاسم: {$name}\n";
$body .= "البريد: {$email}\n";
$body .= "-------------------------\n";
$body .= "{$message}\n";

// From header - ideally use a domain-based address
$fromAddress = 'no-reply@' . ($_SERVER['HTTP_HOST'] ?? 'website.local');
$headers = [];
$headers[] = 'MIME-Version: 1.0';
$headers[] = 'Content-type: text/plain; charset=UTF-8';
$headers[] = 'From: ' . mb_encode_mimeheader('Website Contact', 'UTF-8') . " <{$fromAddress}>";
$headers[] = 'Reply-To: ' . $email;
$headers[] = 'X-Mailer: PHP/' . phpversion();

// Send
$ok = @mail($to, '=?UTF-8?B?' . base64_encode($subject) . '?=', $body, implode("\r\n", $headers));

if ($ok) {
  echo json_encode(['ok' => true, 'msg' => 'تم الإرسال بنجاح. شكراً لتواصلك!']);
} else {
  http_response_code(500);
  echo json_encode(['ok' => false, 'msg' => 'تعذر الإرسال من الخادم. جرّب لاحقاً أو استخدم SMTP.']);
}
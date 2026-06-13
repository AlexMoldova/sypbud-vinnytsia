<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=utf-8");

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    http_response_code(405);
    echo json_encode(["status" => "error", "message" => "Тільки POST метод"]);
    exit;
}

// Збираємо дані
$name = htmlspecialchars(trim($_POST["Імʼя"] ?? $_POST["Ім'я"] ?? "Не вказано"));
$phone = htmlspecialchars(trim($_POST["Телефон"] ?? "Не вказано"));
$service = htmlspecialchars(trim($_POST["Послуга"] ?? "Не обрано"));
$comment = htmlspecialchars(trim($_POST["Коментар"] ?? "—"));

if (empty($phone) || $phone === "Не вказано") {
    http_response_code(400);
    echo json_encode(["status" => "error", "message" => "Будь ласка, вкажіть телефон"]);
    exit;
}

// ─── 1. Telegram ──────────────────────────────────────────────
$tg_token = "8937588040:AAHY4DMwmo_ZEKC36Bdm8AVUb2_KZbecXMw";
$tg_chat_id = "5676571556";

$tg_text = "🔥 <b>НОВА ЗАЯВКА</b> — СИПБУД·ВІННИЦЯ\n\n"
    . "👤 <b>Ім'я:</b> $name\n"
    . "📞 <b>Телефон:</b> $phone\n"
    . "🛻 <b>Послуга:</b> $service\n"
    . "📝 <b>Коментар:</b> $comment\n\n"
    . "⏰ " . date("d.m.Y H:i");

$tg_sent = false;
$ch = curl_init("https://api.telegram.org/bot{$tg_token}/sendMessage");
curl_setopt_array($ch, [
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => http_build_query([
        "chat_id" => $tg_chat_id,
        "text" => $tg_text,
        "parse_mode" => "HTML",
    ]),
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT => 10,
    CURLOPT_SSL_VERIFYPEER => false,
]);
$tg_resp = curl_exec($ch);
$tg_http = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($tg_http === 200) {
    $tg_sent = true;
}

// ─── 2. Email (mail) ──────────────────────────────────────────
$to = "bespaluj2075092@gmail.com";
$subject = "=?UTF-8?B?" . base64_encode("Заявка СИПБУД·ВІННИЦЯ: $name — $phone") . "?=";

$msg = "Нова заявка з сайту СИПБУД·ВІННИЦЯ\n";
$msg .= "=====================================\n\n";
$msg .= "Ім'я:     $name\n";
$msg .= "Телефон:  $phone\n";
$msg .= "Послуга:  $service\n";
$msg .= "Коментар: $comment\n\n";
$msg .= "--- Відправлено [" . date("d.m.Y H:i") . "] ---\n";

$headers = "From: СИПБУД <no-reply@sypbud-vinnytsia.vn.ua>\r\n";
$headers .= "Content-Type: text/plain; charset=UTF-8\r\n";
$headers .= "Content-Transfer-Encoding: 8bit\r\n";

$mail_sent = @mail($to, $subject, $msg, $headers);

// ─── Результат ────────────────────────────────────────────────
if ($tg_sent) {
    echo json_encode(["status" => "success", "message" => "Заявку відправлено! Чекайте на дзвінок."]);
} else {
    // Логуємо
    $log = date("Y-m-d H:i:s") . " | FAIL | $name | $phone | TG HTTP $tg_http\n";
    @file_put_contents(__DIR__ . "/leads_error.log", $log, FILE_APPEND);

    http_response_code(500);
    echo json_encode(["status" => "error", "message" => "Помилка. Зателефонуйте: +380672267917"]);
}

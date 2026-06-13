<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=utf-8");

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    http_response_code(405);
    echo json_encode(["status" => "error", "message" => "Тільки POST метод"]);
    exit;
}

$name = htmlspecialchars(trim($_POST["Імʼя"] ?? $_POST["Ім'я"] ?? "Не вказано"));
$phone = htmlspecialchars(trim($_POST["Телефон"] ?? "Не вказано"));
$service = htmlspecialchars(trim($_POST["Послуга"] ?? "Не обрано"));
$comment = htmlspecialchars(trim($_POST["Коментар"] ?? "—"));

if (empty($phone) || $phone === "Не вказано") {
    http_response_code(400);
    echo json_encode(["status" => "error", "message" => "Будь ласка, вкажіть телефон"]);
    exit;
}

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

$sent = @mail($to, $subject, $msg, $headers);

if ($sent) {
    echo json_encode(["status" => "success", "message" => "Заявку відправлено! Чекайте на дзвінок."]);
} else {
    $log = date("Y-m-d H:i:s") . " | MAIL FAILED | $name | $phone | $service\n";
    @file_put_contents(__DIR__ . "/leads_error.log", $log, FILE_APPEND);

    http_response_code(500);
    echo json_encode(["status" => "error", "message" => "Помилка відправки. Зателефонуйте: +380672267917"]);
}

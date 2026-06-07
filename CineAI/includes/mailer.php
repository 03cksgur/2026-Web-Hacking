<?php
// includes/mailer.php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . '/PHPMailer/Exception.php';
require_once __DIR__ . '/PHPMailer/PHPMailer.php';
require_once __DIR__ . '/PHPMailer/SMTP.php';

function sendVerificationEmail($to, $token) {
    $configList = include __DIR__ . '/../config/mail.php';
    if (!$configList) return false;

    // Build the verify URL based on the current server
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http";
    $host = $_SERVER['HTTP_HOST'];
    $link = $protocol . "://" . $host . "/verify_email.php?token=" . urlencode($token);

    $mail = new PHPMailer(true);

    try {
        $mail->isSMTP();
        $mail->Host       = $configList['smtp_host'];
        $mail->SMTPAuth   = true;
        $mail->Username   = $configList['smtp_user'];
        $mail->Password   = $configList['smtp_pass'];
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS; // SSL
        $mail->Port       = $configList['smtp_port'];
        $mail->CharSet    = 'UTF-8';

        $mail->setFrom($configList['from_email'], $configList['from_name']);
        $mail->addAddress($to);

        $mail->isHTML(true);
        $mail->Subject = 'CineAI - 회원가입 이메일 인증을 완료해주세요';
        
        $body = "<h2>CineAI 회원가입을 환영합니다!</h2>";
        $body .= "<p>아래 링크를 클릭하여 이메일 인증을 완료하시면 관리자 승인 단계를 거치게 됩니다.</p>";
        $body .= "<p><a href='{$link}' style='padding:10px 20px; background:#4CC9F0; color:#fff; text-decoration:none; border-radius:5px;'>이메일 인증하기</a></p>";
        $body .= "<p>버튼이 작동하지 않으면 아래 주소를 복사하여 브라우저에 붙여넣어 주세요.</p>";
        $body .= "<p>{$link}</p>";

        $mail->Body = $body;

        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("Message could not be sent. Mailer Error: {$mail->ErrorInfo}");
        return false;
    }
}

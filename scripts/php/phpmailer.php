<?php
require_once __DIR__ . '/vendor/autoload.php';// Adjust the path to your autoload.php

if (class_exists('PHPMailer\PHPMailer\PHPMailer')) {
    echo "PHPMailer is installed.";
} else {
    echo "PHPMailer is not installed.";
}
?>
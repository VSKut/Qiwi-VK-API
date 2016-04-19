<?php

require_once('../src/QiwiVK.class.php');

use VSKut\Qiwi_VK_API\QiwiVK;

$phone = $_GET['phone'];
$password = $_GET['password'];

$qiwi = new QiwiVK($phone, $password);

$oauth_token = $qiwi->getOauthToken();

if ($oauth_token) {
    echo 'oAuth token was received.<br/>';
    echo '<form method="get" action="/examples/token.php">';
    echo '<input type="text" name="phone" value="'.$phone.'"><br/>';
    echo '<input type="text" name="password" value="'.$password.'"><br/>';
    echo '<input type="text" name="token" value="'.$oauth_token.'"><br/>';
    echo '<input type="text" name="sms" placeholder="Code from sms"><br/>';
    echo '<input type="submit" value="Get Access token">';
    echo '</form>';
} else {
    echo '<pre>';
    var_dump($qiwi->getError());
    echo '</pre>';
}
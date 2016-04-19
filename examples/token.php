<?php

require_once('../src/QiwiVK.class.php');

use VSKut\Qiwi_VK_API\QiwiVK;

$phone = $_GET['phone'];
$password = $_GET['password'];

$qiwi = new QiwiVK($phone, $password);

$qiwi->setOauthToken($_GET['token']);
$access_token = $qiwi->getAccessToken($_GET['sms']);

if ($access_token) {
    echo 'Access token was received.<br/>';
    echo '<form method="get" action="/examples/check.php">';
    echo '<input type="text" name="phone" value="'.$phone.'"><br/>';
    echo '<input type="text" name="password" value="'.$password.'"><br/>';
    echo '<input type="text" name="token" value="'.$access_token.'"><br/>';
    echo '<input type="submit" value="Check Access token">';
    echo '</form>';
} else {
    echo '<pre>';
    var_dump($qiwi->getError());
    echo '</pre>';
}
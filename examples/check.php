<?php

require_once('../src/QiwiVK.class.php');

use VSKut\Qiwi_VK_API\QiwiVK;

$phone = $_GET['phone'];
$password = $_GET['password'];

$qiwi = new QiwiVK($phone, $password);

$qiwi->setAccessToken($_GET['token']);
$result = $qiwi->checkAccessToken();

if ($result) {
    echo 'Access token is valid.<br/>';
    echo '<form method="get" action="/examples/transactions.php">';
    echo '<input type="text" name="phone" value="'.$phone.'"><br/>';
    echo '<input type="text" name="password" value="'.$password.'"><br/>';
    echo '<input type="text" name="token" value="'.$_GET['token'].'"><br/>';
    echo '<input type="submit" value="Get transactions array">';
    echo '</form>';
} else {
    echo '<pre>';
    var_dump($qiwi->getError());
    echo '</pre>';
}
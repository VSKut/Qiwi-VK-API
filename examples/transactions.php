<?php

require_once('../src/QiwiVK.class.php');

use VSKut\Qiwi_VK_API\QiwiVK;

$phone = $_GET['phone'];
$password = $_GET['password'];

$qiwi = new QiwiVK($phone, $password);

$qiwi->setAccessToken($_GET['token']);
$result = $qiwi->checkAccessToken();

if ($result) {
    echo '<pre>';
    var_dump($qiwi->getTransactions());
    echo '</pre>';

/*    echo '<pre>';
    var_dump($qiwi->checkTransaction(300, 'text'));
    echo '</pre>';*/

} else {
    echo '<pre>';
    var_dump($qiwi->getError());
    echo '</pre>';
}
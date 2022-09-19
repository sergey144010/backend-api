<?php

require_once ("./vendor/autoload.php");

$data = getenv('DATA');
if (! $data) {
    $data = '/data';
}

(new \App\Application())->run($data);
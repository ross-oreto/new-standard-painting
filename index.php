<?php
require 'vendor/autoload.php';
$f3 = \Base::instance();
$f3->route('GET /','Alex\Nsp\App->index');
$f3->route('GET /schedule','Alex\Nsp\App->schedule');
$f3->route('GET /about','Alex\Nsp\App->about');
$f3->route('GET /contact','Alex\Nsp\App->contact');
$f3->run();
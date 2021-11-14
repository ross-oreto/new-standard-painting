<?php
require 'vendor/autoload.php';
$f3 = \Base::instance();
$f3->route('GET /','Alex\Nsp\App->index');
$f3->route('GET /schedule','Alex\Nsp\App->schedule');
$f3->route('GET /about','Alex\Nsp\App->about');
$f3->route('GET /pricing','Alex\Nsp\App->pricing');

$f3->set('ONERROR','Alex\Nsp\ErrorHandler::handle');

$f3->run();
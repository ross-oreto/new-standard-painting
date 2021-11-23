<?php

use Alex\Nsp\Controllers\App;
use Oreto\F3Willow\Willow;

require '../vendor/autoload.php';
$f3 = \Base::instance();
Willow::equip($f3, [App::routes()])->run();
<?php

use Alex\Nsp\App;
use Alex\Nsp\Willow;

require '../vendor/autoload.php';
$f3 = \Base::instance();
Willow::run($f3, [App::routes()]);
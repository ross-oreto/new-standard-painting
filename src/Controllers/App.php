<?php

namespace Alex\Nsp\Controllers;

use Oreto\F3Willow\Routing\Routes;
use Oreto\F3Willow\Willow;

class App extends Willow {
    static function routes(): Routes {
        return Routes::create(self::class)
            ->GET("home", "/")->handler('index')
            ->GET("schedule", "/schedule")->handler('schedule')
            ->build();
    }

    function schedule($f3) {
        echo $this->render("schedule");
    }
}
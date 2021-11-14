<?php

namespace Alex\Nsp;

class App {
    function render(string $view, $f3): string {
        $f3->set('content',"views/$view.htm");
        return \Template::instance()->render("views/template.htm");
    }

    function index($f3) {
        echo $this->render("home", $f3);
    }

    function schedule($f3) {
        echo $this->render("schedule", $f3);
    }

    function about($f3) {
        echo $this->render("about", $f3);
    }

    function pricing($f3) {
        echo $this->render("pricing", $f3);
    }
}
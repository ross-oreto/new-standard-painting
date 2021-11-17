import {$} from "./jquery.js"

$(function () {
    $(".navbar-burger").on('click',function() {
        // Toggle the "is-active" class on both the "navbar-burger" and the "navbar-menu"
        $(".navbar-burger").toggleClass("is-active");
        $(".navbar-menu").toggleClass("is-active");
    });
});
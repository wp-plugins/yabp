/*
    Plugin Name: Yet Another bol.com Plugin
    Version: 1.1
    Author: Mitchel Troost
*/

var $j = jQuery.noConflict();

$j(document).ready(function () {
    $j('a[rel*=external]').click( function() {
        window.open(this.href);
        return false;
    });
});
<?php

add_action('wp_update_nav_menu', 'daiquiri_update_nav_menu');

function daiquiri_update_nav_menu() {
    // check if the wp-content/menus directory exists
    mkdir(WP_CONTENT_DIR . '/menus/', 0755);

    // loop over menus and save the html in the wp-content/menus directory
    foreach (get_terms( 'nav_menu') as $menu) {
        $html = wp_nav_menu(array(
            'menu' => $menu->name,
            'echo' => false,
            'container' => false,
            'items_wrap' => '%3$s'
        ));
        $filename = WP_CONTENT_DIR . '/menus/' . $menu->name . '.html';
        $fh = fopen($filename, 'w') or die("can't open file");
        fwrite($fh, $html);
        fclose($fh);
    }
}

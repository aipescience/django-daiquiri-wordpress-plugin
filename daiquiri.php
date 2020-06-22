<?php
/*
  Plugin Name: Daiquiri framework integration
  Description: Daiquiri framework integration
  Author: Jochen Klar <jklar@aip.de>
  Version: 0.1
  Text Domain: Daiquiri framework integration
 */

/*
 * Helper function to log debug messages
 */

if (!function_exists('daiquiri_log')) {
    function daiquiri_log($message)
    {
        if (DAIQUIRI_DEBUG) {
            error_log($message);
        }
    }
}

/*
 * Include daiquiri shortcodes
 */

require_once('daiquiri_navigation.php');

/*
 * disable admin bar
 */

$show_admin_bar = false;

/*
 * Automatiacally login the user which is logged in into daiquiri right now.
 */

add_action('init', 'daiquiri_auto_login');

function daiquiri_auto_login()
{
    if (isset($_COOKIE['sessionid']) && !is_user_logged_in()) {
        // check if WordPress is below Daiquiri
//        if (strpos(get_option('siteurl'), DAIQUIRI_URL) === false) {
 //           echo '<h1>Error with daiquiri plugin</h1><p>Wordpress URL is not below Daiquiri URL. Please set the correct DAIQUIRI_URL in wp-config.php.</p>';
//            die(0);
//        }

        // check which user is logged in into daiquiri right now
        $url = rtrim(DAIQUIRI_URL, '/') . '/accounts/profile.json/';

        require_once('HTTP/Request2.php');
        $req = new HTTP_Request2($url);
        $req->setMethod('GET');
        $req->addCookie("sessionid", $_COOKIE["sessionid"]);
        $req->setConfig(array(
            'ssl_verify_peer' => false, // we trust the certificate here
            'connect_timeout' => 2,
            'timeout' => 3
        ));

        try {
            $response = $req->send();
            $status = $response->getStatus();
            $body = $response->getBody();

            daiquiri_log($url . ' returned ' . $status);
        } catch (HTTP_Request2_Exception $e) {
            echo '<h1>Error with daiquiri plugin</h1><p>Error with HTTP request.</p>';
            throw $e;
        }

        if ($status == 200) {
            // decode the json to get the daiquiri user
            $daiquiri_user = json_decode($body);

            // get the wordpress user with the same username and log him/her in (or not)
            $wordpress_user = get_user_by('login', $daiquiri_user->username);

            if ($wordpress_user) {
                // log him/her in
                wp_set_current_user($wordpress_user->ID, $wordpress_user->user_login);
                wp_set_auth_cookie($wordpress_user->ID);
                do_action('wp_login', $wordpress_user->user_login);

                daiquiri_log('"' . $wordpress_user->user_login . '" logged in');
            } else {
                echo '<h1>Error with daiquiri plugin</h1><p>User not found.</p>';
                error_log('Daiquiri user "' . $daiquiri_user->username . '" does not exist in WordPress');
                die(0);
            }
        }
    }
}

/*
 * Override the build in authentification of wordpress
 */

add_action('wp_authenticate', 'daiquiri_authenticate', 1, 2);

function daiquiri_authenticate($username, $password) {
    require_once('./wp-includes/registration.php');

    // get the login url
    $url = rtrim(DAIQUIRI_URL, '/') . '/accounts/login/';

    // append the redirect to query parameter
    if ($_GET["redirect_to"]) {
        $url .= '?next=' . $_GET["redirect_to"];
    }

    // just do the redirect
    wp_redirect($url);
    exit();
}

/*
 * Hide the personal profile options.
 */

add_action('profile_personal_options', 'daiquiri_hide_start');

function daiquiri_hide_start() {
    echo '<div style="display: none;"><!-- the following fields are hidden since a change to these values would be overwritten at the next login. -->';
}

add_action('show_user_profile', 'daiquiri_hide_end');

function daiquiri_hide_end() {
    echo '</div><!-- hidden -->';
}

/*
 * Log out of daiquiri when logging out of wordpress.
 */

add_action('wp_logout', 'daiquiri_logout');

function daiquiri_logout() {
    $url = rtrim(DAIQUIRI_URL, '/') . '/accounts/logout/';
    wp_redirect($url);
    exit();
}

/*
 * Disable emails send on email address change
 */

add_filter( 'send_password_change_email', '__return_false');

<?php
/*
Plugin Name: Verifiable Credential Login
Description: Login with Verifiable Credentials.
*/

include 'phpqrcode/src/qrlib.php';
include 'controller.php';

// If this file was called directly, abort.
if (!defined('WPINC')) {
    die;
};

/**
 * Controller object that handles calls to the ACA-Py agent admin API.
 */
$controller = new Controller();

// Don't check if credential is verified if timed out
if (!isset($_GET['timeout'])) {
    add_action('login_enqueue_scripts', 'verify_enqueue_script');
}
/**
 * Link polling for verified script to login page
 */
function verify_enqueue_script() {
    wp_enqueue_script('check-verify', plugins_url('/poll.js', __FILE__));
}


add_filter( 'login_message', 'the_login_message' );
/**
 * Display message about verifiable credential login.
 * Display timed out message if timed out.
 */
function the_login_message( $message ) {

    if (isset($_GET['timeout'] )) {
        $message = "
        <div style='
        line-height: 2;'>
        <h3>Verifiable credential login has timed out.</h3>
        <div style='
            display: flex;
            justify-content: center;
            align-items: center;'>
        <button style='
            position: center;
            font-size: 16px;
            padding: 6px 15px;'
            onClick=\"window.location = 'wp-login.php';\">
            Try again</button>
        </div>
        </div>";
    } else {
        $message = "
        <h3>Login with a verifiable credential by scanning the
        QR code with your mobile wallet or sign in below.</h3>";
    }

    return $message;
}


// Don't display QR code if timed out
if (!isset($_GET['timeout'])) {
    add_action( 'login_enqueue_scripts', 'vc_logo');
}
/**
 * Create a new presentation request and get its presentation exchange id. 
 * Generate a QR code based on the presentation exchange id. 
 * Set the presentation exchange id in the login page's HTML.
 */
function vc_logo() { 
    global $controller;
    $pres_ex_id = $controller->create_pres_req();
    
    if (!$pres_ex_id) {
        echo "Failed to create presentation request.";
        exit(1);
    }

    $get_url = ControllerInit::$webhook_url . '/webhooks/pres_req/' . $pres_ex_id;
    QRcode::png($get_url, 'qr.png');

    ?>
    <style type="text/css">
        body.login div#login h1 a {
            background-image: url("qr.png");
            background-size: 220px 220px;
            height: 220px;
            width: 220px;
        }
    </style>
    <input type="hidden" id="pres_ex_id" value=<?=$pres_ex_id?>></input>
    <?php

}


add_action('login_init', 'authenticate_page');
/**
 * Check if pres_ex_id has been set in the URL for /wp-login.php. 
 * (For example, /wp-login.php?pres_ex_id=0).
 * Authenticate the user based on the presentation if presentation exchange id is set.
 */
function authenticate_page() {

    if ( isset($_GET['pres_ex_id'] ) ) {
        $pres_ex_id = $_GET['pres_ex_id'];
        authenticate($pres_ex_id);
    }
    
}


/**
 * Log user into Wordpress based on whether the proof (identified by presentation exchange id), is verified. 
 * Get user information from the verifiable proof.
 * @param string $pres_ex_id
 */
function authenticate($pres_ex_id) {
    global $controller;

    $result = $controller->get_record($pres_ex_id);
    if (!$result) {
        echo "Getting presentation record failed.";
        exit(1);
    }

    if ($result["state"] == 'verified') {
        $name = $result["presentation"]["requested_proof"]["revealed_attrs"]["0_name_uuid"]["raw"];
    } else {
        exit(1);
    }

    $controller->delete_pres($pres_ex_id); // Delete presentation record so that it cannot be reused or copied maliciously

    // Make user email from name revealed in proof
    $name = strtolower(str_replace(' ', '', $name));
    $email = $name . '@matrixgroup.net';
    $user = get_user_by('email', $email);

    if (!$user) {
        $password = hash('whirlpool', base64_encode(random_bytes(1024)) . $email . time());
        $user = wp_create_user($name, $password, $email);
        $user = $user = get_user_by('email', $email);
    }

    if (!is_wp_error( $user )) {
        wp_clear_auth_cookie();
        wp_set_current_user( $user->ID);
        wp_set_auth_cookie( $user->ID);

        wp_safe_redirect(admin_url());
    }
}


add_action( 'rest_api_init', 'register_routes');
/**
 * Register new RESTful API routes for plugin to handle sending the connectionless proof
 * and polling from JavaScript frontend.
 */
function register_routes() {

    // Called by the mobile agent by scanning QR code
    register_rest_route(
        'vc-api',
        '/webhooks/pres_req/(?P<pres_ex_id>[^/]*)',
        array(
            'methods' => 'GET',
            'callback' => 'webhooks_pres_req',
            'permission_callback' => '__return_true'
        )
    );

    // Called by the ACA-Py agent when state changes
    register_rest_route(
        'vc-api',
        '/topic/(?P<topic>[^/]*)',
        array(
            'methods' => 'POST',
            'callback' => 'webhooks',
            'permission_callback' => '__return_true'
        )
    );

    // Called by frontend JavaScript
    register_rest_route(
        'backend',
        '/verified',
        array(
            'methods' => 'POST',
            'callback' => 'verified',
            'permission_callback' => '__return_true'
        )
    );
}


/**
 * RESTful API endpoint called by mobile wallet.
 * Redirects to a new URL that contains the connectionless proof request.
 * $data must contain the presentation exchange ID.
 * @param array $data
 */
function webhooks_pres_req( $data ) {
    global $controller;
    $oob_arr = $controller->wh_pres_req($data['pres_ex_id']);

    if (!$oob_arr) {
        echo "Creating the connectionless array failed.";
        exit(1);
    }

    $jsonStr = json_encode($oob_arr, true);
    $jsonB64 = base64_encode($jsonStr);
    $full_encoded = ControllerInit::$webhook_url . '/webhooks/?m=' . $jsonB64;

    //error_log($full_encoded, './wp-errors.log');
    header('Location: '. $full_encoded);
    exit(0);
}


/**
 * RESTful API endpoint called by the ACA-Py agent.
 * Checks whether a presentation record has been received
 * from another agent and verifies that record if it has been received.
 * $data must contain the presentation exchange ID and the state.
 * @param array $data
 */
function webhooks( $data ) {
    global $controller;

    $state = $data['state'];
    $pres_ex_id = $data['presentation_exchange_id'];

    if ($state === 'presentation_received') {
        $status = $controller->verify_pres($pres_ex_id);
        if (!$status) {
            echo "Verifying the presentation failed.";
            exit(1);
        }
    }

}


/**
 * RESTful API endpoint called by JavaScript frontend.
 * Checks whether the presentation has been verified.
 * Presentation exchange id is passed via POST request.
 */
function verified() {
    global $controller;

    $pres_ex_id = file_get_contents('php://input');
    $result = $controller->get_record($pres_ex_id);
    if (!$result) {
        echo "Getting presentation record failed.";
        exit(1);
    }

    return json_encode($result, true);
}

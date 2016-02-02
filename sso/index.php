<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Main login page for SSO with ILP
 * This is a copy of login/index.php with modifications to support wantsurl
 * as a form parameter.
 *
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require('../../../config.php');

require_once('../../../login/lib.php');

// Try to prevent searching for sites that allow sign-up.
if (!isset($CFG->additionalhtmlhead)) {
    $CFG->additionalhtmlhead = '';
}
$CFG->additionalhtmlhead .= '<meta name="robots" content="noindex" />';

redirect_if_major_upgrade_required();

$testsession = optional_param('testsession', 0, PARAM_INT); // Test session works properly.
$cancel      = optional_param('cancel', 0, PARAM_BOOL);      // Redirect to frontpage, needed for loginhttps.

if ($cancel) {
    redirect(new moodle_url('/'));
}

// HTTPS is required in this page when $CFG->loginhttps enabled.
$PAGE->https_required();

$context = context_system::instance();
$PAGE->set_url("$CFG->httpswwwroot/login/index.php");
$PAGE->set_context($context);
$PAGE->set_pagelayout('login');

// Initialize variables.
$errormsg = '';
$errorcode = 0;

// Login page requested session test.
if ($testsession) {
    if ($testsession == $USER->id) {
        if (isset($SESSION->wantsurl)) {
            $urltogo = $SESSION->wantsurl;
        } else {
            $urltogo = $CFG->wwwroot.'/';
        }
        unset($SESSION->wantsurl);
        redirect($urltogo);
    } else {
        // TODO: try to find out what is the exact reason why sessions do not work.
        $errormsg = get_string("cookiesnotenabled");
        $errorcode = 1;
    }
}

// Check for timed out sessions.
if (!empty($SESSION->has_timed_out)) {
    $session_has_timed_out = true;
    unset($SESSION->has_timed_out);
} else {
    $session_has_timed_out = false;
}

// Auth plugins may override these - SSO anyone?
$frm  = false;
$user = false;

$authsequence = get_enabled_auth_plugins(true); // Auths, in sequence.
foreach ($authsequence as $authname) {
    $authplugin = get_auth_plugin($authname);
    $authplugin->loginpage_hook();
}

// Define variables used in page.
$site = get_site();

$loginsite = get_string("loginsite");
$PAGE->navbar->add($loginsite);

if ($user !== false or $frm !== false or $errormsg !== '') {
    // Some auth plugin already supplied full user, fake form data or prevented user login with error message.

} else if (!empty($SESSION->wantsurl) && file_exists($CFG->dirroot.'/login/weblinkauth.php')) {
    // Handles the case of another Moodle site linking into a page on this site.
    // TODO: move weblink into own auth plugin.
    include($CFG->dirroot.'/login/weblinkauth.php');
    if (function_exists('weblink_auth')) {
        $user = weblink_auth($SESSION->wantsurl);
    }
    if ($user) {
        $frm->username = $user->username;
    } else {
        $frm = data_submitted();
    }

} else {
    $frm = data_submitted();
}

// Variables needed by ILP to redirect to the correct target location after login.
$SESSION->wantsurl = $frm->wantsurl;
$SESSION->failedloginurl = $frm->failedloginurl;

// Check if the user has actually submitted login data to use.

if ($frm and isset($frm->username)) { // Login WITH cookies.

    $frm->username = trim(core_text::strtolower($frm->username));

    if (is_enabled_auth('none') ) {
        if ($frm->username !== clean_param($frm->username, PARAM_USERNAME)) {
            $errormsg = get_string('username').': '.get_string("invalidusername");
            $errorcode = 2;
            $user = null;
        }
    }

    if ($user) {
        // User already supplied by aut plugin prelogin hook.
    } else if (($frm->username == 'guest') and empty($CFG->guestloginbutton)) {
        $user = false; // Can't log in as guest if guest button is disabled.
        $frm = false;
    } else {
        if (empty($errormsg)) {
            $user = authenticate_user_login($frm->username, $frm->password);
        }
    }

    // Intercept 'restored' users to provide them with info & reset password.
    if (!$user and $frm and is_restored_user($frm->username)) {
        $PAGE->set_title(get_string('restoredaccount'));
        $PAGE->set_heading($site->fullname);
        echo $OUTPUT->header();
        echo $OUTPUT->heading(get_string('restoredaccount'));
        echo $OUTPUT->box(get_string('restoredaccountinfo'), 'generalbox boxaligncenter');
        require_once('restored_password_form.php'); // Use our "supplanter" login_forgot_password_form. MDL-20846.
        $form = new login_forgot_password_form('forgot_password.php', array('username' => $frm->username));
        $form->display();
        echo $OUTPUT->footer();
        die;
    }

    if ($user) {

        // Language setup.
        if (isguestuser($user)) {
            // No predefined language for guests - use existing session or default site lang.
            unset($user->lang);

        } else if (!empty($user->lang)) {
            // Unset previous session language - use user preference instead.
            unset($SESSION->lang);
        }

        if (empty($user->confirmed)) {       // This account was never confirmed
            $PAGE->set_title(get_string("mustconfirm"));
            $PAGE->set_heading($site->fullname);
            echo $OUTPUT->header();
            echo $OUTPUT->heading(get_string("mustconfirm"));
            echo $OUTPUT->box(get_string("emailconfirmsent", "", $user->email), "generalbox boxaligncenter");
            echo $OUTPUT->footer();
            die;
        }

        // Let's get them all set up.
        complete_user_login($user);

        // Sets the username cookie.
        if (!empty($CFG->nolastloggedin)) {
            /*
            * Do not store last logged in user in cookie
            * auth plugins can temporarily override this from loginpage_hook()
            * do not save $CFG->nolastloggedin in database!
            */

        } else if (empty($CFG->rememberusername) or ($CFG->rememberusername == 2 and empty($frm->rememberusername))) {
            // No permanent cookies, delete old one if exists.
            set_moodle_cookie('');

        } else {
            set_moodle_cookie($USER->username);
        }
        $urltogo = core_login_get_return_url();

    // Check if user password has expired.
    // Currently supported only for ldap-authentication module.
        $userauth = get_auth_plugin($USER->auth);
        if (!empty($userauth->config->expiration) and $userauth->config->expiration == 1) {
            if ($userauth->can_change_password()) {
                $passwordchangeurl = $userauth->change_password_url();
                if (!$passwordchangeurl) {
                    $passwordchangeurl = $CFG->httpswwwroot.'/login/change_password.php';
                }
            } else {
                $passwordchangeurl = $CFG->httpswwwroot.'/login/change_password.php';
            }
            $days2expire = $userauth->password_expire($USER->username);
            $PAGE->set_title("$site->fullname: $loginsite");
            $PAGE->set_heading("$site->fullname");
            if (intval($days2expire) > 0 && intval($days2expire) < intval($userauth->config->expiration_warning)) {
                echo $OUTPUT->header();
                echo $OUTPUT->confirm(get_string('auth_passwordwillexpire', 'auth', $days2expire), $passwordchangeurl, $urltogo);
                echo $OUTPUT->footer();
                exit;
            } elseif (intval($days2expire) < 0 ) {
                echo $OUTPUT->header();
                echo $OUTPUT->confirm(get_string('auth_passwordisexpired', 'auth'), $passwordchangeurl, $urltogo);
                echo $OUTPUT->footer();
                exit;
            }
        }

        // Discard any errors before the last redirect.
        unset($SESSION->loginerrormsg);

        // Test the session actually works by redirecting to self.
        $SESSION->wantsurl = $urltogo;
        redirect(new moodle_url(get_login_url(), array('testsession' => $USER->id)));

    } else {
        if (empty($errormsg)) {
            $errormsg = get_string("invalidlogin");
            $errorcode = 3;
        }
    }
}

// Detect problems with timedout sessions.
if ($session_has_timed_out and !data_submitted()) {
    $errormsg = get_string('sessionerroruser', 'error');
    $errorcode = 4;
}

// First, let's remember where the user was trying to get to before they got here.

if (empty($SESSION->wantsurl)) {
    $SESSION->wantsurl = (array_key_exists('HTTP_REFERER', $_SERVER) &&
                          $_SERVER["HTTP_REFERER"] != $CFG->wwwroot &&
                          $_SERVER["HTTP_REFERER"] != $CFG->wwwroot.'/' &&
                          $_SERVER["HTTP_REFERER"] != $CFG->httpswwwroot.'/login/' &&
                          strpos($_SERVER["HTTP_REFERER"], $CFG->httpswwwroot.'/login/?') !== 0 &&
                          strpos($_SERVER["HTTP_REFERER"], $CFG->httpswwwroot.'/login/index.php') !== 0) // There might be some extra params such as ?lang=.
    ? $_SERVER["HTTP_REFERER"] : null;
}

// Redirect to alternative login URL if needed.
if (!empty($CFG->alternateloginurl)) {
    $loginurl = $CFG->alternateloginurl;

    if (strpos($SESSION->wantsurl, $loginurl) === 0) {
        // We do not want to return to alternate url.
        $SESSION->wantsurl = null;
    }

    if ($errorcode) {
        if (strpos($loginurl, '?') === false) {
            $loginurl .= '?';
        } else {
            $loginurl .= '&';
        }
        $loginurl .= 'errorcode='.$errorcode;
    }

    redirect($loginurl);
}

// Make sure we really are on the https page when https login required.
$PAGE->verify_https_required();

// Generate the login page with forms.

if (!isset($frm) or !is_object($frm)) {
    $frm = new stdClass();
}

if (empty($frm->username) && $authsequence[0] != 'shibboleth') {  // See bug 5184.
    if (!empty($_GET["username"])) {
        $frm->username = clean_param($_GET["username"], PARAM_RAW); // We do not want data from _POST here.
    } else {
        $frm->username = get_moodle_cookie();
    }

    $frm->password = "";
}

if (!empty($frm->username)) {
    $focus = "password";
} else {
    $focus = "username";
}

if (!empty($CFG->registerauth) or is_enabled_auth('none') or !empty($CFG->auth_instructions)) {
    $show_instructions = true;
} else {
    $show_instructions = false;
}

$potentialidps = array();
foreach ($authsequence as $authname) {
    $authplugin = get_auth_plugin($authname);
    $potentialidps = array_merge($potentialidps, $authplugin->loginpage_idp_list($SESSION->wantsurl));
}

if (!empty($SESSION->loginerrormsg)) {
    // We had some errors before redirect, show them now.
    $errormsg = $SESSION->loginerrormsg;
    unset($SESSION->loginerrormsg);

} else if ($testsession) {
    // No need to redirect here.
    unset($SESSION->loginerrormsg);

} else if ($errormsg or !empty($frm->password)) {
    // We must redirect after every password submission.
    if ($errormsg) {
        $SESSION->loginerrormsg = $errormsg;
    }
    // Credentials error; redirect back to ILP for login.
    redirect($frm->failedloginurl);
}

$PAGE->set_title("$site->fullname: $loginsite");
$PAGE->set_heading("$site->fullname");

echo $OUTPUT->header();

if (isloggedin() and !isguestuser()) {
    // Prevent logging when already logged in, we do not want them to relogin by accident because sesskey would be changed.
    echo $OUTPUT->box_start();
    $logout = new single_button(new moodle_url($CFG->httpswwwroot.'/login/logout.php', array('sesskey'=>sesskey(), 'loginpage' => 1)), get_string('logout'), 'post');
    $continue = new single_button(new moodle_url($CFG->httpswwwroot.'/login/index.php?redirect=2', array('cancel' => 1)), get_string('cancel'), 'get');
    echo $OUTPUT->confirm(get_string('alreadyloggedin', 'error', fullname($USER)), $logout, $continue);
    echo $OUTPUT->box_end();
} else {
    include("index_form.html");
    if ($errormsg) {
        $PAGE->requires->js_init_call('M.util.focus_login_error', null, true);
    } else if (!empty($CFG->loginpageautofocus)) {
        // Focus username or password.
        $PAGE->requires->js_init_call('M.util.focus_login_form', null, true);
    }
}

echo $OUTPUT->footer();

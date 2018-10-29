<?php
/***************************************************************************
*                                                                          *
*   (c) 2004 Vladimir V. Kalynyak, Alexey V. Vinokurov, Ilya M. Shalnev    *
*                                                                          *
* This  is  commercial  software,  only  users  who have purchased a valid *
* license  and  accept  to the terms of the  License Agreement can install *
* and use this program.                                                    *
*                                                                          *
****************************************************************************
* PLEASE READ THE FULL TEXT  OF THE SOFTWARE  LICENSE   AGREEMENT  IN  THE *
* "copyright.txt" FILE PROVIDED WITH THIS DISTRIBUTION PACKAGE.            *
****************************************************************************/

use Tygh\Exceptions\DeveloperException;
use Tygh\Registry;


if (!defined('BOOTSTRAP')) { die('Access denied'); }

if (defined('HTTPS')) {
    if (!isset($_SESSION['display_ssl_tooltip'])) {
        $_SESSION['display_ssl_tooltip'] = 'Y';
    } elseif ($_SESSION['display_ssl_tooltip'] == 'Y') {
        $_SESSION['display_ssl_tooltip'] = 'N';
    }
}
if (!empty($_REQUEST['wid'])) {
    if ($_REQUEST['wid'] == 'all') {
        unset($_SESSION['wid']);
    } else {
        $_SESSION['wid'] = $_REQUEST['wid'];
    }
}
if (empty($_SESSION['hide_anouncement'])) {
    $anouncement = db_get_field("SELECT text FROM ?:anouncements WHERE start_timestamp <= ?i AND end_timestamp + 86399 >= ?i ORDER BY priority ASC", TIME, TIME);
    if (!empty($anouncement)) {
        Registry::get('view')->assign('anouncement', $anouncement);
    }
}
// Registry::get('view')->assign('mobile_page_id', fn_crc32(implode('_', $_REQUEST)));
Registry::get('view')->assign('company_phone', preg_replace('/[^0-9+]/', '', Registry::get('settings.Company.company_phone')));

$antibot_rules = fn_get_schema('security', 'antibot_rules');

$runtime_controller = Registry::get('runtime.controller');
$runtime_mode = Registry::get('runtime.mode');

if (isset($antibot_rules[$runtime_controller][$runtime_mode])) {
    $rule = $antibot_rules[$runtime_controller][$runtime_mode];

    if (isset($rule['condition']) && is_callable($rule['condition'])) {
        $condition_result = call_user_func($rule['condition'], $_REQUEST);

        if (!$condition_result) {
            return;
        }
    }

    if (isset($rule['request_method'])
        && fn_strtolower($_SERVER['REQUEST_METHOD']) !== fn_strtolower($rule['request_method'])
    ) {
        return;
    }

    if (empty($rule['verification_scenario'])) {
        throw new DeveloperException('Antibot validation rule must contain "verification_scenario" parameter.');
    }

    if (false === fn_image_verification($rule['verification_scenario'], $_REQUEST)) {
        if (isset($rule['save_post_data'])) {
            call_user_func_array('fn_save_post_data', (array)$rule['save_post_data']);
        }

        if (!empty($rule['terminate_process'])) {
            exit;
        }

        if (isset($rule['rewrite_controller_status']) && is_array($rule['rewrite_controller_status'])) {
            if (!empty($rule['rewrite_controller_status'][1])) {
                $location = array_pop($rule['rewrite_controller_status']);
                if (is_callable($location)) {
                    $location = call_user_func($location);
                }
                $rule['rewrite_controller_status'][] = $location;
            } // Redirect was desired, but no redirect URL was passed
            elseif ($rule['rewrite_controller_status'][0] === CONTROLLER_STATUS_REDIRECT) {
                // Just exit here, because core will continue executing controller stack when no redirect URL is given
                if (empty($_REQUEST['redirect_url'])) {
                    exit;
                }

                $rule['rewrite_controller_status'][] = $_REQUEST['redirect_url'];
            }

            return $rule['rewrite_controller_status'];
        }
    }
}
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

use Tygh\Registry;
use Tygh\Enum\ProductTracking;

if (!defined('BOOTSTRAP')) { die('Access denied'); }

//
// Returns an array of IDs of compatible products
//
function fn_get_compatible_products_ids($current_product_id, $current_group_id)
{
    $_sets = db_get_hash_array(
        "SELECT ?:products.product_id, ?:conf_class_products.class_id, ?:conf_classes.group_id "
            . "FROM ?:conf_classes "
                . "LEFT JOIN ?:conf_class_products ON ?:conf_classes.class_id = ?:conf_class_products.class_id "
                . "LEFT JOIN ?:products ON ?:products.product_id = ?:conf_class_products.product_id "
        . "WHERE ?:products.status IN ('A', 'H')",
        'product_id'
    );

    $sets = Array();
    foreach ($_sets as $_set) {
        $sets[$_set['class_id']][$_set['product_id']] = $_set;
    }

    $_relations = db_get_array(
        "SELECT slave_class_id, group_id FROM ?:conf_class_products "
            . "INNER JOIN ?:conf_compatible_classes "
                . "ON ?:conf_compatible_classes.master_class_id = ?:conf_class_products.class_id "
            . "INNER JOIN ?:conf_classes "
                . "ON ?:conf_classes.class_id = ?:conf_class_products.class_id "
        . "WHERE product_id = ?i",
        $current_product_id
    );

    $available_products = Array();

    foreach ($_relations as $slave_class) {
        if (isset($sets[$slave_class['slave_class_id']])) {
            foreach ($sets[$slave_class['slave_class_id']] as $product) {
                $available_products[$product['product_id']] = array(
                    'product_id' => $product['product_id'],
                    'group_id' => $product['group_id']
                );
            }
        }
    }

    return $available_products;
}

//
// Delete all links to this product product congiguration module
//
function fn_delete_configurable_product($product_id)
{
    db_query("DELETE FROM ?:conf_class_products WHERE product_id = ?i", $product_id);
    db_query("DELETE FROM ?:conf_group_products WHERE product_id = ?i", $product_id);
    db_query("DELETE FROM ?:conf_product_groups WHERE product_id = ?i", $product_id);

    // If this product was set as default for selection in some group
    $default_ids = db_get_array("SELECT product_id, default_product_ids FROM ?:conf_product_groups WHERE default_product_ids LIKE ?l", "%$product_id%");
    foreach ($default_ids as $key => $value) {
        $def_pr = trim(str_replace("::", ":", str_replace($product_id, "", $value['default_product_ids'])), ":");
        db_query("UPDATE ?:conf_product_groups SET default_product_ids = ?s WHERE product_id = ?i", $def_pr, $value['product_id']);
    }
}

//
// Delete product configuration group
//
function fn_delete_group($group_id)
{
    db_query("DELETE FROM ?:conf_groups WHERE group_id = ?i", $group_id);
    db_query("DELETE FROM ?:conf_group_products WHERE group_id = ?i", $group_id);
    db_query("DELETE FROM ?:conf_product_groups WHERE group_id = ?i", $group_id);
    db_query("DELETE FROM ?:conf_group_descriptions WHERE group_id = ?i", $group_id);

    fn_delete_image_pairs($group_id, 'conf_group');

    // Reset all classes in this group
    db_query("UPDATE ?:conf_classes SET group_id = 0 WHERE group_id = ?i", $group_id);
}

//
// Delete product configuration class
//
function fn_delete_class($class_id)
{
    db_query("DELETE FROM ?:conf_classes WHERE class_id = ?i", $class_id);
    db_query("DELETE FROM ?:conf_class_products WHERE class_id = ?i", $class_id);
    db_query("DELETE FROM ?:conf_compatible_classes WHERE slave_class_id = ?i OR master_class_id = ?i", $class_id, $class_id);
    db_query("DELETE FROM ?:conf_class_descriptions WHERE class_id = ?i", $class_id);
}

function fn_product_configurator_get_group_name($group_id, $lang_code = CART_LANGUAGE)
{
    if (!empty($group_id)) {
        return db_get_field("SELECT configurator_group_name FROM ?:conf_group_descriptions WHERE group_id = ?i AND lang_code = ?s", $group_id, $lang_code);
    }

    return false;
}

function fn_product_configurator_get_class_name($class_id, $lang_code = CART_LANGUAGE)
{
    if (!empty($class_id)) {
        return db_get_field("SELECT class_name FROM ?:conf_class_descriptions WHERE class_id = ?i AND lang_code = ?s", $class_id, $lang_code);
    }

    return false;
}

//
// This function regenerates the cart ID tahing into account the confirable properties of an item
//
function fn_product_configurator_generate_cart_id(&$_cid, $extra, $only_selectable)
{
    // Configurable product
    if (!empty($extra['configuration'])) {
        foreach ($extra['configuration'] as $k => $v) {
            $_cid[] = $k;
            if (!empty($v['product_ids'])) {
                foreach ($v['product_ids'] as $_id) {
                    $_cid[] = $_id;
                    if (!empty($v['options'][$_id]['product_options'])) {
                        foreach ($v['options'][$_id]['product_options'] as $opt => $vrt) {
                            $_cid[] = $vrt;
                        }
                    }
                }
                $_cid[] = $v['amount'];
            }
        }
    }
    if (!empty($extra['parent']['configuration'])) {
        $_cid[] = $extra['parent']['configuration'];
        $_cid[] = $v['step'];
    }
}

//
// This function clones product configuration
//
function fn_product_configurator_clone_product($product_id, $pid)
{
    $configuration = db_get_array("SELECT * FROM ?:conf_product_groups WHERE product_id = ?i", $product_id);
    if (empty($configuration)) {
        return false;
    }
    if (is_array($configuration)) {
        foreach ($configuration as $k => $v) {
            $v['product_id'] = $pid;
            db_query("INSERT INTO ?:conf_product_groups ?e", $v);
        }
    }

    return true;
}

function fn_product_configurator_get_products($params, &$fields, &$sortings, &$condition, &$join, $sorting, $group_by, $lang_code, $having)
{
    if (AREA == 'C') {
        foreach ($sortings as $type => $field) {
            $sortings[$type] = array('products.product_type', $field);
        }
    }
    
    $sortings['configurable'] = 'products.product_type';
    
    if (!empty($params['configurable'])) {
        if ($params['configurable'] == 'Y') {
            $condition .= db_quote(' AND products.product_type = ?s', 'C');
        } elseif ($params['configurable'] != 'Y') {
            $condition .= db_quote(' AND products.product_type != ?s', 'C');
        }
    }
    if (!empty($params['pc_group_id'])) {
        $fields[] = 'GROUP_CONCAT(DISTINCT ?:conf_class_products.class_id) as class_ids';
        $join .= db_quote(" LEFT JOIN ?:conf_group_products ON ?:conf_group_products.product_id = products.product_id LEFT JOIN ?:conf_classes ON ?:conf_classes.group_id = ?:conf_group_products.group_id LEFT JOIN ?:conf_class_products ON ?:conf_class_products.product_id = products.product_id");
        $condition .= db_quote(' AND ?:conf_group_products.group_id = ?i', $params['pc_group_id']);
    }

    return true;
}

function fn_product_configurator_gather_additional_product_data_before_discounts(&$product, $auth, $params)
{
    if (!empty($product['product_type']) && $product['product_type'] == 'C') {
        if (AREA == 'C' && !empty($params['get_for_one_product'])) {
                $product['configuration_mode'] = true;
                $selected_configuration = array();
                $product['edit_configuration'] = !empty($product['cart_id']) ? $product['cart_id'] : $_REQUEST['cart_id'];;
                if (!empty($_REQUEST['cart_id'])) {
                    $cart = & $_SESSION['cart'];
                    if (isset($cart['products'][$product['edit_configuration']]['extra'])) {
                        $product['extra'] = $cart['products'][$product['edit_configuration']]['extra'];
                        $product['selected_amount'] = $cart['products'][$product['edit_configuration']]['amount'];
                    }

                    $selected_configuration = $cart['products'][$_REQUEST['cart_id']]['extra']['configuration'];
                } elseif (!empty($product['selected_configuration'])) {
                    $selected_configuration = $product['selected_configuration'];
                }
                fn_get_configuration_groups($product, $selected_configuration);
        } elseif (AREA == 'C') {
            $product['price'] = $product['base_price'] = 1;
        }
    }

    return true;
}

/**
 * Additional actions for product quick view
 *
 * @param array $params Request parameters
 * @return boolean Always true
 */
function fn_product_configurator_prepare_product_quick_view($params)
{
    $product = Registry::get('view')->getTemplateVars('product');
    fn_pconf_gather_default_configuration_price($product);

    Registry::get('view')->assign('product', $product);

    return true;
}

/**
 * Calculates price of default product configuration
 *
 * @param array $product Product data
 * @return boolean Always true
 */
function fn_pconf_gather_default_configuration_price(&$product)
{
    $price = 0;

    if ($product['product_type'] == 'C') {
        $conf_product_groups = db_get_hash_single_array("SELECT ?:conf_product_groups.group_id, ?:conf_product_groups.default_product_ids FROM ?:conf_product_groups LEFT JOIN ?:conf_groups ON ?:conf_product_groups.group_id = ?:conf_groups.group_id WHERE ?:conf_groups.status = 'A' AND ?:conf_product_groups.product_id = ?i", array('group_id', 'default_product_ids'), $product['product_id']);

        $product_ids = array();
        foreach ($conf_product_groups as $group_id => $group_product_ids) {
            $tmp = is_array($group_product_ids) ? $group_product_ids : explode(':', $group_product_ids);
            foreach ($tmp as $product_id) {
                $product_ids[$product_id] = !empty($product_ids[$product_id]) ? ($product_ids[$product_id] + 1) : 1;
            }
        }

        if (!empty($product_ids)) {
            list($sub_products, $search) = fn_get_products(array('pid' => array_keys($product_ids)));

            fn_gather_additional_products_data($sub_products, array('get_icon' => false, 'get_detailed' => false, 'get_options' => false, 'get_discounts' => true, 'get_features' => false));

            foreach ($sub_products as $sub_product) {
                $price_modifier = $product_ids[$sub_product['product_id']];

                // calculate original price
                $sub_price = !empty($sub_product['original_price']) ? $sub_product['original_price'] : $sub_product['base_price'];

                $product['original_price'] = (!empty($product['original_price']) ? $product['original_price'] : $product['base_price']) + $sub_price * $price_modifier;

                // calculate list price
                $sub_price = ($sub_product['list_price'] > 0) ? $sub_product['list_price'] : $sub_product['base_price'];

                $product['list_price'] = (($product['list_price'] > 0) ? $product['list_price'] : $product['base_price']) + $sub_price * $price_modifier;

                $product['base_price'] += $sub_product['base_price'] * $price_modifier;
                $product['price'] += $sub_product['price'] * $price_modifier;

            }
        }
    }

    return true;
}

/** * Recalculates price and checks if product can be added with the current price
 *
 * @param array $data Adding product data
 * @param float $price Calculated product price
 * @param boolean $allow_add Flag that determines if product can be added to cart
 * @return boolean Always true
 */
function fn_product_configurator_add_product_to_cart_check_price($data, $price, &$allow_add)
{
    if (!$allow_add && empty($price) && !empty($data['configuration'])) {
        $allow_add = true;
    }

    return true;
}

function fn_product_configurator_get_cart_product_data($product_id, &$_pdata, &$product, $auth, &$cart, $hash)
{
    if (!empty($product['configuration'])) {
        $cart['products'][$hash]['amount_total'] = 0;
        $tmp_cart = $cart;
        $_pdata['weight'] = 0;
        foreach ($product['configuration'] as $item_id => $_data) {
            $cart['products'][$hash]['amount_total'] += $_data['amount'];

            $_cproduct = fn_get_cart_product_data($item_id, $product['configuration'][$item_id], false, $tmp_cart, $auth);

            if (empty($_cproduct)) { // FIXME - for deleted products for OM
                unset($product['configuration'][$item_id]);

                continue;
            }

            $cart['products'][$hash]['price'] += $_cproduct['price'] * $_data['extra']['step'];
            $_pdata['price'] += $_cproduct['price'] * $_data['extra']['step'];
            $_pdata['base_price'] += $_cproduct['base_price'] * $_data['extra']['step'];
            $_pdata['weight'] += $_cproduct['weight'] * $_data['extra']['step'];
            
            
            $_tax = (!empty($_cproduct['tax_summary']) ? ($_cproduct['tax_summary']['added'] / $_cproduct['amount']) : 0);
            $_cproduct['display_price'] = $_cproduct['price'] + (Registry::get('settings.Appearance.cart_prices_w_taxes') == 'Y' ? $_tax : 0);
            $_cproduct['step'] = $_data['extra']['step'];
            $_cproduct['subtotal'] = $_cproduct['price'] * $_data['extra']['step'];
            $_cproduct['display_subtotal'] = $_cproduct['display_price'] * $_data['extra']['step'];
            $_pdata['configuration'][$item_id] = $_cproduct;
        }
    }
}

function fn_product_configurator_pre_add_to_cart(&$product_data, &$cart, $auth, $update)
{
    if (!empty($update)) {
        foreach ($product_data as $key => $value) {
            if (!empty($cart['products'][$key]['extra']['configuration']) && !empty($value['product_id'])) {
                $product_data[$key]['extra']['configuration'] = $cart['products'][$key]['extra']['configuration'];
                if (!empty($value['product_options'])) {
                    $product_data[$key]['extra']['product_options'] = $value['product_options'];
                }
                foreach ($cart['products'][$key]['configuration'] as $k => $v) {
                    $product_data[$k] = array(
                        'product_id' => $v['product_id'],
                        'amount' => $v['extra']['step'] * $value['amount'],
                        'extra' => array(
                            'parent' => array(
                                'configuration' => $cart['products'][$key]['extra']['configuration_id'],
                                'id' => $key
                            ),
                            'step' => $v['extra']['step'],
                        ),
                    );
                    if (!empty($v['product_options'])) {
                        $product_data[$k]['product_options'] = $v['product_options'];
                    }
                }

                $product_data[$key]['extra']['configuration_id'] = $cart['products'][$key]['extra']['configuration_id'];
            }
        }

    } else {
        foreach ($product_data as $key => $value) {
            if (!empty($value['cart_id'])) { // if we're editing the configuration, just delete it and add new
                fn_delete_cart_product($cart, $value['cart_id']);
            }

            if (!empty($value['configuration']) && !empty($value['product_id'])) {
                $product_data[$key]['extra']['configuration'] = $value['configuration'];

                if (!empty($value['product_options'])) {
                    $product_data[$key]['extra']['product_options'] = $value['product_options'];
                }

                $cart_id = fn_generate_cart_id($key, $product_data[$key]['extra'], false);

                foreach ($value['configuration'] as $group_id => $_data) {
                    foreach ($_data['product_ids'] as $_id) {
                        if (!isset($product_data[$_id])) {
                            $product_data[$_id] = array();
                            $product_data[$_id]['product_id'] = $_id;
                            $product_data[$_id]['amount'] = (!empty($_data['amount']) ? $_data['amount'] : 1) * $value['amount'];
                            $product_data[$_id]['extra']['parent']['configuration'] = $cart_id;
                        } elseif (isset($product_data[$_id]['extra']['parent']['configuration']) && $product_data[$_id]['extra']['parent']['configuration'] == $cart_id) {
                            $product_data[$_id]['amount'] += (!empty($_data['amount']) ? $_data['amount'] : 1) * $value['amount'];
                        }
                        $product_data[$_id]['extra']['parent']['id'] = $value['product_id'];
                        if (!empty($_data['options'][$_id]['product_options'])) {
                            $product_data[$_id]['product_options'] = $_data['options'][$_id]['product_options'];
                        }
                    }
                }
                $product_data[$key]['extra']['configuration_id'] = $cart_id;
            }
        }
    }

    // We need to calculate step here because in configuration may be the same products.
    foreach ($product_data as $key => $value) { // We need set 'step' value for all products
        if (isset($value['extra']['parent']['id'])) {
            $parent_id = $value['extra']['parent']['id'];
            if (isset($product_data[$parent_id]) && $value['amount'] >= $product_data[$parent_id]['amount']) {
                $product_data[$key]['extra']['step'] = (int) ($value['amount'] / $product_data[$parent_id]['amount']);
            } else {
                $product_data[$key]['extra']['step'] = $value['amount'];
            }
        } elseif (!empty($value['extra']['configuration'])) {
            $product_data[$key]['extra']['step'] = $value['amount'];
        }
    }

}

function fn_product_configurator_add_to_cart(&$cart, $product_id, $_id)
{
    if (isset($cart['products'][$_id]['extra']['parent']['configuration'])) {
        $is_added = false;
        foreach ($cart['products'] as $key => $product) {
            if (isset($product['extra']['configuration_id']) && $product['extra']['configuration_id'] == $cart['products'][$_id]['extra']['parent']['configuration']) {
                $cart['products'][$_id]['extra']['parent']['configuration'] = $key;
                $is_added = true;
                break;
            }
        }
        if (!$is_added) {
            unset($cart['products'][$_id]);
            foreach ($cart['product_groups'] as $key_group => $group) {
                if (in_array($_id, array_keys($group['products']))) {
                    unset($cart['product_groups'][$key_group]['products'][$_id]);
                }
            }
        }
    }
}

/**
 * Chack main product product amount after adding product to cart
 *
 * @param array $product_data Product data
 * @param array $cart Cart data
 * @param array $auth Auth data
 * @param bool $update Flag the determains if cart data are updated
 * @return bool Always true
 */
function fn_product_configurator_post_add_to_cart($product_data, &$cart, $auth, $update)
{
    foreach ($cart['products'] as $key => $value) {
        if (!empty($value['extra']['configuration'])) {

            $total_amount = $value['amount'];
            $is_changed = false;
            foreach ($cart['products'] as $k => $v) {
                if (isset($v['extra']['parent']['configuration']) && $v['extra']['parent']['configuration'] == $value['extra']['configuration_id']) {
                    $amount = floor($v['amount'] / $v['extra']['step']);
                    if ($total_amount > $amount) {
                        $total_amount = $amount;
                        $is_changed =  true;
                    }
                    $v['product_option_data'] = fn_get_selected_product_options_info($v['product_options']);
                    $cart['products'][$key]['configuration'][$k] = $v;
                    unset($cart['products'][$k]);
                }
            }
            if (count($value['extra']['configuration']) > count($cart['products'][$key]['configuration'])) {
                unset($cart['products'][$key]);
                continue;
            }

            if ($is_changed) {
                $cart['products'][$key]['amount'] = $total_amount;
                foreach ($cart['products'][$key]['configuration'] as $k => $v) {
                    $cart['products'][$k]['amount'] = (int) $cart['products'][$k]['extra']['step'] * $total_amount;
                }
            }
        }
    }
}

/**
 * Compares order items
 *
 * @param array $item1 First item
 * @param array $item2 Second item
 * @return int The result of comparison
 */
function fn_pconf_compare_order_items($item1, $item2)
{
   $result = 0;

   if (isset($item1['extra']['configuration_id']) && isset($item2['extra']['parent']['configuration']) && $item1['extra']['configuration_id'] == $item2['extra']['parent']['configuration']) {
       $result = -1;
   } elseif (isset($item2['extra']['configuration_id']) && isset($item1['extra']['parent']['configuration']) && $item2['extra']['configuration_id'] == $item1['extra']['parent']['configuration']) {
       $result = 1;
   } else {
       $result = strnatcasecmp($item1['product'], $item2['product']);
   }

   return $result;
}

/**
 * Sorts order items
 *
 * @param array $order Order information
 * @param array $additional_data Additional order data
 * @return bool Always true
 */
function fn_product_configurator_get_order_info(&$order, $additional_data)
{
   if (!empty($order['products'])) {
       uasort($order['products'], 'fn_pconf_compare_order_items');
   }

   return true;
}

/**
 * Prepare configurable product data to add it to wishlist
 *
 * @param array $product_data product data
 * @param array $wishlist wishlist storage
 * @param array $auth user session data
 * @return boolean always true
 */
function fn_product_configurator_pre_add_to_wishlist(&$product_data, &$wishlist, $auth)
{
    $update = false;
    fn_product_configurator_pre_add_to_cart($product_data, $wishlist, $auth, $update);

    return true;
}

/**
 * Delete configurable product from the wishlist
 *
 * @param array $wishlist wishlist storage
 * @param array $wishlist_id ID of the product to delete
 * @return boolean always true
 */
function fn_product_configurator_delete_wishlist_product(&$wishlist, $wishlist_id)
{
    if (!empty($wishlist['products'][$wishlist_id]['extra']['configuration'])) {
        foreach ($wishlist['products'] as $key => $item) {
            if (!empty($item['extra']['parent']['configuration']) && $item['extra']['parent']['configuration'] == $wishlist_id) {
                unset($wishlist['products'][$key]);
            }
        }
    }

    return true;
}

function fn_product_configurator_buy_together_restricted_product($product_id, $auth, &$is_restricted, $show_notification)
{
    if ($is_restricted) {
        return true;
    }

    $product_data = Registry::get('view')->getTemplateVars('product_data');

    if (!empty($product_data)) {
        if ($product_data['product_type'] == 'C') {
            $is_restricted = true;
        }

    } elseif (!empty($product_id)) {
        $product_data = fn_get_product_data($product_id, $auth, CART_LANGUAGE, '', true, true, true, true);

        if ($product_data['product_type'] == 'C') {
            $is_restricted = true;
        }
    }

    if ($is_restricted && $show_notification) {
        fn_set_notification('E', __('error'), __('buy_together_is_not_compatible_with_configurator', array(
            '[product_name]' => $product_data['product']
        )));
    }
}

function fn_product_configurator_calculate_options($cart_products, &$cart, $auth)
{
    if (!empty($cart['products'])) {
        foreach ($cart['products'] as $id => &$product) {
            if (!empty($product['extra']['parent']['configuration']) && !empty($cart['products'][$product['extra']['parent']['configuration']]['object_id'])) {
                $product['extra']['parent']['configuration'] = $cart['products'][$product['extra']['parent']['configuration']]['object_id'];
            }
        }
    }
}

function fn_product_configurator_amazon_products(&$cart_products, $cart)
{
    if (!empty($cart['products'])) {
        foreach ($cart['products'] as $cart_id => $product) {
            if (!empty($product['extra']['configuration'])) {
                foreach ($cart['products'] as $_id => $_product) {
                    if (isset($_product['extra']['parent']['configuration']) && $_product['extra']['parent']['configuration'] == $cart_id) {
                        $cart_products[$cart_id]['price'] -= $cart_products[$_id]['price'];
                    }
                }
            }
        }
    }
}

function fn_product_configurator_update_product_pre(&$product_data, $product_id, $lang_code, $can_update)
{
    if ($can_update == false) {
        return false;
    }
    if ($product_data['product_type'] == 'C') {
        $product_data['zero_price_action'] = 'P';
        $product_data['auto_price'] = 'N';
        $product_data['tracking'] = 'D';
        
        if (fn_allowed_for('ULTIMATE')) {
            if (!empty($product_id) && !empty($product_data['company_id'])) {
                $product_company_id = db_get_field('SELECT company_id FROM ?:products WHERE product_id = ?i', $product_id);

                if ($product_data['company_id'] != $product_company_id) {
                    // check if product is used in product groups
                    $is_in_conf = false;
                    if ($product_data['product_type'] == 'C') {
                        $is_in_conf = true;
                    } elseif (db_get_field("SELECT count(1) FROM ?:conf_group_products WHERE product_id = ?i", $product_id)) {
                        $is_in_conf = true;
                    } elseif (db_get_field("SELECT count(1) FROM ?:conf_class_products WHERE product_id = ?i", $product_id)) {
                        $is_in_conf = true;
                    }

                    if ($is_in_conf) {
                        $product_data['company_id'] = $product_company_id;
                        fn_set_notification('W', __('warning'), __('pconf_company_update_denied'));
                    }
                }
            }
        }
    }

    return true;
}

function fn_check_pconf_access($db_field, $value, $show_notification = true)
{
    if (fn_allowed_for('ULTIMATE') && !empty($value)) {
        list($table, $field) = explode('.', $db_field);
        if (!empty($table) && !empty($field)) {
            $condition = fn_get_company_condition($table . '.company_id');
            if ($condition) {
                if (!is_array($value)) {
                    $value = explode(',', $value);
                }
                foreach ($value as $v) {
                    $result = db_get_field("SELECT COUNT(1) FROM $table WHERE $db_field = ?s" . $condition, $v);
                    if (!$result) {
                        if ($show_notification) {
                            fn_set_notification('E', __('error'), __('access_denied'));
                        }

                        return false;
                    }
                }
            }
        }
    }

    return true;
}

function fn_get_configuration_groups(&$product, $selected_configuration)
{
    $_tmp = microtime();
    $product_configurator_groups = db_get_array(
        "SELECT ?:conf_groups.group_id, ?:conf_group_descriptions.configurator_group_name, ?:conf_group_descriptions.amount_field, "
            . "?:conf_group_descriptions.full_description, ?:conf_groups.configurator_group_type, "
            . "?:conf_product_groups.position, ?:conf_product_groups.default_product_ids, ?:conf_product_groups.required, ?:conf_product_groups.max_amount "
        . "FROM ?:conf_groups "
            . "LEFT JOIN ?:conf_group_descriptions "
                . "ON ?:conf_group_descriptions.group_id = ?:conf_groups.group_id "
            . "LEFT JOIN ?:conf_product_groups "
                . "ON ?:conf_product_groups.group_id = ?:conf_groups.group_id  "
        . "WHERE ?:conf_groups.status = 'A' AND ?:conf_group_descriptions.lang_code = ?s "
            . "AND ?:conf_product_groups.product_id = ?i "
        . "ORDER BY ?:conf_product_groups.position",
        CART_LANGUAGE, $product['product_id']
    );

    $product['hide_stock_info'] = false;
    if (!empty($product_configurator_groups)) {
        $c_price = 0;

        $params = $inventory = array();
        foreach ($product_configurator_groups as $k => $v) {

            $params['pc_group_id'] = $v['group_id'];
            list($_products, $search) = fn_get_products($params);

            if (empty($_products)) {
                unset($product_configurator_groups[$k]);
                continue;
            }

            $default_ids = explode(':', $v['default_product_ids']);
            $selected_ids = empty($selected_configuration[$v['group_id']]['product_ids']) ? $default_ids : (!is_array($selected_configuration[$v['group_id']]['product_ids']) ? array($selected_configuration[$v['group_id']]['product_ids']) : $selected_configuration[$v['group_id']]['product_ids']);
            $selected_options = empty($selected_configuration[$v['group_id']]['options']) ? array() : $selected_configuration[$v['group_id']]['options'];
            $amount = !empty($selected_configuration[$v['group_id']]['amount']) ? $selected_configuration[$v['group_id']]['amount'] : 1;
            
            $class_ids = array();
            $is_selected = false;
            foreach ($_products as $_k => $_v) {

                $_products[$_k] = $_v;

                if (in_array($_v['product_id'], $selected_ids)) {
                    $is_selected = true;
                    $_products[$_k]['selected'] = 'Y';
                    if (!empty($selected_options[$_v['product_id']]['product_options'])) {
                        $_products[$_k]['selected_options'] = $selected_options[$_v['product_id']]['product_options'];
                    }
                } else {
                    $_products[$_k]['selected'] = 'N';
                }

                // Recommended products
                if (in_array($_v['product_id'], $default_ids)) {
                    $_products[$_k]['recommended'] = 'Y';
                }

                if (!empty($_v['class_ids'])) {
                    $_products[$_k]['class_ids'] = explode(',', $_v['class_ids']);
                    $class_ids = array_merge($class_ids, $_products[$_k]['class_ids']);
                }
            }
            
            if (!$is_selected) {
                $product['hide_stock_info'] = true;
            }
            fn_gather_additional_products_data($_products, array(
                'get_icon' => false,
                'get_detailed' => true,
                'get_additional' => false,
                'get_options' => true,
                'get_discounts' => false,
                'get_features' => false,
                'get_title_features' => true,
                'allow_duplication' => false
            ));

            $classes = db_get_hash_multi_array("SELECT ?:conf_compatible_classes.slave_class_id, ?:conf_compatible_classes.master_class_id FROM ?:conf_compatible_classes LEFT JOIN ?:conf_classes ON ?:conf_classes.class_id = ?:conf_compatible_classes.slave_class_id WHERE ?:conf_compatible_classes.master_class_id IN (?n) AND ?:conf_classes.status = 'A'", array('master_class_id', 'slave_class_id'), $class_ids);
            
            foreach ($_products as $_k => $_v) {
                $_products[$_k]['compatible_classes'] = array();
                if ($_v['selected'] == 'Y') {
                    if ($_v['hide_stock_info']) {
                        $product['hide_stock_info'] = true;
                    } else {
                        if (!empty($_v['tracking']) && $_v['tracking'] == ProductTracking::TRACK_WITH_OPTIONS) {
                            $max_amount = $_v['inventory_amount'];
                        } else {
                            $max_amount = $_v['amount'];
                        }
                    }
                }
                if (!empty($_v['class_ids'])) {
                    foreach ($_v['class_ids'] as $l => $cl_id) {
                        if (!empty($classes[$cl_id])) {
                            $_products[$_k]['compatible_classes'] += $classes[$cl_id];
                        }
                    }
                }
            }

            if ($product_configurator_groups[$k]['max_amount'] > 1 && isset($max_amount)) {
                $product_configurator_groups[$k]['show_amount'] = true;
            }
            if (isset($max_amount) && $product_configurator_groups[$k]['max_amount'] > $max_amount) {
                $product_configurator_groups[$k]['max_amount'] = $max_amount;
            }
            $product_configurator_groups[$k]['amount'] = ($product_configurator_groups[$k]['max_amount'] < $amount) ? $product_configurator_groups[$k]['max_amount'] : $amount;
            if (isset($max_amount)) {
                $inventory[] = floor($max_amount / $product_configurator_groups[$k]['amount']);
            }
            foreach ($_products as $_k => $_v) {
                if ($_v['selected'] == 'Y') {
                    $product_configurator_groups[$k]['selected_product'] = $_v['product_id'];
                    $c_price += $_v['price'] * $product_configurator_groups[$k]['amount'];
                }
            }
            
            $product_configurator_groups[$k]['products_count'] = count($_products);
            $product_configurator_groups[$k]['products'] = $_products;
            $product_configurator_groups[$k]['main_pair'] = fn_get_image_pairs($v['group_id'], 'conf_group', 'M');
        }
        if (!empty($inventory)) {
            $product['amount'] = min($inventory);
        }
    }
    
    // Substitute configuration price instead of product price
    if (!empty($c_price)) {
        $product['price'] = $product['base_price'] = $product['original_price'] = $c_price;
    }

    if (!empty($product_configurator_groups)) {

        // Define list of incompatible products
        $tmp = $product_configurator_groups;
        foreach ($product_configurator_groups as $k => $v) {
            foreach ($v['products'] as $_k => $_v) {
                if ($_v['selected'] == 'Y' && !empty($_v['compatible_classes'])) {
                    foreach ($tmp as $t_key => $t_val) {
                        if ($v['group_id'] !=  $t_val['group_id']) {
                            foreach ($t_val['products'] as $t_kk => $t_vv) {
                                if (!empty($t_vv['class_ids']) && !empty(array_intersect($t_vv['class_ids'], array_keys($_v['compatible_classes'])))) {
                                    $tmp[$t_key]['products'][$t_kk]['disabled'] = false;
                                } else {
                                    $tmp[$t_key]['products'][$t_kk]['disabled'] = true;
                                }
                            }
                        }
                    }
                }
            }
        }

        $product['product_configurator_groups'] = $tmp;
    }

    return $product_configurator_groups;
}

function fn_product_configurator_get_additional_information(&$product, $product_data)
{
    if (!empty($product_data['product_data'][$product['product_id']]['configuration'])) {
        $product['selected_configuration'] = $product_data['product_data'][$product['product_id']]['configuration'];
    }
    if (!empty($product_data['product_data'][$product['product_id']]['cart_id'])) {
        $product['cart_id'] = $product_data['product_data'][$product['product_id']]['cart_id'];
    }
}

/**
 * Calculates price of selected configuration products
 *
 * @param array $conf_product_groups Product groups with selected products identifiers
 * @return float Calculated price
 */
function fn_pconf_get_configuration_price($conf_product_groups)
{
    $price = 0;
    $auth = & $_SESSION['auth'];
    foreach ($conf_product_groups as $k => $v) {
        if (!empty($v)) {
            $_products = db_get_hash_single_array("SELECT ?:product_prices.product_id, IF(?:product_prices.percentage_discount = 0, ?:product_prices.price, ?:product_prices.price - (?:product_prices.price * ?:product_prices.percentage_discount)/100) as price FROM ?:product_prices LEFT JOIN ?:conf_group_products ON ?:conf_group_products.product_id = ?:product_prices.product_id WHERE ?:conf_group_products.group_id = ?i AND ?:product_prices.lower_limit = 1 AND ?:product_prices.usergroup_id IN (?n)", array('product_id', 'price'), $k, (AREA == 'A' ? USERGROUP_ALL : array_merge(array(USERGROUP_ALL), $auth['usergroup_ids'])));
            $tmp = is_array($v) ? $v : explode(':', $v);
            foreach ($tmp as $pid) {
                if (!empty($pid) && !empty($_products[$pid]) && AREA != 'A') {
                    $price += $_products[$pid];
                }
            }
        }
    }

    return $price;
}

/**
 * Update product configurator products
 *
 * @param array $cart Array of cart content and user information necessary for purchase
 * @param array $product_data Array of new products data
 * @param array $auth Array of user authentication data (e.g. uid, usergroup_ids, etc.)
 * @return boolean Always true
 */
function fn_product_configurator_update_cart_products_post(&$cart, $product_data, $auth)
{
    if (!empty($cart['products'])) {
        foreach ($cart['products'] as $_id => $product) {
            if (!empty($product['extra']['configuration']) && !empty($product['prev_cart_id']) && $product['prev_cart_id'] != $_id) {
                foreach ($cart['products'] as $aux_id => $aux_product) {
                    if (!empty($aux_product['extra']['parent']['configuration']) && $aux_product['extra']['parent']['configuration'] == $product['prev_cart_id']) {
                        $cart['products'][$aux_id]['extra']['parent']['configuration'] = $_id;
                        $cart['products'][$aux_id]['update_c_id'] = true;
                    }
                }
            }
        }

        foreach ($cart['products'] as $upd_id => $upd_product) {
            if (!empty($upd_product['update_c_id']) && $upd_product['update_c_id'] == true) {
                $new_id = fn_generate_cart_id($upd_product['product_id'], $upd_product['extra'], false);

                if (!isset($cart['products'][$new_id])) {
                    unset($upd_product['update_c_id']);
                    $cart['products'][$new_id] = $upd_product;
                    unset($cart['products'][$upd_id]);
                    foreach ($cart['product_groups'] as $key_group => $group) {
                        if (in_array($upd_id, array_keys($group['products']))) {
                            unset($cart['product_groups'][$key_group]['products'][$upd_id]);
                            $cart['product_groups'][$key_group]['products'][$new_id] = $upd_product;
                        }
                    }

                    // update taxes
                    fn_update_stored_cart_taxes($cart, $upd_id, $new_id, false);
                }
            }
        }
    }

    return true;
}


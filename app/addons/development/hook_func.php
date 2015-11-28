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

use Tygh\FeaturesCache;
use Tygh\Registry;

if (!defined('BOOTSTRAP')) { die('Access denied'); }

function fn_development_get_notification_rules(&$force_notification, $params, $disable_notification)
{
    if ($disable_notification) {
        $force_notification['S'] = false;
    } else {
        if (!empty($params['notify_user_sms']) || $params === true) {
            $force_notification['S'] = true;
        } else {
            if (AREA == 'A') {
                $force_notification['S'] = false;
            }
        }
    }
}

function fn_development_order_notification($order_info, $order_statuses, $force_notification)
{
    if (!is_array($force_notification)) {
        $force_notification = fn_get_notification_rules($force_notification, !$force_notification);
    }
    $order_statuses = fn_get_statuses(STATUSES_ORDER, array(), true, false, ($order_info['lang_code'] ? $order_info['lang_code'] : CART_LANGUAGE), $order_info['company_id']);
    $status_params = $order_statuses[$order_info['status']]['params'];

    $notify_user_sms = isset($force_notification['S']) ? $force_notification['S'] : (!empty($status_params['notify_sms']) && $status_params['notify_sms'] == 'Y' ? true : false);

    if ($notify_user_sms == true) {

        $order_status = $order_statuses[$order_info['status']];
        $status_settings = $order_statuses[$order_info['status']]['params'];
        $profile_fields = fn_get_profile_fields('I', '', $order_info['lang_code']);
        $phone = preg_replace('/[^0-9]/', '', $order_info['phone']);
        if (!empty($order_status['sms_text']) && strlen($phone) == 11 && $phone[0] == '7') {
            if (strpos($order_status['sms_text'], '[order_id]')) {
                $order_status['sms_text'] = str_replace('[order_id]', $order_info['order_id'], $order_status['sms_text']);
            }
            if (strpos($order_status['sms_text'], '[delivery_time]')) {
                $delivery_time = '';
                if (!empty($order_info['s_city']) && !empty($order_info['delivery_time'])) {
                    $delivery_time = ' ' . __("destination_delivery_time", array('[city]' => $order_info['s_city'])) . ': ' . $order_info['delivery_time'] . __("workdays");
                }
                $order_status['sms_text'] = str_replace('[delivery_time]', $delivery_time, $order_status['sms_text']);
            }
            if (strpos($order_status['sms_text'], '[office_info]')) {
                $office_info = '';
                if (!empty($order_info['shipping'][0]['office_data'])) {
                    $office_info = ' ' . __("destination_point") . ': ' . $order_info['shipping'][0]['office_data']['City'] . ',' . $order_info['shipping'][0]['office_data']['Address'] . ',' . $order_info['shipping'][0]['office_data']['WorkTime'] . ',' . $order_info['shipping'][0]['office_data']['Phone'];
                }
                $order_status['sms_text'] = str_replace('[office_info]', $office_info, $order_status['sms_text']);
            }
            fn_rus_unisender_send_sms($order_status['sms_text'], $phone);
        }
    }
}

function fn_development_get_status_params_definition(&$status_params, $type)
{
    $notify_sms = array (
        'type' => 'checkbox',
        'label' => 'notify_customer_sms',
        'default_value' => 'N'
    );
    $status_params = fn_insert_before_key($status_params, 'notify_department', 'notify_sms', $notify_sms);
}

function fn_development_get_user_info($user_id, $get_profile, $profile_id, &$user_data)
{
    if (AREA == 'C') {
        $user_data['confirmation_sent'] = (fn_ekey_exists($user_id, 'E')) ? 'Y' : 'N';
        $user_data['mail_server'] = fn_get_mail_server($user_data['email']);
    }
}

function fn_development_update_user_pre($user_id, &$user_data, $auth, $ship_to_another, $notify_user, $send_password)
{
    $names = array(
        'р-н',
        'район',
        'г.',
        'гор.',
        'город',
        'п.г.т.',
        'посёлок городского типа',
        'р.п.',
        'рабочий посёлок',
        'к.п.',
        'курортный посёлок',
        'к.',
        'кишлак',
        'пс',
        'поселковый совет',
        'сс',
        'сельсовет',
        'смн',
        'сомон',
        'вл.',
        'волость',
        'д.п.',
        'дачный поселковый совет',
        'п.',
        'посёлок сельского типа',
        'н.п.',
        'населённый пункт',
        'п. ст.',
        'посёлок при станции',
        'ж/д ст.',
        'железнодорожная станция',
        'с.',
        'село',
        'м.',
        'местечко',
        'д.',
        'дер.',
        'деревня',
        'сл.',
        'слобода',
        'ст.',
        'станция',
        'ст-ца',
        'станица',
        'х.',
        'хутор',
        'у.',
        'улус',
        'рзд.',
        'разъезд',
        'клх',
        'колхоз',
        'свх',
        'совхоз',
        'зим.',
        'зимовье',
    );
    $user_data['s_city'] = str_replace($names, '', $user_data['s_city']);
    if (!empty($user_data['b_city'])) {
        $user_data['b_city'] = str_replace($names, '', $user_data['b_city']);
    }
}

function fn_development_change_order_status_post($status_to, $status_from, $order_info, $force_notification, $order_statuses, $place_order)
{
    $saving_data = db_get_hash_array("SELECT * FROM ?:savings_groups ORDER BY amount ASC", 'group_id');
    if (!empty($saving_data) && !empty($order_info['user_id'])) {
        $orders_total = db_get_field("SELECT SUM(total) FROM ?:orders WHERE user_id = ?i AND status = 'C'", $order_info['user_id']);
        $usergroup_ids = array();
        foreach ($saving_data as $i => $group_data) {
            $usergroup_ids[] = $group_data['usergroup_id'];
            if ($orders_total > $group_data['amount']) {
                $usergroup_id = $group_data['usergroup_id'];
            }
        }
        if (!empty($usergroup_ids)) {
            foreach ($usergroup_ids as $i => $ug_id) {
                fn_change_usergroup_status('F', $order_info['user_id'], $ug_id);
            }
        }
        if (!empty($usergroup_id)) {
            fn_change_usergroup_status('A', $order_info['user_id'], $usergroup_id);
        }
    }
}

function fn_development_pre_place_order($cart, &$allow, $product_groups)
{
    if (!$allow && !empty($cart['is_call_request'])) {
        $allow = true;
    }
}

function fn_development_delete_feature_post($feature_id, $variant_ids)
{
    db_query("UPDATE ?:product_options SET feature_id = '0' WHERE feature_id = ?i", $feature_id);
    db_query("UPDATE ?:product_option_variants SET feature_variant_id = '0' WHERE feature_variant_id IN (?n)", $variant_ids);
}

function fn_development_get_product_feature_data_before_select(&$fields, $join, $condition, $feature_id, $get_variants, $get_variant_images, $lang_code)
{
    $fields[] = '?:product_features.note_url';
    $fields[] = '?:product_features.note_text';
}

function fn_development_pre_get_orders($params, &$fields, $sortings, $get_totals, $lang_code)
{
    $fields[] = "?:orders.net_total";
    $fields[] = "?:orders.s_country";
    $fields[] = "?:orders.s_city";
}

function fn_development_get_order_info(&$order, $additional_data)
{
    if (fn_strtolower($order['s_city']) == fn_strtolower(Registry::get('settings.Company.company_city'))) {
        unset($order['delivery_time']);
    }
    $order['income'] = $order['total'] - $order['net_total'];
}

function fn_development_get_cart_product_data_post($hash, $product, $skip_promotion, &$cart, $auth, $promotion_amount, &$_pdata)
{
    $net_cost_rub = 0;
    if (!empty($_pdata['configuration'])) {
        foreach ($_pdata['configuration'] as $i => $pc) {
            $nc = (!empty($pc['net_cost']) && !empty($pc['net_currency_code'])) ? $pc['net_cost'] * Registry::get('currencies.' . $pc['net_currency_code'] . '.coefficient') : $pc['price'];
            $net_cost_rub += $nc * $pc['step'];
        }
    }
    $net_cost = (!empty($_pdata['net_cost']) && !empty($_pdata['net_currency_code'])) ? $_pdata['net_cost'] * Registry::get('currencies.' . $_pdata['net_currency_code'] . '.coefficient') : $_pdata['price'];
    $_pdata['net_cost_rub'] = ($net_cost + $net_cost_rub) * $product['amount'];
    $cart['net_subtotal'] += $_pdata['net_cost_rub'];
}

function fn_development_calculate_cart_post(&$cart, $auth, $calculate_shipping, $calculate_taxes, $options_style, $apply_cart_promotions, $cart_products, $product_groups)
{
    $cart['net_total'] = $cart['net_subtotal'];
    if (!empty($cart['org_payment_surcharge'])) {
        $cart['net_total'] += $cart['org_payment_surcharge'];
    }
    if (!empty($cart['shipping'])) {
        foreach ($cart['shipping'] as $i => $shp) {
            $cart['net_total'] += $shp['original_rate'];
            if (!empty($shp['delivery_time'])) {
                $cart['delivery_time'] = preg_replace('/[^\-0-9]/', '', $shp['delivery_time']);
            }
        }
    }
}

function fn_development_pre_get_cart_product_data($hash, $product, $skip_promotion, $cart, $auth, $promotion_amount, &$fields, $join)
{
    $fields[] = '?:products.net_cost';
    $fields[] = '?:products.net_currency_code';
}

function fn_development_get_page_data(&$page_data, $lang_code)
{
    $page_data['image'] = fn_get_image_pairs($page_data['page_id'], 'page', 'M', false, true, $lang_code);
}

function fn_development_update_page_post($page_data, $page_id, $lang_code, $create, $old_page_data)
{
    fn_attach_image_pairs('page_bg', 'page', $page_id, DESCR_SL);
}

function fn_development_seo_is_indexed_page(&$indexed_pages)
{
    $indexed_pages['products.view']['noindex'][] = 'ohash';
}

function fn_development_apply_option_modifiers_pre($product_options, $base_value, &$orig_options, $extra, $fields, $type)
{
    if (empty($orig_options) && !empty($extra['product_data']['product_options'])) {
        $orig_options = $extra['product_data']['product_options'];
    }
}

function fn_development_get_filters_products_count_pre(&$params)
{
    $params['get_all'] = true;
    $tc_id = Registry::get('view')->gettemplatevars('active_tab');
    $tab_ids = Registry::get('view')->gettemplatevars('tab_ids');
    if (!empty($tab_ids[$tc_id])) {
        $params['features_hash'] = (!empty($params['features_hash']) ? ($params['features_hash'] . '.') : '') . 'V' . implode('.V', $tab_ids[$tc_id]);
    }
}

function fn_development_get_filters_products_count_before_select($filters, $view_all, &$params)
{
    $tc_id = Registry::get('view')->gettemplatevars('active_tab');
    $tab_ids = Registry::get('view')->gettemplatevars('tab_ids');
    if (!empty($tab_ids[$tc_id])) {
        str_replace('V' . implode('.V', $tab_ids[$tc_id]), '', $params['features_hash']);
    }
}

function fn_development_cron_routine()
{
    $scheme = fn_get_schema('cron', 'schema');
    
    if (!empty($scheme)) {
        foreach ($scheme as $type => $data) {
            if (!empty($data['wday']) && $data['wday'] != date('N')) {
                continue;
            }
            $is_executed = db_get_field("SELECT log_id FROM ?:cron_logs WHERE type = ?s AND status = 'F' AND timestamp > ?i", $type, TIME - $data['frequency']);
            if (empty($is_executed)) {
                $action = array(
                    'type' => $type,
                    'status' => 'S',
                    'timestamp' => TIME
                );
                $log_id = db_query("INSERT INTO ?:cron_logs ?e", $action);
                list($result, $errors) = call_user_func($data['function']);
                db_query("UPDATE ?:cron_logs SET status = ?s, errors = ?s WHERE log_id = ?i", ($result ? 'F' : 'S'), serialize($errors), $log_id);
            }
        }
    }
}

function fn_development_validate_sef_object($path, $seo, $vars, &$result, $objects)
{
    if ($seo['type'] == 'l') {
        if ($path == '/players' && !empty($seo['object_id'])) {
            $result = true;
        } else {
            $result = false;
        }
    }
}

function fn_development_clone_product_options_post($from_product_id, $to_product_id, $from_global_option_id)
{
    fn_update_product_tracking($to_product_id);
}

function fn_development_delete_product_option_post($option_id, $pid, $option_deleted)
{
    if ($option_deleted) {
        db_query("DELETE FROM ?:product_options_exceptions WHERE product_id = ?i", $pid);
    }

    fn_update_product_tracking($pid);
}

function fn_development_shippings_get_shippings_list_conditions($group, $shippings, &$fields, $join, &$condition, $order_by)
{
    $fields[] = "?:shippings.website, ?:shippings.payment_ids";
    $condition .= db_quote(' AND ((?:shippings.min_total <= ?d OR ?:shippings.min_total = 0.00)', $group['package_info']['C']);
    $condition .= db_quote(' AND (?:shippings.max_total >= ?d OR ?:shippings.max_total = 0.00))', $group['package_info']['C']);
}

function fn_development_shippings_get_shippings_list_post($group, $lang, $area, &$shippings_info)
{
    $destination_id = fn_get_available_destination($group['package_info']['location']);
    $shipping_ids = db_get_field("SELECT shipping_ids FROM ?:destinations WHERE destination_id = ?i", $destination_id);
    if (!empty($shipping_ids)) {
        $shipping_ids = unserialize($shipping_ids);
    }
    
    foreach ($shippings_info as $key => $shipping_info) {
        if (!empty($shipping_ids) && !in_array($shipping_info['shipping_id'], $shipping_ids)) {
            unset($shippings_info[$key]);
            continue;
        }
        $shippings_info[$key]['icon'] = fn_get_image_pairs($shipping_info['shipping_id'], 'shipping', 'M', true, true, $lang);
        $_payment_ids = unserialize($shippings_info[$key]['payment_ids']);
        $payment_ids = array();
        if (!empty($_payment_ids)) {
            foreach ($_payment_ids as $p_ids => $p_data) {
                if ($p_data['enabled'] === 'Y') {
                    $payment_ids[] = $p_ids;
                }
            }
        }
        if (!empty($payment_ids)) {
            $payment_categories = db_get_fields("SELECT payment_category FROM ?:payments WHERE payment_id IN (?n)", array_unique($payment_ids));
            if (!empty($payment_categories)) {
                foreach ($payment_categories as $i => $p_type) {
                    $shippings_info[$key]['available_payments'][$p_type] = 'Y';
                }
            }
        }
    }

    foreach ($shippings_info as $i => $shipping) {
        if ($shipping['min_weight'] > 0) {
            $keep = false;
            foreach ($group['products'] as $j => $product) {
                if ($product['weight'] >= $shipping['min_weight']) {
                    $keep = true;
                }
            }
            if (!$keep) {
                unset($shippings_info[$i]);
            }
        }
    }
}

function fn_development_update_payment_surcharge(&$cart, $auth, $lang_code)
{
    $surcharge = $cart['payment_surcharge'];
    $included = true;
    if (!empty($cart['chosen_shipping']) && !empty($cart['payment_id'])) {
        $ids = db_get_hash_single_array("SELECT payment_ids, shipping_id FROM ?:shippings WHERE shipping_id IN (?a)", array('shipping_id', 'payment_ids'), $cart['chosen_shipping']);
        foreach ($cart['chosen_shipping'] as $i => $sh_id) {
            if (!empty($ids[$sh_id])) {
                $payments = unserialize($ids[$sh_id]);
                if (!empty($payments[$cart['payment_id']]) && $payments[$cart['payment_id']]['enabled'] == 'Y') {
                    if ($payments[$cart['payment_id']]['included'] == 'N') {
                        $included = false;
                    }
                    if (floatval($payments[$cart['payment_id']]['a_surcharge'])) {
                        $surcharge += $payments[$cart['payment_id']]['a_surcharge'];
                    }
                    if (floatval($payments[$cart['payment_id']]['p_surcharge']) && !empty($cart['total'])) {
                        $surcharge += fn_format_price($cart['total'] * $payments[$cart['payment_id']]['p_surcharge'] / 100);
                    }
                }
            }
        }
    }
    $cart['org_payment_surcharge'] = $cart['payment_surcharge'] = $surcharge;
    if ($included) {
        $cart['payment_surcharge'] = 0;
    }
}

function fn_development_prepare_checkout_payment_methods($cart, $auth, &$payment_groups)
{
    if (!empty($cart['chosen_shipping'])) {
        $allowed_payments = $available_payments = array();
        $ids = db_get_hash_single_array("SELECT payment_ids, shipping_id FROM ?:shippings WHERE shipping_id IN (?a)", array('shipping_id', 'payment_ids'), $cart['chosen_shipping']);
        foreach ($cart['chosen_shipping'] as $i => $sh_id) {
            $available_payments = array_merge($available_payments, $cart['shipping'][$sh_id]['available_payments']);
            if (!empty($ids[$sh_id])) {
                $payments = unserialize($ids[$sh_id]);
                foreach ($payments as $p_id => $p_data) {
                    if ($p_data['enabled'] == 'Y') {
                        $allowed_payments[$p_id][] = $p_data;
                    }
                }
            }
        }
        if (!empty($allowed_payments) && !empty($payment_groups)) {
            foreach ($payment_groups as $j => $sh) {
                if (!in_array($sh['payment_id'], array_keys($allowed_payments)) || empty($available_payments[$sh['payment_category']]) || $available_payments[$sh['payment_category']] == 'N') {
                    unset($payment_groups[$j]);
                } else {
                    foreach ($allowed_payments[$sh['payment_id']] as $k => $sg) {
                        if (floatval($sg['a_surcharge'])) {
                            $payment_groups[$j]['surcharge_value'] += $sg['a_surcharge'];
                        }
                        if (floatval($sg['p_surcharge']) && !empty($cart['total'])) {
                            $payment_groups[$j]['surcharge_value'] += fn_format_price($cart['total'] * $sg['p_surcharge'] / 100);
                        }
                    }
                }
            }
        }
    }
}

function fn_development_update_shipping(&$shipping_data, $shipping_id, $lang_code)
{
    if (isset($shipping_data['payment_ids'])) {
        $shipping_data['payment_ids'] = !empty($shipping_data['payment_ids']) ? serialize($shipping_data['payment_ids']) : serialize(array());
    }
}

function fn_development_get_product_option_data_post(&$opt, $product_id, $lang_code)
{
    if (!empty($opt['variants'])) {
        $image_pairs = fn_get_image_pairs(array_keys($opt['variants']), 'variant_additional', 'Z', true, true, $lang_code);
        foreach ($opt['variants'] as $i => $variant) {
            if (!empty($image_pairs[$variant['variant_id']])) {
                $opt['variants'][$i]['images'] = $image_pairs[$variant['variant_id']];
            }
        }
    }
}

function fn_development_update_product_option_post($option_data, $option_id, $deleted_variants, $lang_code)
{
    if ($option_data['inventory'] == 'Y' && !empty($option_data['product_id'])) {
        db_query("UPDATE ?:products SET tracking = 'O' WHERE product_id = ?i", $option_data['product_id']);
    }
    if (!empty($option_data['feature_id'])) {
        $feature_data = fn_get_product_feature_data($option_data['feature_id'], true);
    }
    $option_variants = array();
            
    if (!empty($option_data['variants'])) {
        foreach ($option_data['variants'] as $i => $variant) {
            if (!empty($variant['variant_id'])) {
                $feature_variant_id = 0;
                if (!empty($feature_data)) {
                    if (!empty($feature_data['variants'])) {
                        foreach ($feature_data['variants'] as $j => $f_variant) {
                            if ($variant['variant_name'] == $f_variant['variant']) {
                                $feature_variant_id = $f_variant['variant_id'];
                                break;
                            }
                        }
                    }
                    if (empty($feature_variant_id)) {
                        $f_v_data = array(
                            'variant' => $variant['variant_name']
                        );
                        $feature_variant_id = fn_update_product_feature_variant($feature_data['feature_id'], $feature_data['feature_type'], $f_v_data);
                    }
                }
                db_query('UPDATE ?:product_option_variants SET feature_variant_id = ?i WHERE variant_id = ?i', $feature_variant_id, $variant['variant_id']);

                fn_attach_image_pairs('variant_additional_' . $variant['variant_id'], 'variant_additional', $variant['variant_id'], $lang_code);
                fn_attach_image_pairs('variant_add_additional_' . $variant['variant_id'], 'variant_additional', $variant['variant_id'], $lang_code);
            }
        }
    }
}

function fn_development_get_product_option_data_pre($option_id, $product_id, &$fields, $condition, $join, &$extra_variant_fields, $lang_code)
{
    $extra_variant_fields .= ' a.code_suffix,';
    $fields .= ', b.default_text, b.option_note';
}

function fn_development_get_product_features(&$fields, $join, $condition, $params)
{
    $fields[] = 'pf.note_url';
    $fields[] = 'pf.note_text';
}

function fn_development_get_product_features_list_before_select(&$fields, $join, $condition, $product, $display_on, $lang_code)
{
    $fields .= db_quote(", vd.mens_clothes_size_chart, vd.womens_clothes_size_chart, vd.mens_shoes_size_chart, vd.womens_shoes_size_chart");
}

function fn_development_redirect_complete(&$meta_redirect)
{
    $meta_redirect = false;
}

function fn_development_delete_product_feature_variants($feature_id, $variant_ids)
{
    if (!empty($feature_id)) {
        FeaturesCache::deleteFeature($feature_id);
    } elseif (!empty($variant_ids)) {
        FeaturesCache::deleteVariants($variant_ids);
    }
}

function fn_development_update_product_features_value($product_id, $product_features, $add_new_variant, $lang_code)
{
    $features = db_get_array("SELECT * FROM ?:product_features_values WHERE product_id = ?i", $product_id);
    FeaturesCache::updateProductFeaturesValue($product_id, $features, $lang_code);
}

function fn_development_get_category_data_post($category_id, $field_list, $get_main_pair, $skip_company_condition, $lang_code, &$category_data)
{
    if (!empty($category_data['brand_id'])) {
        list($brands) = fn_get_product_feature_variants(array(
            'feature_id' => BRAND_FEATURE_ID,
            'feature_type' => 'E',
            'variant_ids' => $category_data['brand_id'],
            'get_images' => true
        ));
        $category_data['brand'] = $brands[$category_data['brand_id']];
    }
    if (AREA == 'C')  {
        if (!empty($category_data['id_path']) && (empty($category_data['tabs_categorization']) || empty($category_data['sections_categorization']) || empty($category_data['subtabs_categorization']))) {
            $category_data['cat_ids'] = explode('/', $category_data['id_path']);
            $categorization_data = db_get_hash_array("SELECT category_id, tabs_categorization, subtabs_categorization, sections_categorization FROM ?:categories WHERE category_id IN (?n)", 'category_id', $category_data['cat_ids']);
            fn_format_categorization($category_data, $categorization_data, 'tabs_categorization');
            fn_format_categorization($category_data, $categorization_data, 'subtabs_categorization');
            fn_format_categorization($category_data, $categorization_data, 'sections_categorization');
        }
        $data = fn_get_category_global_data($category_data, array('note_url', 'note_text', 'products_sorting'));
        if (empty($category_data['note_url']) && !empty($data['note_url'])) {
            $category_data['note_url'] = $data['note_url'];
        }
        if (empty($category_data['note_text']) && !empty($data['note_text'])) {
            $category_data['note_text'] = $data['note_text'];
        }
        if (empty($category_data['products_sorting']) && !empty($data['products_sorting'])) {
            $category_data['products_sorting'] = $data['products_sorting'];
        }
    }
    if (!empty($category_data['sections_categorization'])) {
        $category_data['sections_categorization'] = unserialize($category_data['sections_categorization']);
    }
    if (!empty($category_data['cross_categories'])) {
        $category_data['cross_categories'] = unserialize($category_data['cross_categories']);
    }
}

function fn_development_get_product_feature_variants(&$fields, $join, &$condition, $group_by, $sorting, $lang_code, $limit)
{
    if (!empty($params['variant_ids'])) {
        $params['variant_ids'] = is_array($params['variant_ids']) ? $params['variant_ids'] : array($params['variant_ids']);
        $condition .= db_quote(" AND ?:product_feature_variants.variant_id IN (?n)", $params['variant_ids']);
    }
}

function fn_development_get_categories_post(&$categories_list, $params, $lang_code)
{
    if (AREA == 'C' && empty($params['skip_filter'])) {
        fn_filter_categroies($categories_list);
    }
    if (!empty($params['roundabout']) && !empty($categories_list)) {
        $brand_ids = array();
        foreach ($categories_list as $i => $category) {
            if (!empty($category['brand_id'])) {
                $brand_ids[] = $category['brand_id'];
            }
        }
        if (!empty($brand_ids)) {
            list($brands) = fn_get_product_feature_variants(array(
                'feature_id' => BRAND_FEATURE_ID,
                'feature_type' => 'E',
                'variant_ids' => $brand_ids,
                'get_images' => true
            ));
            foreach ($categories_list as $i => $category) {
                if (!empty($category['brand_id'])) {
                    $categories_list[$i]['brand'] = $brands[$category['brand_id']];
                }
            }
        }
    }
}

function fn_development_get_lang_var_post(&$value, $var_name)
{
    $value = fn_check_vars($value);
}

function fn_development_get_categories(&$params, $join, $condition, &$fields, $group_by, $sortings, $lang_code)
{
    $fields[] = '?:categories.note_url';
    $fields[] = '?:categories.note_text';
    $fields[] = '?:categories.product_count';
    $fields[] = '?:categories.code';
    $fields[] = '?:categories.is_virtual';
    if (!empty($params['roundabout']) || !empty($params['get_description'])) {
        $fields[] = '?:category_descriptions.description';
        if (!empty($params['roundabout'])) {
            $params['get_images'] = true;
            $fields[] = '?:categories.brand_id';
        }
    }
}

function fn_development_top_menu_form(&$v, $type, $id, $use_name)
{
    if ($type == 'P') {
        $params = array('plain' => true);
        list($players,) = fn_get_players($params);
        if (!empty($players)) {
            $atp = array();
            $wta = array();
            foreach($players as $i => $player) {
                if ($player['gender'] == 'M') {
                    $atp[] = $player;
                } else {
                    $wta[] = $player;
                }
            }
            $v['subitems'] = array(
                array('descr' => __('atp'), 'param' => 'players.list', 'subitems' => fn_top_menu_standardize($atp, 'player_id', 'player', '', 'players.view?player_id=')),
                array('descr' => __('wta'), 'param' => 'players.list', 'subitems' => fn_top_menu_standardize($wta, 'player_id', 'player', '', 'players.view?player_id=')),
            );
        }
    }
}

function fn_development_get_product_options(&$fields, $condition, $join, $extra_variant_fields, $product_ids, $lang_code)
{
    $fields .= ", b.default_text";
}

function fn_development_get_selected_product_options_post(&$_opts, $product_id, $selected_options, $lang_code)
{
    $_opts = fn_sort_array_by_key($_opts, 'show_on_catalog', SORT_DESC);
}

function fn_development_get_product_options_post($product_ids, $lang_code, $only_selectable, $inventory, $only_avail, &$options)
{
    $variant_ids = array();
    foreach ($options as $product_id => $_options) {
        $options[$product_id] = fn_sort_array_by_key($_options, 'show_on_catalog', SORT_DESC);
        foreach ($_options as $option_id => $_option) {
            if (!empty($_option['variants'])) {
                $variant_ids = array_merge($variant_ids, array_keys($_option['variants']));
            }
        }
    }
    if (!empty($variant_ids) && Registry::get('runtime.controller') == 'products') {
        $image_pairs = fn_get_image_pairs($variant_ids, 'variant_additional', 'Z', true, true, $lang_code);
        foreach ($options as $product_id => $_options) {
            foreach ($_options as $option_id => $_option) {
                if (!empty($_option['variants'])) {
                    foreach ($_option['variants'] as $variant_id => $variant) {
                        if (!empty($image_pairs[$variant_id])) {
                            $options[$product_id][$option_id]['variants'][$variant_id]['images'] = $image_pairs[$variant_id];
                        }
                    }
                }
            }
        }
    }
}

function fn_development_calculate_cart_items(&$cart, &$cart_products, $auth)
{
    if (!empty($cart_products)) {
        $main_ids = array();
        foreach ($cart_products as $i => $product) {
            $main_ids[] = $product['main_category'];
            $cart['products'][$i]['main_category'] = $product['main_category'];
            
            $color_ids = $color_prod_image_pairs = array();
            if ($product['tracking'] == 'O' && !empty($product['product_options'])) {
                foreach ($product['product_options'] as $i => $opt_data) {
                    if (!empty($opt_data['show_on_catalog']) && $opt_data['show_on_catalog'] == 'Y' && !empty($opt_data['variants']) && !empty($opt_data['value'])) {
                        $color_ids[] = $opt_data['value'];
                    }
                }
            }
        }
        $id_paths = db_get_hash_single_array("SELECT category_id, id_path FROM ?:categories WHERE category_id IN (?n)", array('category_id', 'id_path'), array_unique($main_ids));
        $cart['product_categories'] = array();
        if (!empty($id_paths)) {
            foreach ($id_paths as $i => $path) {
                $cart['product_categories'] = array_merge($cart['product_categories'], explode('/', $path));
            }
            foreach ($cart_products as $i => $product) {
                $cart_products[$i]['id_path'] = $id_paths[$product['main_category']];
            }
        }
        $cart['product_categories'] = array_unique($cart['product_categories']);
        
        if (!empty($color_ids)) {
            $color_prod_image_pairs = fn_get_image_pairs($color_ids, 'variant_additional', 'Z', false, true, CART_LANGUAGE);
        }

        if (!empty($color_prod_image_pairs)) {
            foreach ($cart_products as $cart_id => $product) {
                if ($product['tracking'] == 'O' && !empty($product['product_options'])) {
                    foreach ($product['product_options'] as $i => $opt_data) {
                        if (!empty($opt_data['show_on_catalog']) && $opt_data['show_on_catalog'] == 'Y' && !empty($opt_data['variants']) && !empty($opt_data['value'])) {
                            $cart['products'][$cart_id]['ohash'] = 'ohash[' . $opt_data['option_id'] . ']=' . $opt_data['value'];
                            if (!empty($color_prod_image_pairs[$opt_data['value']])) {
                                $cart['products'][$cart_id]['main_pair'] = reset($color_prod_image_pairs[$opt_data['value']]);
                            }
                        }
                    }
                }
            }
        }
    }
}

// ДЛЯ СПИСКА ТОВАРОВ
function fn_development_gather_additional_products_data_post($product_ids, $params, &$products, $auth)
{
    if (AREA == 'C' && empty($params['get_for_one_product']) && !empty($products)) {
        $color_ids = $color_image_pairs = $color_prod_image_pairs = $features_condition = array();
        if (!empty($params['get_options'])) {
            $avail_combinations = db_get_hash_multi_array("SELECT product_id, combination FROM ?:product_options_inventory WHERE amount > 0 AND product_id IN (?n)", array('product_id', 'combination'), $product_ids);
        }
        
        foreach ($products as $i => $product) {
            if (!empty($params['get_options'])) {
                // Для иконок цветов в списке товаров
                if ($product['tracking'] == 'O' && !empty($product['product_options'])) {
                    $show_opt_id = false;
                    foreach ($product['product_options'] as $i => $opt_data) {
                        if (!empty($opt_data['show_on_catalog']) && $opt_data['show_on_catalog'] == 'Y' && !empty($opt_data['variants'])) {
                            $show_opt_id = $opt_data['option_id'];
                            break;
                        }
                    }
                    if (!empty($show_opt_id)) {
                        if (!empty($avail_combinations[$product['product_id']])) {
                            foreach (array_keys($avail_combinations[$product['product_id']]) as $i => $combination) {
                                $options = fn_get_product_options_by_combination($combination);
                                if (!empty($options[$show_opt_id])) {
                                    $color_ids[] = $options[$show_opt_id];
                                }
                            }
                        }
                    }
                }
            }
            
            if (!empty($params['get_title_features'])) {
                // Условие для хак-к
                $find_set = array(
                    " f.categories_path = '' "
                );
                $path = explode('/', $product['id_path']);
                foreach ($path as $k => $v) {
                    $find_set[] = db_quote(" FIND_IN_SET(?i, f.categories_path) ", $v);
                }
                $find_in_set = db_quote(" AND (?p)", implode('OR', $find_set));
                $features_condition[] = db_quote(" (v.product_id = ?i ?p) ", $product['product_id'], $find_in_set);
            }
        }
        
        if (!empty($params['get_title_features'])) {
            $condition = db_quote("f.display_on_catalog = 'Y' AND f.status = 'A' AND IF(f.parent_id, (SELECT status FROM ?:product_features as df WHERE df.feature_id = f.parent_id), 'A') = 'A' ?p AND (v.variant_id != 0 OR (f.feature_type != 'C' AND v.value != '') OR (f.feature_type = 'C') OR v.value_int != '') AND v.lang_code = ?s", db_quote(" AND (?p)", implode('OR', $features_condition)), CART_LANGUAGE);
            $fields = db_quote("f.feature_type, v.variant_id, v.feature_id, GROUP_CONCAT(vd.variant SEPARATOR ',') as variants, v.product_id, gf.position as gposition");
            $join = db_quote(
                "LEFT JOIN ?:product_features_values as v ON v.feature_id = f.feature_id "
                . " LEFT JOIN ?:product_feature_variant_descriptions as vd ON vd.variant_id = v.variant_id AND vd.lang_code = ?s"
                . " LEFT JOIN ?:product_features as gf ON gf.feature_id = f.parent_id AND gf.feature_type = ?s ",
                CART_LANGUAGE, 'G');
            $products_features = db_get_hash_multi_array("SELECT $fields FROM ?:product_features as f $join WHERE $condition GROUP BY v.product_id, v.feature_id ORDER BY f.position", array('product_id', 'feature_id'));
            $brands = fn_get_product_feature_data(BRAND_FEATURE_ID, true, true);
        }

        if (!empty($params['get_options'])) {
            if (!empty($color_ids)) {
                $color_image_pairs = fn_get_image_pairs($color_ids, 'variant_image', 'V', true, false, CART_LANGUAGE);
            }
        }

        foreach ($products as $i => &$product) {
            if (!empty($params['get_options'])) {
                // Для иконок цветов в списке товаров
                if ($product['tracking'] == 'O' && !empty($product['product_options'])) {
                    foreach ($product['product_options'] as $j => $opt_data) {
                        if (!empty($opt_data['show_on_catalog']) && $opt_data['show_on_catalog'] == 'Y' && !empty($opt_data['variants'])) {
                            foreach ($opt_data['variants'] as $k => $v_data) {
                                if (!empty($color_image_pairs[$v_data['variant_id']])) {
                                    $product['option_images'][$v_data['variant_id']] = reset($color_image_pairs[$v_data['variant_id']]);
                                }
                            }
                        }
                    }
                }
            }
            
            if (!empty($params['get_title_features'])) {
                if (!empty($products_features[$product['product_id']])) {
                    $series_feature = fn_get_subtitle_feature($products_features[$product['product_id']], $product['type']);
                    $variants = explode(',', $series_feature['variants']);
                    $brand = $products_features[$product['product_id']][BRAND_FEATURE_ID]['variants'];
                    $product['brand'] = $brands['variants'][$products_features[$product['product_id']][BRAND_FEATURE_ID]['variant_id']];
                    if ($product['type'] == 'R') {
                        $product['subtitle'] = __("type") .  ' - ' .  $products_features[$product['product_id']][TYPE_FEATURE_ID]['variants'];
//                         if (!empty($variants)) {
//                             $product['subtitle'] = __("series") .  ' - ' .  reset($variants);
//                         } else {
//                             $product['subtitle'] = __("type") .  ' - ' .  $products_features[$product['product_id']][TYPE_FEATURE_ID]['variants'];
//                         }
                    } elseif ($product['type'] == 'A') {
                        $product['subtitle'] = reset($variants) .  ' - ' .  $brand;
                    } elseif ($product['type'] == 'S') {
                        $product['subtitle'] = __("surface") .  ' - ' .  reset($variants);
                    } elseif ($product['type'] == 'B') {
                        $product['subtitle'] = __("bag") .  ' - ' .  $brand;
                    } elseif ($product['type'] == 'ST') {
                        if (!empty($variants) && count($variants) > 1 && $series_feature['feature_type'] == 'M') {
                            $product['subtitle'] = __("hybrid");
                        } else {
                            $product['subtitle'] = __("structure") .  ' - ' .  reset($variants);
                        }
                    } elseif ($product['type'] == 'BL') {
                        $product['subtitle'] = __("type") .  ' - ' .  reset($variants);
                    } elseif ($product['type'] == 'OG') {
                        $product['subtitle'] = __("type") .  ' - ' .  reset($variants);
                    } elseif ($product['type'] == 'BG') {
                        $product['subtitle'] = __("material") .  ' - ' .  reset($variants);
                    }
                }
            }
        }

        if (!empty($params['get_options'])) {
            if (!empty($color_ids)) {
                $color_prod_image_pairs = fn_get_image_pairs($color_ids, 'variant_additional', 'Z', false, true, CART_LANGUAGE);
            }

            if (!empty($product['selected_options']) && $product['tracking'] == 'O' && !empty($product['product_options'])) {
                foreach ($product['product_options'] as $i => $opt_data) {
                    if (!empty($product['selected_options'][$opt_data['option_id']]) && !empty($opt_data['show_on_catalog']) && $opt_data['show_on_catalog'] == 'Y') {
                        $product['ohash'] = 'ohash[' . $opt_data['option_id'] . ']=' . $product['selected_options'][$opt_data['option_id']];
                        if(!empty($color_prod_image_pairs[$product['selected_options'][$opt_data['option_id']]])) {
                            $product['main_pair'] = reset($color_prod_image_pairs[$product['selected_options'][$opt_data['option_id']]]);
                        }
                    }
                }
            } elseif (Registry::get('settings.Appearance.catalog_options_mode') == 'Y' && !empty($params['allow_duplication'])) {
                $new_products = array();
                foreach ($products as $i => &$product) {
                    $found = false;
                    if ($product['tracking'] == 'O' && !empty($product['product_options'])) {
                        foreach ($product['product_options'] as $j => $opt_data) {
                            if (!empty($opt_data['show_on_catalog']) && $opt_data['show_on_catalog'] == 'Y' && !empty($opt_data['variants'])) {
                                $iteration = 0;
                                foreach ($opt_data['variants'] as $k => $v_data) {
                                    $image_pair = !empty($color_prod_image_pairs[$v_data['variant_id']]) ? reset($color_prod_image_pairs[$v_data['variant_id']]) : array();
                                    $new_product = array();
                                    if (!empty($image_pair) && $image_pair['pair_id'] != $product['main_pair']['pair_id'] && !empty($color_image_pairs[$v_data['variant_id']])) {
                                        $new_product = $product;
                                        $new_product['main_pair'] = reset($color_prod_image_pairs[$v_data['variant_id']]);
                                    } elseif (!empty($color_image_pairs[$v_data['variant_id']]) && empty($image_pair)) {
                                        $new_product = $product;
                                    }
                                    if (!empty($new_product)) {
                                        if ($iteration > 0) {
                                            $new_product['ohash'] = 'ohash[' . $opt_data['option_id'] . ']=' . $v_data['variant_id'];
                                        }
                                        if (!empty($v_data['variant_name'])) {
                                            $new_product['product'] .= ' ' . $v_data['variant_name'];
                                        }
                                        $new_products[] = $new_product;
                                        $found = true;
                                    }
                                    $iteration++;
                                }
                            }
                        }
                    }
                    if (!$found)  {
                        $new_products[] = $product;
                    }
                }
                $products = $new_products;
            }
        }
    }
}

// ДЛЯ ОДНОГО ТОВАРА
function fn_development_gather_additional_product_data_post(&$product, $auth, $params)
{
    if (AREA == 'C' && !empty($params['get_for_one_product'])) {
        if (!empty($params['get_options'])) {
            if (!empty($product['category_main_id'])) {
                $product['category_main_title'] = db_get_field("SELECT category FROM ?:category_descriptions WHERE category_id = ?i AND lang_code = ?s", $product['category_main_id'], CART_LANGUAGE);
            }
            if ($product['tracking'] == 'O' && !empty($product['combination_hash'])) {
                $combination = $product['combination_hash'];
            } elseif ($product['tracking'] == 'B') {
                $combination = 0;
            }
            if (isset($combination)) {
                if ($auth['user_id'] == 0 && !empty($_SESSION['product_notifications']['email'])) {
                    $subscription_id = db_get_field("SELECT subscription_id FROM ?:product_subscriptions WHERE product_id = ?i AND combination_hash = ?i AND email = ?s", $product['product_id'], $combination, $_SESSION['product_notifications']['email']);
                    if (!empty($subscription_id)) {
                        $product['inventory_notification'] = 'Y';
                        $product['inventory_notification_email'] = $_SESSION['product_notifications']['email'];
                    }
                } else {
                    $email = db_get_field("SELECT email FROM ?:product_subscriptions WHERE product_id = ?i AND combination_hash = ?i AND user_id = ?i", $product['product_id'], $combination, $auth['user_id']);
                    if (!empty($email)) {
                        $product['inventory_notification'] = 'Y';
                        $product['inventory_notification_email'] = $email;
                    }
                }
            }
            
            if (($params['get_icon'] == true || $params['get_detailed'] == true) && !empty($product['selected_options'])) {
                foreach ($product['selected_options'] as $option_id => $variant_id) {
                    if (!empty($product['product_options'][$option_id]['variants'][$variant_id]['images'])) {
                        $tmp = $product['product_options'][$option_id]['variants'][$variant_id]['images'];
                        $product['main_pair'] = reset($tmp);
                        unset($tmp[key($tmp)]);
                        $product['image_pairs'] = $tmp;
                        break;
                    }
                }
            }
        }
    }
}

function fn_development_get_product_features_list_post(&$features_list, $product, $display_on, $lang_code)
{
    if (!empty($features_list[BRAND_FEATURE_ID]['variants'])) {
        $image_pairs = fn_get_image_pairs(array_keys($features_list[BRAND_FEATURE_ID]['variants']), 'feature_variant', 'V', true, true, CART_LANGUAGE);
        foreach ($features_list[BRAND_FEATURE_ID]['variants'] as $variant_id => $variant) {
            $features_list[BRAND_FEATURE_ID]['variants'][$variant_id]['image_pair'] = array_pop($image_pairs[$variant_id]);
        }
    }
}

function fn_development_update_product_filter(&$filter_data, $filter_id, $lang_code)
{
    if (!empty($filter_data['feature_type']) && $filter_data['feature_type'] == 'N') {
        if (!empty($filter_data['is_slider']) && $filter_data['is_slider'] != 'Y') {
            db_query("UPDATE ?:product_filters SET field_type = '' WHERE filter_id = ?i", $filter_id);
        } else {
            $field_type = db_get_field("SELECT field_type FROM ?:product_filters WHERE filter_id = ?i", $filter_id);
            if (empty($field_type)) {
                $existing_codes = db_get_fields('SELECT field_type FROM ?:product_filters GROUP BY field_type');
                $existing_codes[] = 'P';
                $existing_codes[] = 'A';
                $existing_codes[] = 'F';
                $existing_codes[] = 'V';
                $existing_codes[] = 'R';
                $existing_codes[] = 'B';
                $codes = array_diff(range('A', 'Z'), $existing_codes);
                $field_type = reset($codes);
                db_query("UPDATE ?:product_filters SET field_type = ?s WHERE filter_id = ?i", $field_type, $filter_id);
            }
        }
    }
}

function fn_development_add_range_to_url_hash_pre(&$hash, $range, $field_type)
{
    $fields = fn_get_product_filter_fields();
    foreach ($fields as $i => $fld) {
        if ($field_type == $i && !empty($fld['slider']) && !in_array($i, array('P', 'A'))) {
            $pattern = '/(' . $i . '\d+-\d+\.?)|(\.?' . $i . '\d+-\d+)/';
            $hash = preg_replace($pattern, '', $hash);          
        }
    }

}

function fn_development_get_product_filter_fields(&$filters)
{
    static $fields;
    
    if (!isset($fields)) {
        $fields = db_get_array("SELECT ?:product_filters.field_type, ?:product_filter_descriptions.filter, ?:product_filters.feature_id FROM ?:product_filters LEFT JOIN ?:product_features ON ?:product_features.feature_id = ?:product_filters.feature_id LEFT JOIN ?:product_filter_descriptions ON ?:product_filter_descriptions.filter_id = ?:product_filters.filter_id AND ?:product_filter_descriptions.lang_code = ?s WHERE ?:product_filters.is_slider = 'Y' AND ?:product_features.feature_type = 'N' AND ?:product_filters.field_type != ''", CART_LANGUAGE);
    }
    
    if (!empty($fields)) {
        foreach ($fields as $i => $field) {
            $filters[$field['field_type']] = array(
                'description' => $field['filter'],
                'feature_id' => $field['feature_id'],
                'condition_type' => 'S',
                'slider' => true,
            );
        }
    }
}

function fn_development_get_filter_range_name_post(&$range_name, $range_type, $range_id)
{
    if (!in_array($range_type, array('P', 'A', 'V', 'R'))) {
        $from = fn_strtolower(__('range_from'));
        $to = fn_strtolower(__('range_to'));
        $fields = fn_get_product_filter_fields();
        $data = explode('-', $range_id);
        $from_val = !empty($data[0]) ? $data[0] : 0;
        $to_val = !empty($data[1]) ? $data[1] : 0;
        $range_name = $fields[$range_type]['description'] . " : $from $from_val $to $to_val";
    }
}

function fn_development_get_filters_products_count_before_select_filters(&$sf_fields, &$sf_join, &$condition, $sf_sorting, $params)
{
    $sf_fields .= db_quote(", ?:product_filters.is_slider, ?:product_filters.units, ?:product_filters.note_url, ?:product_filters.note_text");
}

function fn_development_update_category_pre(&$category_data, $category_id, $lang_code)
{
    if (!empty($category_data['sections_categorization'])) {
        $category_data['sections_categorization'] = serialize($category_data['sections_categorization']);
    }
    if (!empty($category_data['cross_categories'])) {
        $category_data['cross_categories'] = serialize($category_data['cross_categories']);
    }
}

function fn_development_get_products(&$params, &$fields, &$sortings, &$condition, &$join, $sorting, $group_by, $lang_code, $having)
{
    if (!empty($params['tabs_categorization']) || !empty($params['subtabs_categorization']) || !empty($params['sections_categorization'])) {
        $params['items_per_page'] = 0;
        if (!empty($params['tabs_categorization'])) {
            $join .= db_quote(" LEFT JOIN ?:product_features_values AS tabs_categorization ON tabs_categorization.product_id = products.product_id AND tabs_categorization.feature_id = ?i", $params['tabs_categorization']);
            $fields[] = 'tabs_categorization.variant_id AS tabs_categorization';
        }
        if (!empty($params['subtabs_categorization'])) {
            $join .= db_quote(" LEFT JOIN ?:product_features_values AS subtabs_categorization ON subtabs_categorization.product_id = products.product_id AND subtabs_categorization.feature_id = ?i", $params['subtabs_categorization']);
            $fields[] = 'subtabs_categorization.variant_id AS subtabs_categorization';
        }
        if (!empty($params['sections_categorization'])) {
            $join .= db_quote(" LEFT JOIN ?:product_features_values AS sections_categorization ON sections_categorization.product_id = products.product_id AND sections_categorization.feature_id IN (?n)", $params['sections_categorization']);
            $fields[] = "GROUP_CONCAT(',', sections_categorization.variant_id) AS sections_categorization";
        }
    }
    if (!empty($params['similar_pid'])) {
        $similar_products_features = array(
            'R' => array(
                array('34' => 'self', '33' => '939'),
                array(R_BALANCE_FEATURE_ID => 'self', R_LENGTH_FEATURE_ID => 'self', R_HEADSIZE_FEATURE_ID => 'self', R_WEIGHT_FEATURE_ID => 'self', R_STIFFNESS_FEATURE_ID => 'self')
            ),
            'A' => array(
                array('52' => 'self', '50' => 'self')
            ),
            'S' => array(
                array('54' => 'self')
            ),
            'B' => array(
                array('84' => 'self', '58' => '940'),
                array('58' => 'self')
            ),
            'ST' => array(
                array('77' => 'self', '60' => 'self')
            ),
            'BL' => array(
                array('64' => 'self')
            ),
            'OG' => array(
                array('66' => 'self')
            ),
            'BG' => array(
                array('72' => 'self')
            ),
            'PC' => array(
                array('34' => 'self'),
                array('84' => 'self')
            ),
        );
        $digit_features = array(R_WEIGHT_FEATURE_ID, R_STIFFNESS_FEATURE_ID, R_BALANCE_FEATURE_ID);
        if (!empty($_SESSION['category_type']) && !empty($similar_products_features[$_SESSION['category_type']]) && !empty($_SESSION['product_features'])) {
            foreach ($similar_products_features[$_SESSION['category_type']] as $i => $ors) {
                $feature_condition = array();
                foreach ($ors as $feature_id => $val) {
                    if (!empty($_SESSION['product_features'][$feature_id])) {
                        if (!empty($_SESSION['product_features'][$feature_id]['variants'])) {
                            foreach ($_SESSION['product_features'][$feature_id]['variants'] as $j => $variant) {
                                if (!empty($variant['variant_id'])) {
                                    $feature_condition[$feature_id]['variants'][] = array(
                                        'variant_id' => ($val == 'self') ? $variant['variant_id'] : $val,
                                    );
                                }
                            }
                        } else {
                            if (!in_array($feature_id, $digit_features)) {
                                if (!empty($_SESSION['product_features'][$feature_id]['variant_id'])) {
                                    $feature_condition[$feature_id] = array(
                                        'variant_id' => ($val == 'self') ? $_SESSION['product_features'][$feature_id]['variant_id'] : $val,
                                    );
                                } elseif (!empty($_SESSION['product_features'][$feature_id]['value'])) {
                                    $feature_condition[$feature_id] = array(
                                        'value' => ($val == 'self') ? $_SESSION['product_features'][$feature_id]['value'] : $val,
                                    );
                                }
                            } else {
                                if ($feature_id == R_WEIGHT_FEATURE_ID) {
                                    $margin_value = 5;
                                }
                                if ($feature_id == R_STIFFNESS_FEATURE_ID) {
                                    $margin_value = 2;
                                }
                                if ($feature_id == R_BALANCE_FEATURE_ID) {
                                    $margin_value = 0.32;
                                }
                                $feature_condition[$feature_id] = array(
                                    'min_value' => ($val == 'self') ? $_SESSION['product_features'][$feature_id]['variant_name'] - $margin_value : $val,
                                    'max_value' => ($val == 'self') ? $_SESSION['product_features'][$feature_id]['variant_name'] + $margin_value : $val
                                );
                            }
                        }
                    } elseif (!empty($_SESSION['product_category'])) {
                        $condition .= db_quote(" AND ?:categories.category_id = ?i", $_SESSION['product_category']);
                    } else {
                        $condition .= " AND NULL";
                    }
                }
                $params['features_condition'][] = $feature_condition;
            }
        } elseif (!empty($_SESSION['product_category'])) {
            $condition .= db_quote(" AND ?:categories.category_id = ?i", $_SESSION['product_category']);
        } else {
            $condition .= " AND NULL";
        }
    }
    if (!empty($params['same_brand_pid'])) {
        if (!empty($_SESSION['product_features'][BRAND_FEATURE_ID])) {
            $params['features_condition'][] = array(
                BRAND_FEATURE_ID => array(
                    'variant_id' => $_SESSION['product_features'][BRAND_FEATURE_ID]['variant_id']
                )
            );
        } else {
            $condition .= " AND NULL";
        }
    }
    if (!empty($params['features_condition'])) {
        FeaturesCache::getProductsConditions($params['features_condition'], $join, $condition, $lang_code);
    }
}

function fn_development_get_products_pre(&$params, $items_per_page, $lang_code)
{
    if (!empty($params['shoes_surface'])) {
        $params['cid'] = SHOES_CATEGORY_ID;
        $params['subcats'] = 'Y';
        $feature_hash = 'V' . ALLCOURT_SURFACE_FV_ID;
        if ($params['shoes_surface'] == 'clay') {
            $feature_hash .= '.V' . CLAY_SURFACE_FV_ID;
        }
        if ($params['shoes_surface'] == 'grass') {
            $feature_hash .= '.V' . GRASS_SURFACE_FV_ID;
        }
        $params['features_hash'] = (!empty($params['features_hash']) ? '.' : '') . $feature_hash;
    }
    if (!empty($params['rackets_type'])) {
        $params['cid'] = RACKETS_CATEGORY_ID;
        $params['subcats'] = 'Y';
        if ($params['rackets_type'] == 'power') {
            $feature_hash = 'V' . POWER_RACKET_FV_ID;
        }
        if ($params['rackets_type'] == 'club') {
            $feature_hash = 'V' . CLUB_RACKET_FV_ID;
        }
        if ($params['rackets_type'] == 'pro') {
            $feature_hash = 'V' . PRO_RACKET_FV_ID;
        }
        $feature_condition = array();
        if ($params['rackets_type'] == 'heavy_head_light') {
            $feature_condition[R_WEIGHT_FEATURE_ID] = array(
                'min_value' => 300
            );
            $feature_condition[R_BALANCE_FEATURE_ID] = array(
                'max_value' => 35
            );
        }
        if ($params['rackets_type'] == 'light_head_heavy') {
            $feature_condition[R_WEIGHT_FEATURE_ID] = array(
                'max_value' => 300
            );
            $feature_condition[R_BALANCE_FEATURE_ID] = array(
                'min_value' => 35
            );
        }
        if ($params['rackets_type'] == 'stiff') {
            $feature_condition[R_STIFFNESS_FEATURE_ID] = array(
                'min_value' => 65
            );
        }
        if ($params['rackets_type'] == 'soft') {
            $feature_condition[R_STIFFNESS_FEATURE_ID] = array(
                'max_value' => 64
            );
        }
        if ($params['rackets_type'] == 'regular_head') {
            $feature_condition[R_HEADSIZE_FEATURE_ID] = array(
                'min_value' => 612,
                'max_value' => 677
            );
        }
        if ($params['rackets_type'] == 'regular_length') {
            $feature_condition[R_LENGTH_FEATURE_ID] = array(
                'variant_id' => REGULAR_LENGTH_FV_ID
            );
        }
        if ($params['rackets_type'] == 'kids_17') {
            $feature_condition[R_LENGTH_FEATURE_ID] = array(
                'variant_id' => KIDS_17_FV_ID
            );
        }
        if ($params['rackets_type'] == 'kids_19') {
            $feature_condition[R_LENGTH_FEATURE_ID] = array(
                'variant_id' => KIDS_19_FV_ID
            );
        }
        if ($params['rackets_type'] == 'kids_21') {
            $feature_condition[R_LENGTH_FEATURE_ID] = array(
                'variant_id' => KIDS_21_FV_ID
            );
        }
        if ($params['rackets_type'] == 'kids_23') {
            $feature_condition[R_LENGTH_FEATURE_ID] = array(
                'variant_id' => KIDS_23_FV_ID
            );
        }
        if ($params['rackets_type'] == 'kids_25') {
            $feature_condition[R_LENGTH_FEATURE_ID] = array(
                'variant_id' => KIDS_25_FV_ID
            );
        }
        if ($params['rackets_type'] == 'kids_26') {
            $feature_condition[R_LENGTH_FEATURE_ID] = array(
                'variant_id' => KIDS_26_FV_ID
            );
        }
        if ($params['rackets_type'] == 'closed_pattern') {
            $feature_condition[R_STRING_PATTERN_FEATURE_ID] = array(
                'variant_id' => CLOSED_PATTERN_FV_ID
            );
        }
        if ($params['rackets_type'] == 'open_pattern') {
            $feature_condition[R_STRING_PATTERN_FEATURE_ID] = array(
                'not_variant' => CLOSED_PATTERN_FV_ID
            );
        }
        if (!empty($feature_condition)) {
            $params['features_condition'][] = $feature_condition;
        }
        if (!empty($feature_hash)) {
            $params['features_hash'] = (!empty($params['features_hash']) ? '.' : '') . $feature_hash;
        }
    }
    if (!empty($params['strings_type'])) {
        $params['cid'] = STRINGS_CATEGORY_ID;
        $params['subcats'] = 'Y';
        if ($params['strings_type'] == 'natural_gut') {
            $params['cid'] = NATURAL_GUT_MATERIAL_CATEGORY_ID;
        }
        if ($params['strings_type'] == 'nylon') {
            $params['cid'] = NYLON_MATERIAL_CATEGORY_ID;
        }
        if ($params['strings_type'] == 'polyester') {
            $params['cid'] = POLYESTER_MATERIAL_CATEGORY_ID;
        }
        if ($params['strings_type'] == 'hybrid') {
            $params['cid'] = HYBRID_MATERIAL_CATEGORY_ID;
        }
        if ($params['strings_type'] == 'monofil') {
            $params['cid'] = MONO_STRUCTURE_CATEGORY_ID;
        }
        if ($params['strings_type'] == 'multifil') {
            $params['cid'] = MULTI_STRUCTURE_CATEGORY_ID;
        }
        if ($params['strings_type'] == 'textured') {
            $params['cid'] = TEXTURED_STRUCTURE_CATEGORY_ID;
        }
        if ($params['strings_type'] == 'synthetic_gut') {
            $params['cid'] = SYNTH_GUT_STRUCTURE_CATEGORY_ID;
        }
        if (!empty($feature_hash)) {
            $params['features_hash'] = (!empty($params['features_hash']) ? '.' : '') . $feature_hash;
        }
    }
}

function fn_development_delete_product_post($product_id, $product_deleted)
{
    if ($product_deleted) {
        db_query("DELETE FROM ?:players_gear WHERE product_id = ?i", $product_id);
        db_query("DELETE FROM ?:product_technologies WHERE product_id = ?i", $product_id);
        FeaturesCache::deleteProduct($product_id);
    }
}

function fn_development_update_product_post($product_data, $product_id, $lang_code, $create)
{
    if (isset($product_data['players'])) {
        if ($create) {
            $existing_gear = array();
        } else {
            $existing_gear = db_get_fields("SELECT player_id FROM ?:players_gear WHERE product_id = ?i", $product_id);
        }
        $product_data['players'] = (empty($product_data['players'])) ? array() : explode(',', $product_data['players']);
        $to_delete = array_diff($existing_gear, $product_data['players']);

        if (!empty($to_delete)) {
            db_query("DELETE FROM ?:players_gear WHERE player_id IN (?n) AND product_id = ?i", $to_delete, $product_id);
        }
        $to_add = array_diff($product_data['players'], $existing_gear);

        if (!empty($to_add)) {
            foreach ($to_add as $i => $gr) {
                $__data = array(
                    'product_id' => $product_id,
                    'player_id' => $gr
                );
                db_query("REPLACE INTO ?:players_gear ?e", $__data);
            }
        }
    }
    
    if (isset($product_data['technologies'])) {
        if ($create) {
            $existing_products = array();
        } else {
            $existing_products = db_get_fields("SELECT technology_id FROM ?:product_technologies WHERE product_id = ?i", $product_id);
        }
        $product_data['technologies'] = (empty($product_data['technologies'])) ? array() : explode(',', $product_data['technologies']);
        $to_delete = array_diff($existing_products, $product_data['technologies']);

        if (!empty($to_delete)) {
            db_query("DELETE FROM ?:product_technologies WHERE technology_id IN (?n) AND product_id = ?i", $to_delete, $product_id);
        }
        $to_add = array_diff($product_data['technologies'], $existing_products);

        if (!empty($to_add)) {
            foreach ($to_add as $i => $gr) {
                $__data = array(
                    'product_id' => $product_id,
                    'technology_id' => $gr
                );
                db_query("REPLACE INTO ?:product_technologies ?e", $__data);
            }
        }
    }
    
    // Generate thumbail to speed up the first loading of the products list
    $main_pair = fn_get_image_pairs($product_id, 'product', 'M', true, true, $lang_code);
    if (!empty($main_pair)) {
        fn_image_to_display($main_pair, Registry::get('settings.Thumbnails.product_lists_thumbnail_width'), Registry::get('settings.Thumbnails.product_lists_thumbnail_height'));
    }
}

function fn_development_update_product_pre(&$product_data, $product_id, $lang_code, $can_update)
{
    if (!empty($product_data['main_category'])) { // to exclude products list update
        $id_path = explode('/', db_get_field("SELECT id_path FROM ?:categories WHERE category_id = ?i", $product_data['main_category']));
        $enable_discussion = array('254', '263', '265', '266', '312', '313');
        $intersection = array_intersect($id_path, $enable_discussion);
        if (!empty($intersection) && $product_data['product_type'] == 'P') {
            $product_data['discussion_type'] = 'B';
        } else {
            $product_data['discussion_type'] = 'D';
        }
        
        $players = (empty($product_data['players'])) ? array() : explode(',', $product_data['players']);
        $variant_ids = db_get_fields("SELECT feature_variant_id FROM ?:players WHERE player_id IN (?n)", $players);
        $product_data['product_features'][PLAYER_FEATURE_ID] = array_combine($variant_ids, $variant_ids);
        
        $global_data = fn_get_product_global_data($product_data, array('shipping_weight', 'margin', 'net_currency_code'));
        $product_data['global_margin'] = $global_data['margin'];
        $product_data['global_net_currency_code'] = $global_data['net_currency_code'];
        if (empty($product_data['net_currency_code']) && !empty($global_data['net_currency_code'])) {
            $product_data['net_currency_code'] = $global_data['net_currency_code'];
        }
        if (empty($product_data['margin']) || $product_data['margin'] == 0) {
            fn_get_product_margin($product_data);
        }

        $old_data = db_get_row("SELECT auto_price, margin, net_cost, net_currency_code FROM ?:products WHERE product_id = ?i", $product_id);
        if (!empty($product_data['auto_price']) && $product_data['auto_price'] == 'Y' && $product_data['net_cost'] > 0 && $product_data['margin'] > 0 && !empty($product_data['net_currency_code']) && (empty($old_data['auto_price']) || $product_data['auto_price'] != $old_data['auto_price'] || $product_data['margin'] != $old_data['margin'] || $product_data['net_cost'] != $old_data['net_cost'] || $product_data['net_currency_code'] != $old_data['net_currency_code'])) {
            $base_price = fn_calculate_base_price($product_data);
            $product_data['price'] = fn_round_price($base_price);
            if (!empty($product_data['prices'])) {
                foreach ($product_data['prices'] as $i => $p_data) {
                    if (!empty($p_data['lower_limit'])) {
                        if ($p_data['lower_limit'] == 1) {
                            $product_data['prices'][$i]['price'] = fn_round_price($base_price);
                        } elseif ($p_data['type'] == 'A') {
                            $product_data['prices'][$i]['price'] = fn_round_price($base_price - $base_price * RACKETS_QTY_DSC_PRC / 100);
                        }
                    }
                }
            }
        }
        
        if ($product_data['weight'] == 0 && !empty($global_data['shipping_weight'])) {
            $product_data['weight'] = $global_data['shipping_weight'];
        }
    }
}

function fn_development_update_category_post($category_data, $category_id, $lang_code)
{
    if (!empty($category_data['override_shipping_weight']) && $category_data['override_shipping_weight'] == 'Y') {
        $products = array();
        $_params = array (
            'cid' => $category_id,
            'subcats' => 'Y'
        );
        list($prods,) = fn_get_products($_params);
        if (!empty($prods)) {
            foreach ($prods as $i => $prod) {
                $products[] = $prod['product_id'];
            }
        }
        if (!empty($products)) {
            db_query("UPDATE ?:products SET weight = ?d WHERE product_id IN (?n)", $category_data['shipping_weight'], $products);
        }
    }
    if (!empty($category_data['recalculate_margins']) && $category_data['recalculate_margins'] == 'Y') {
        $products = array();
        $_params = array (
            'cid' => $category_id,
            'subcats' => 'Y'
        );
        list($prods,) = fn_get_products($_params);
        if (!empty($prods)) {
            foreach ($prods as $i => $prod) {
                if ($prod['auto_price'] == 'Y' && $prod['net_cost'] > 0) {
                    unset($prod['margin']);
                    $products[$prod['product_id']] = $prod;
                }
            }
        }
        if (!empty($products)) {
            fn_process_update_prices($products);
        }
    }
}

function fn_development_get_product_data_post(&$product_data, $auth, $preview, $lang_code)
{
    if (AREA == 'A') {
        $plain = true;
    } else {
        $plain = false;
    }
    list($players, ) = fn_get_players(array('product_id' => $product_data['product_id'], 'plain' => $plain));
    list($technologies, ) = fn_get_technologies(array('product_id' => $product_data['product_id'], 'plain' => $plain));
    if (AREA == 'A') {
        $product_data['players'] = implode(',', array_keys($players));
        $product_data['technologies'] = implode(',', array_keys($technologies));
    } else {
        $product_data['players'] = $players;
        $product_data['technologies'] = $technologies;
    }
    $types_ids = fn_get_categories_types($product_data['main_category']);
    $product_data['id_path'] = db_get_field("SELECT id_path FROM ?:categories WHERE category_id = ?i", $product_data['main_category']);
    $product_data['type'] = fn_identify_category_type($product_data['id_path']);
    $product_data['category_type'] = ($product_data['product_type'] == 'C') ? 'PC' : fn_get_category_type($types_ids[$product_data['main_category']]);
    if (!in_array($product_data['category_type'], array('A', 'S'))) {
        $product_data['offer_help'] = true;
    }
    $product_data['category_main_id'] = $types_ids[$product_data['main_category']];
}

function fn_development_get_products_post(&$products, $params, $lang_code)
{
    if (!empty($products)) {
        $main_ids = array();
        foreach ($products as $i => $product) {
            $main_ids = array_merge($main_ids, $product['category_ids']);
        }
        $id_paths = db_get_hash_single_array("SELECT category_id, id_path FROM ?:categories WHERE category_id IN (?n)", array('category_id', 'id_path'), array_unique($main_ids));
        foreach ($products as $i => $product) {
            $products[$i]['id_path'] = $id_paths[$product['main_category']];
            foreach ($product['category_ids'] as $j => $cat_id) {
                $products[$i]['all_path'][$cat_id] = $id_paths[$cat_id];
            }
            $products[$i]['type'] = fn_identify_category_type($products[$i]['id_path']);
        }
    }
    if (!empty($params['shuffle']) && $params['shuffle'] == 'Y') {
        shuffle($products);
    }
}

function fn_development_is_shared_product_pre($product_id, $company_id, &$return)
{
    $return = 'N';
}

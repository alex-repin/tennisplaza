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

if (!defined('BOOTSTRAP')) { die('Access denied'); }

define('MAIN_THEME_NAME', 'responsive');
define('MOBILE_THEME_NAME', 'tennishouse_mobile');

fn_register_hooks(
    'get_categories',
    'top_menu_form',
    'get_products',
    
    'get_lang_var_post',
    'get_products_pre',
    'gather_additional_product_data_post',
    'get_products_post',
    'get_filters_products_count_before_select_filters',
    'get_filter_range_name_post',
    'get_product_filter_fields',
    'calculate_cart_items',
    'get_filters_products_count_pre',
    'update_product_pre',
    'add_range_to_url_hash_pre',
    'update_product_filter',
    'get_product_features_list_post',
    'update_product_post',
    'get_product_data_post',
    'delete_product_post',
    'get_categories_post',
    'get_product_feature_variants',
    'get_category_data_post',
    'update_product_features_value',
    'delete_product_feature_variants',
    'redirect_complete',
    'get_product_features_list_before_select',
    'update_category_post',
    'get_product_option_data_pre',
    'update_product_option_post',
    'get_product_option_data_post',
    'get_product_options_post',
    'get_product_options',
    'update_shipping',
    'prepare_checkout_payment_methods',
    'shippings_get_shippings_list_post',
    'shippings_get_shippings_list_conditions',
    'delete_product_option_post',
    'clone_product_options_post',
    'validate_sef_object',
    'cron_routine',
    'get_filters_products_count_before_select',
    'gather_additional_products_data_post',
    'is_shared_product_pre',
    'apply_option_modifiers_pre',
    'seo_is_indexed_page',
    'get_selected_product_options_post',
    'update_category_pre',
    'update_page_post',
    'get_page_data',
    'pre_get_cart_product_data',
    'calculate_cart_post',
    'get_cart_product_data_post',
    'get_order_info',
    'pre_get_orders',
    'get_product_feature_data_before_select',
    'get_product_features',
    'delete_feature_post',
    'pre_place_order',
    'update_payment_surcharge',
    'change_order_status_post',
    'update_user_pre',
    'get_user_info',
    'get_status_params_definition',
    'order_notification',
    'get_notification_rules',
    'render_block_content_pre'
);
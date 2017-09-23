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

$scheme['conditions']['warehouses'] = array(
    'operators' => array ('eq', 'neq'),
    'type' => 'select',
    'variants_function' => array('fn_get_simple_warehouses'),
    'field_function' => array('fn_promotions_check_warehouses', '#this', '@product'),
    'zones' => array('catalog')
);
$scheme['conditions']['promo_codes'] = array(
    'type' => 'list',
    'field_function' => array('fn_promotion_validate_promo_code', '#this', '@cart', '#id'),
    'zones' => array('cart'),
    'applicability' => array( // applicable for "positive" groups only
        'group' => array(
            'set_value' => true
        ),
    ),
);

return $scheme;

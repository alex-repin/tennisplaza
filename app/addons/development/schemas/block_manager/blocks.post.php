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

$schema['products']['content']['items']['fillings']['similar_products']['params'] = array (
    'sort_by' => 'price',
    'sort_order' => 'desc',
    'request' => array (
        'similar_pid' => '%PRODUCT_ID%',
        'exclude_pid' => '%PRODUCT_ID%'
    ),
    'shuffle' => 'Y',
    'limit' => 10,
);
$schema['products']['content']['items']['fillings']['same_brand_products']['params'] = array (
    'sort_by' => 'price',
    'sort_order' => 'desc',
    'request' => array (
        'same_brand_pid' => '%PRODUCT_ID%',
        'exclude_pid' => '%PRODUCT_ID%'
    ),
    'shuffle' => 'Y',
    'limit' => 10,
);
$schema['pages']['content']['items']['fillings']['dynamic_content'] = array (
    'params' => array (
        'status' => 'A',
        'request' => array (
            'parent_id' => '%PAGE_ID%'
        ),
    ),
);
$schema['products']['settings']['all_items_url'] = array (
    'type' => 'input',
    'default_value' => ''
);
$schema['products']['content']['items']['fillings']['allcourt_shoes']['params'] = array (
    'sort_by' => 'price',
    'sort_order' => 'desc',
    'shoes_surface' => 'allcourt',
    'shuffle' => 'Y',
    'limit' => 10,
);
$schema['products']['content']['items']['fillings']['shoes_for_clay']['params'] = array (
    'sort_by' => 'price',
    'sort_order' => 'desc',
    'shoes_surface' => 'clay',
    'shuffle' => 'Y',
    'limit' => 10,
);
$schema['products']['content']['items']['fillings']['shoes_for_grass']['params'] = array (
    'sort_by' => 'price',
    'sort_order' => 'desc',
    'shoes_surface' => 'grass',
    'shuffle' => 'Y',
    'limit' => 10,
);
$schema['products']['content']['items']['fillings']['power_rackets']['params'] = array (
    'sort_by' => 'price',
    'sort_order' => 'desc',
    'rackets_type' => 'power',
    'shuffle' => 'Y',
    'limit' => 10,
);
$schema['products']['content']['items']['fillings']['club_rackets']['params'] = array (
    'sort_by' => 'price',
    'sort_order' => 'desc',
    'rackets_type' => 'club',
    'shuffle' => 'Y',
    'limit' => 10,
);
$schema['products']['content']['items']['fillings']['pro_rackets']['params'] = array (
    'sort_by' => 'price',
    'sort_order' => 'desc',
    'rackets_type' => 'pro',
    'shuffle' => 'Y',
    'limit' => 10,
);
$schema['products']['content']['items']['fillings']['heavy_head_light_rackets']['params'] = array (
    'sort_by' => 'price',
    'sort_order' => 'desc',
    'rackets_type' => 'heavy_head_light',
    'shuffle' => 'Y',
    'limit' => 10,
);
$schema['products']['content']['items']['fillings']['light_head_heavy_rackets']['params'] = array (
    'sort_by' => 'price',
    'sort_order' => 'desc',
    'rackets_type' => 'light_head_heavy',
    'shuffle' => 'Y',
    'limit' => 10,
);
$schema['products']['content']['items']['fillings']['stiff_rackets']['params'] = array (
    'sort_by' => 'price',
    'sort_order' => 'desc',
    'rackets_type' => 'stiff',
    'shuffle' => 'Y',
    'limit' => 10,
);
$schema['products']['content']['items']['fillings']['soft_rackets']['params'] = array (
    'sort_by' => 'price',
    'sort_order' => 'desc',
    'rackets_type' => 'soft',
    'shuffle' => 'Y',
    'limit' => 10,
);
$schema['products']['content']['items']['fillings']['regular_head_rackets']['params'] = array (
    'sort_by' => 'price',
    'sort_order' => 'desc',
    'rackets_type' => 'regular_head',
    'shuffle' => 'Y',
    'limit' => 10,
);
$schema['products']['content']['items']['fillings']['regular_length_rackets']['params'] = array (
    'sort_by' => 'price',
    'sort_order' => 'desc',
    'rackets_type' => 'regular_length',
    'shuffle' => 'Y',
    'limit' => 10,
);
$schema['products']['content']['items']['fillings']['closed_pattern_rackets']['params'] = array (
    'sort_by' => 'price',
    'sort_order' => 'desc',
    'rackets_type' => 'closed_pattern',
    'shuffle' => 'Y',
    'limit' => 10,
);
$schema['products']['content']['items']['fillings']['open_pattern_rackets']['params'] = array (
    'sort_by' => 'price',
    'sort_order' => 'desc',
    'rackets_type' => 'open_pattern',
    'shuffle' => 'Y',
    'limit' => 10,
);
$schema['products']['content']['items']['fillings']['natural_gut_strings']['params'] = array (
    'sort_by' => 'price',
    'sort_order' => 'desc',
    'strings_type' => 'natural_gut',
    'shuffle' => 'Y',
    'limit' => 10,
);
$schema['products']['content']['items']['fillings']['nylon_strings']['params'] = array (
    'sort_by' => 'price',
    'sort_order' => 'desc',
    'strings_type' => 'nylon',
    'shuffle' => 'Y',
    'limit' => 10,
);
$schema['products']['content']['items']['fillings']['polyester_strings']['params'] = array (
    'sort_by' => 'price',
    'sort_order' => 'desc',
    'strings_type' => 'polyester',
    'shuffle' => 'Y',
    'limit' => 10,
);
$schema['products']['content']['items']['fillings']['hybrid_strings']['params'] = array (
    'sort_by' => 'price',
    'sort_order' => 'desc',
    'strings_type' => 'hybrid',
    'shuffle' => 'Y',
    'limit' => 10,
);
$schema['products']['content']['items']['fillings']['monofil_strings']['params'] = array (
    'sort_by' => 'price',
    'sort_order' => 'desc',
    'strings_type' => 'monofil',
    'shuffle' => 'Y',
    'limit' => 10,
);
$schema['products']['content']['items']['fillings']['multifil_strings']['params'] = array (
    'sort_by' => 'price',
    'sort_order' => 'desc',
    'strings_type' => 'multifil',
    'shuffle' => 'Y',
    'limit' => 10,
);
$schema['products']['content']['items']['fillings']['textured_strings']['params'] = array (
    'sort_by' => 'price',
    'sort_order' => 'desc',
    'strings_type' => 'textured',
    'shuffle' => 'Y',
    'limit' => 10,
);
$schema['products']['content']['items']['fillings']['synthetic_gut_strings']['params'] = array (
    'sort_by' => 'price',
    'sort_order' => 'desc',
    'strings_type' => 'synthetic_gut',
    'shuffle' => 'Y',
    'limit' => 10,
);
$schema['products']['content']['items']['fillings']['cross_sales']['params'] = array (
    'items_function' => 'fn_get_cross_sales',
    'limit' => 10,
);

return $schema;

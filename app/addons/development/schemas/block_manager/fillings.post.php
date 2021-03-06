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

$schema['discounted_products'] = array (
    'products_limit' => array (
        'type' => 'input',
        'default_value' => 10,
    ),
    'cid' => array (
        'type' => 'picker',
        'option_name' => 'filter_by_categories',
        'picker' => 'pickers/categories/picker.tpl',
        'picker_params' => array(
                'multiple' => true,
                'use_keys' => 'N',
                'view_mode' => 'table',
        ),
        'unset_empty' => true, // remove this parameter from params list if the value is empty
    ),
);

return $schema;

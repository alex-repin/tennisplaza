<div id="category_products_{$block.block_id}">

{*if ($category_data.description || $runtime.customization_mode.live_editor) && !$category_data.category_id|fn_display_subheaders && !$category_data.parent_id}
    <div class="ty-wysiwyg-content ty-mb-s" {live_edit name="category:description:{$category_data.category_id}"}>{$category_data.description nofilter}</div>
{/if*}

{if $subcategories && !$category_data.category_id|fn_display_subheaders && !$category_data.parent_id}
    {math equation="ceil(n/c)" assign="rows" n=$subcategories|count c=$columns|default:"2"}
    {split data=$subcategories size=$rows assign="splitted_subcategories"}
    <ul class="subcategories clearfix">
    {foreach from=$splitted_subcategories item="ssubcateg"}
        <div class="subcategories-column">
        {foreach from=$ssubcateg item=category name="ssubcateg"}
            {if $category}
                <li class="ty-subcategories__item">
                    <a href="{"categories.view?category_id=`$category.category_id`"|fn_url}">
                    {if $category.main_pair}
                        {include file="common/image.tpl"
                            show_detailed_link=false
                            images=$category.main_pair
                            no_ids=true
                            image_id="category_image"
                            image_width=$settings.Thumbnails.category_lists_thumbnail_width
                            image_height=$settings.Thumbnails.category_lists_thumbnail_height
                            class="ty-subcategories-img"
                        }
                    {/if}
                    <span {live_edit name="category:category:{$category.category_id}"}>{$category.category}</span>
                    </a>
                </li>
            {/if}
        {/foreach}
        </div>
    {/foreach}
    </ul>
{/if}

{if $smarty.request.advanced_filter}
    {include file="views/products/components/product_filters_advanced_form.tpl" separate_form=true}
{/if}

{if $products}
{assign var="layouts" value=""|fn_get_products_views:false:0}
{if $category_data.product_columns}
    {assign var="product_columns" value=$category_data.product_columns}
{else}
    {assign var="product_columns" value=$settings.Appearance.columns_in_products_list}
{/if}

{if $layouts.$selected_layout.template}
    {include file="`$layouts.$selected_layout.template`" columns=$product_columns}
{/if}

{elseif !$subcategories || $show_no_products_block}
<p class="ty-no-items cm-pagination-container">{__("text_no_products")}</p>
{else}
<div class="cm-pagination-container"></div>
{/if}
<!--category_products_{$block.block_id}--></div>

{if !$image_title}
    {capture name="mainbox_title"}
    {if $category_data.brand.image_pair.icon.image_path}
        <div class="ty-category__title">
            <div class="ty-category__title-logo">
                {if $category.brand_id == $smarty.const.KIRSCHBAUM_BRAND_ID}
                    {$img_height = "70"}
                {else}
                    {$img_height = "50"}
                {/if}
                {include file="addons/development/common/brand_logo.tpl"  brand=$category_data.brand brand_variant_id=$category_data.brand_id img_height=$img_height}
            </div>
            {if $category_data.description}
                <div class="ty-wysiwyg-content ty-category__title-descr" {live_edit name="category:description:{$category_data.category_id}"}>{$category_data.description nofilter}</div>
            {/if}
        </div>
    {else}
        <span {live_edit name="category:category:{$category_data.category_id}"}>{$category_data.category}</span>
    {/if}
    {/capture}
{/if}

{if $addons.call_requests.buy_now_with_one_click == "Y" && !$hide_call_request}
    {$id = "call_request"}
    <div class="ty-call-request-button"><a id="opener_{$id}_{$obj_prefix}{$obj_id}" class="cm-dialog-opener cm-dialog-auto-size ty-btn ty-btn__text" data-ca-target-id="content_{$id}" data-ca-call-request-id="call_request_{$obj_prefix}{$obj_id}">{__("call_requests.quick_order")}</a></div>
    <div id="call_request_{$obj_prefix}{$obj_id}" class="hidden">
        <input type="hidden" name="product_data[{$product.product_id}][product_id]" value="{$product.product_id}" />
        <input type="hidden" name="product_data[{$product.product_id}][amount]" value="1" />
        {if $product.selected_options}
            {foreach from=$product.selected_options key="option_id" item="variant_id"}
                <input type="hidden" name="product_data[{$product.product_id}][product_options][{$option_id}]" value="{$variant_id}" />
            {/foreach}
        {/if}
        <div class="ty-cr-product-info-container">
            <div class="ty-cr-product-info-image">
                {include file="addons/development/common/load_image.tpl" el_id="product_cr_main_image_`$obj_id`_`$product.main_pair.pair_id`" pair_id=$product.main_pair.pair_id pair_data=$product.main_pair iw=$settings.Thumbnails.product_cart_thumbnail_width ih=$settings.Thumbnails.product_cart_thumbnail_height}
            </div>
            <div class="ty-cr-product-info-header">
                <div class="ty-product-block-title">{$product.product}</div>
            </div>
        </div>
    </div>
{/if}

<ul class="ty-cart-statistic ty-statistic-list">
    <li class="ty-cart-statistic__item ty-statistic-list-subtotal">
        <span class="ty-cart-statistic__title">{__("subtotal")}</span>
        <span class="ty-cart-statistic__value">{include file="common/price.tpl" value=$cart.display_subtotal}</span>
    </li>
    
    {hook name="checkout:checkout_totals"}
        {if $cart.shipping_required == true}
        {if $cart.shipping}
        <li class="ty-cart-statistic__item ty-statistic-list-shipping-method">
            <span class="ty-cart-statistic__title">
                {foreach from=$cart.shipping item="shipping" key="shipping_id" name="f_shipp"}
                    {$shipping.shipping}{if !$smarty.foreach.f_shipp.last}, {/if}
                {/foreach}
                <span class="ty-nowrap">{if $smarty.capture.shipping_estimation|trim}({$smarty.capture.shipping_estimation|trim nofilter}){/if}</span>
            </span>
            <span class="ty-cart-statistic__value">{include file="common/price.tpl" value=$cart.display_shipping_cost}</span>
        </li>
        {elseif $smarty.capture.shipping_estimation|trim}
        <li class="ty-cart-statistic__item ty-statistic-list-shipping-method">
            <span class="ty-cart-statistic__title">{__("shipping_cost")}</span>
            <span class="ty-cart-statistic__value">{$smarty.capture.shipping_estimation nofilter}</span>
        </li>
        {/if}
        {/if}
    {/hook}
    
    {if ($cart.discount|floatval)}
    <li class="ty-cart-statistic__item ty-statistic-list-discount">
        <span class="ty-cart-statistic__title">{__("including_discount")}</span>
        <span class="ty-cart-statistic__value discount-price">-{include file="common/price.tpl" value=$cart.discount}</span>
    </li>
    
    {/if}

    <li class="ty-cart-statistic__item ty-statistic-list-subtotal-discount">
        <span class="ty-cart-statistic__title">{__("order_discount")}{if !$cart.subtotal_discount}<div>{include file="addons/development/common/tooltip.tpl" note_text=__("get_product_review_reward_points", ["[amount]" => $addons.development.product_review, "[limit]" => $addons.development.product_reviews_number_limit, "[limit_month]" => $addons.development.product_reviews_time_limit]) tooltip_title=__("get_discount_now") tooltipclass="ty-category-tooltip"}</div>{/if}</span>
        <span class="ty-cart-statistic__value discount-price">-{include file="common/price.tpl" value=$cart.subtotal_discount}</span>
    </li>
    {hook name="checkout:checkout_discount"}{/hook}

    {if $cart.taxes}
    <li class="ty-cart-statistic__item ty-statistic-list-taxes ty-cart-statistic__group">
        <span class="ty-cart-statistic__title ty-cart-statistic_title_main">{__("taxes")}</span>
    </li>
    {foreach from=$cart.taxes item="tax"}
    <li class="ty-cart-statistic__item ty-statistic-list-tax">
        <span class="ty-cart-statistic__title">{$tax.description}&nbsp;({include file="common/modifier.tpl" mod_value=$tax.rate_value mod_type=$tax.rate_type}{if $tax.price_includes_tax == "Y" && ($settings.Appearance.cart_prices_w_taxes != "Y" || $settings.General.tax_calculation == "subtotal")}&nbsp;{__("included")}{/if})</span>
        <span class="ty-cart-statistic__value">{include file="common/price.tpl" value=$tax.tax_subtotal}</span>
    </li>
    {/foreach}
    {/if}

    {if $cart.payment_surcharge && !$take_surcharge_from_vendor}
    <li class="ty-cart-statistic__item ty-statistic-list-payment-surcharge" id="payment_surcharge_line">
        {assign var="payment_data" value=$cart.payment_id|fn_get_payment_method_data}
        <span class="ty-cart-statistic__title">{$cart.payment_surcharge_title|default:__("payment_surcharge")}{if $payment_data.payment}&nbsp;({$payment_data.payment}){/if}:</span>
        <span class="ty-cart-statistic__value">{include file="common/price.tpl" value=$cart.payment_surcharge span_id="payment_surcharge_value"}</span>
    </li>
    {math equation="x+y" x=$cart.total y=$cart.payment_surcharge assign="_total"}
    {capture name="_total"}{$_total}{/capture}
    {/if}
</ul>
<form name="call_requests_form" id="form_{$id}" action="{""|fn_url}" method="post" class="ty-call-request cm-ajax">
<input type="hidden" name="redirect_url" value="{$config.current_url}" />

{if $product}
    <input type="hidden" name="call_data[product_id]" value="{$product.product_id}" />
    <div class="ty-cr-product-info-container">
        <div class="ty-cr-product-info-image">
            {include file="common/image.tpl" images=$product.main_pair image_width=$settings.Thumbnails.product_cart_thumbnail_width image_height=$settings.Thumbnails.product_cart_thumbnail_height}
        </div>
        <div class="ty-cr-product-info-header">
            <div class="ty-product-block-title">{$product.product}</div>
        </div>
    </div>
{/if}

<div class="ty-cr-information">
    {__("call_requests.information", ['[working_hours]' => __("phone_working_hours")])}
</div>

<div class="ty-control-group">
    <label class="ty-control-group__title" for="call_data_{$id}_name">{__("firstname_lastname")}</label>
    <input id="call_data_{$id}_name" size="50" class="ty-input-text-full" placeholder="{__("firstname_lastname")}" type="text" name="call_data[name]" value="{$smarty.session.auth.user_data.firstname} {$smarty.session.auth.user_data.lastname}" />
</div>

<div class="ty-control-group">
    <label for="call_data_{$id}_phone" class="ty-control-group__title cm-required">{__("phone")}</label>
    <input id="call_data_{$id}_phone" class="ty-input-text-full cm-cr-mask-phone" placeholder="{__("phone")}" size="50" type="tel" name="call_data[phone]" value="{$smarty.session.auth.user_data.phone}" />
</div>

<div class="ty-control-group">
    <label for="call_data_{$id}_email" class="ty-control-group__title cm-email">{__("email")}</label>
    <input id="call_data_{$id}_email" class="ty-input-text-full" size="50" placeholder="{__("email")}" type="text" name="call_data[email]" value="{$smarty.session.auth.user_data.email}" />
</div>

<div class="cr-popup-error-box">
    <div class="hidden cm-cr-error-box help-inline">
        <p>{__("call_requests.enter_phone_or_email_text")}</p>
    </div>
</div>

{*

<div class="ty-control-group">
    <label for="call_data_{$id}_convenient_time" class="ty-control-group__title">{__("call_requests.convenient_time")}</label>
    <input id="call_data_{$id}_convenient_time" class="ty-input-text cm-cr-mask-time" size="6" type="text" name="call_data[time_from]" value="" placeholder="09:00" /> -
    <input id="call_data_{$id}_convenient_time" class="ty-input-text cm-cr-mask-time" size="6" type="text" name="call_data[time_to]" value="" placeholder="20:00" />
</div>

*}

<div class="ty-control-group">
    <input type="hidden" data-autocompletetype="country" name="call_data[country]" value="{$settings.General.default_country}" />
    <input type="hidden" data-autocompletetype="city_id" name="city_id" value="" />
    <input type="hidden" data-autocompletetype="state" name="call_data[state]" value="{$smarty.session.auth.user_data.s_state}" />
    <label for="call_data_{$id}_city" class="ty-control-group__title">{__("city")}</label>
    <input  data-autocompletetype="city" id="call_data_{$id}_city" placeholder="{__("city")}" class="ty-input-text-full" size="50" type="text" name="call_data[city]" value="{$smarty.session.auth.user_data.s_city}" />
</div>
<div class="ty-control-group">
    <label for="call_data_{$id}_address" class="ty-control-group__title">{__("address")}</label>
    <input data-autocompletetype="street-address" id="call_data_{$id}_address" placeholder="{__("address")}" class="ty-input-text-full" size="50" type="text" name="call_data[address]" value="{$smarty.session.auth.user_data.s_address}" />
</div>

{include file="common/image_verification.tpl" option="use_for_call_request" align="left"}

<div class="buttons-container">
    {include file="buttons/button.tpl" but_onclick="fn_send_call_request(`$product.product_id`);" but_text=__("buy_now_cr") but_role="submit" but_meta="ty-btn__primary ty-btn__big ty-btn"}
</div>

</form>

<script type="text/javascript">

(function(_, $) {$ldelim}
    $(function() {$ldelim}
        fn_init_autocomplete($('#form_{$id}'));
    {$rdelim});
{$rdelim}(Tygh, Tygh.$));

{literal}
function fn_send_call_request(obj_id) {

    var cart_changed = true;
    
    var params = [];
    
    if ($('form[name="product_form_' + obj_id + '"]').length) {
        $('#product_data_block').html('');
        
        var elms = $(':input:not([type=radio]):not([type=checkbox])', 'form[name="product_form_' + obj_id + '"]');
        $.each(elms, function(id, elm) {
            if (elm.type != 'submit' && elm.type != 'file' && !($(this).hasClass('cm-hint') && elm.value == elm.defaultValue) && elm.name.length != 0 && elm.name.match("^product_data")) {
                $('#product_data_block').append('<input type="hidden" name="' + elm.name + '" value="' + elm.value + '" />');
            }
        });
        
        var radio = $('input[type=radio]:checked, input[type=checkbox]', 'form[name="product_form_' + obj_id + '"]');
        $.each(radio, function(id, elm) {
            if (elm.name.match("^product_data")) {
                if ($(elm).prop('disabled')) {
                    return true;
                }
                var value = elm.value;
                if ($(elm).is('input[type=checkbox]:checked')) {
                    if (!$(elm).hasClass('cm-no-change')) {
                        value = $(elm).val();
                    }
                } else if ($(elm).is('input[type=checkbox]')) {
                    if (!$(elm).hasClass('cm-no-change')) {
                        value = 'unchecked';
                    } else {
                        value = '';
                    }
                }
                $('#product_data_block').append('<input type="hidden" name="' + elm.name + '" value="' + value + '" />');
            }
        });
    }
}
{/literal}

</script>
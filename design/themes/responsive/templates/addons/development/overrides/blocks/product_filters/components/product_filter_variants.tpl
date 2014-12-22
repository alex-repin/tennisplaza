{if $filter.ranges|fn_is_not_empty}
{split data=$filter.ranges size="10" assign="splitted_ranges" skip_complete=true}
{$tot_width = 180 * $splitted_ranges|count}
<div class="ty-product-filters {if $collapse}hidden{/if}" id="content_{$filter_uid}" style="width: {$tot_width}px;">
    <div id="ranges_{$filter_uid}" class="ty-product-filters__item-more" style="display: inline-block;">
        {foreach from=$splitted_ranges item="column"}
            <div style="float: left;width: 180px;">
            {foreach from=$column item="range"}
                {if $filter.selected_ranges && $range.range_id|array_key_exists:$filter.selected_ranges}
                    {capture name="has_selected"}Y{/capture}
                    <div class="ty-product-filters__group">
                        {strip}
                            {assign var="fh" value=$smarty.request.features_hash|fn_delete_range_from_url:$range:$filter.field_type}
                            {if $fh}
                                {assign var="attach_query" value="features_hash=`$fh`"}
                            {/if}
                            {if $filter.feature_type == "E" && $range.range_id == $smarty.request.variant_id}
                                {assign var="reset_lnk" value=$reset_qstring}
                            {else}
                                {assign var="reset_lnk" value=$filter_qstring}
                            {/if}
                            {if $fh}
                                {assign var="href" value=$reset_lnk|fn_link_attach:$attach_query|fn_url}
                            {else}
                                {assign var="href" value=$reset_lnk|fn_url}
                            {/if}
                            {assign var="use_ajax" value=$href|fn_compare_dispatch:$config.current_url}
                            <a href="{$href}" class="ty-product-filters__item checked{if $allow_ajax && $use_ajax} cm-ajax-force cm-ajax cm-ajax-full-render cm-history"{/if} data-ca-target-id="{$ajax_div_ids}" rel="nofollow"><span class="ty-filter-icon"><i class="ty-icon-ok ty-filter-icon__check"></i><i class="ty-icon-cancel ty-filter-icon__delete"></i></span>{$filter.prefix}{$range.range_name|fn_text_placeholders}{$filter.suffix}</a>
                        {/strip}
                    </div>
                {else}
                    {include file="blocks/product_filters/components/variant_item.tpl" range=$range filter=$filter ajax_div_ids=$ajax_div_ids filter_qstring=$filter_qstring reset_qstring=$reset_qstring allow_ajax=$allow_ajax}
                {/if}
            {/foreach}
            </div>
        {/foreach}
    </div>
</div>
{/if}
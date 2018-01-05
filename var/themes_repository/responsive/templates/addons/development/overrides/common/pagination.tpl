{assign var="id" value=$id|default:"pagination_contents"}
{assign var="pagination" value=$search|fn_generate_pagination}

{if $smarty.capture.pagination_open != "Y"}
    <div class="ty-pagination-container cm-pagination-container" id="{$id}">

    {if $save_current_page}
        <input type="hidden" name="page" value="{$search.page|default:$smarty.request.page}" />
    {/if}

    {if $save_current_url}
        <input type="hidden" name="redirect_url" value="{$config.current_url}" />
    {/if}
{/if}

    {if $settings.Appearance.top_pagination == "Y" && $smarty.capture.pagination_open != "Y" || $smarty.capture.pagination_open == "Y"}
    {assign var="c_url" value=$config.current_url|fn_query_remove:"page"}

    {if ($ajax_pagination == 'Y' && !$config.tweaks.disable_dhtml) || $force_ajax}
        {assign var="ajax_class" value="cm-ajax"}
    {/if}

    {if $smarty.capture.pagination_open == "Y"}
        <div class="ty-pagination__bottom">
    {else}
        <div class="ty-pagination-sorting">
    {/if}
    {if $pagination.total_pages > 1}
    <div class="ty-pagination">
        {*if $pagination.prev_range}
            <a data-ca-scroll=".cm-pagination-container" href="{"`$c_url`&page=`$pagination.prev_range``$extra_url`"|fn_url}" data-ca-page="{$pagination.prev_range}" class="cm-history hidden-phone ty-pagination__item ty-pagination__range {$ajax_class}" data-ca-target-id="{$id}">{$pagination.prev_range_from} - {$pagination.prev_range_to}</a>
        {/if*}
        <a data-ca-scroll=".cm-pagination-container" class="ty-pagination__item ty-pagination__btn {if $pagination.prev_page}ty-pagination__prev cm-history {$ajax_class}{/if}" {if $pagination.prev_page}href="{"`$c_url`&page=`$pagination.prev_page`"|fn_url}" data-ca-page="{$pagination.prev_page}" data-ca-target-id="{$id}"{/if}><i class="ty-pagination__text-arrow">&larr;</i>&nbsp;<span class="ty-pagination__text">{__("prev_page")}</span></a>
        {if $pagination.prev_range}<span class="ty-pagination-el">...</span>{/if}
        <div class="ty-pagination__items">
            {foreach from=$pagination.navi_pages item="pg"}
                {if $pg != $pagination.current_page}
                    <a data-ca-scroll=".cm-pagination-container" href="{"`$c_url`&page=`$pg``$extra_url`"|fn_url}" data-ca-page="{$pg}" class="cm-history ty-pagination__item {$ajax_class}" data-ca-target-id="{$id}">{$pg}</a>
                {else}
                    <span class="ty-pagination__selected">{$pg}</span>
                {/if}
            {/foreach}
        </div>
        {if $pagination.next_range}<span class="ty-pagination-el">...</span>{/if}

        <a data-ca-scroll=".cm-pagination-container" class="ty-pagination__item ty-pagination__btn {if $pagination.next_page}ty-pagination__next cm-history {$ajax_class}{/if}" {if $pagination.next_page}href="{"`$c_url`&page=`$pagination.next_page``$extra_url`"|fn_url}" data-ca-page="{$pagination.next_page}" data-ca-target-id="{$id}"{/if}><span class="ty-pagination__text">{__("next")}</span>&nbsp;<i class="ty-pagination__text-arrow">&rarr;</i></a>

        {*if $pagination.next_range}
            <a data-ca-scroll=".cm-pagination-container" href="{"`$c_url`&page=`$pagination.next_range``$extra_url`"|fn_url}" data-ca-page="{$pagination.next_range}" class="cm-history ty-pagination__item hidden-phone ty-pagination__range {$ajax_class}" data-ca-target-id="{$id}">{$pagination.next_range_from} - {$pagination.next_range_to}</a>
        {/if*}
    </div>
    {/if}
    {if $smarty.capture.pagination_open == "Y"}
        </div>
    {else}
        {if !$no_sorting}
            {include file="views/products/components/sorting.tpl"}
        {/if}
        </div>        
    {/if}
    {else}
        <div><a data-ca-scroll=".cm-pagination-container" href="" data-ca-page="{$pg}" data-ca-target-id="{$id}" class="hidden"></a></div>
    {/if}

{if $smarty.capture.pagination_open == "Y"}
    <!--{$id}--></div>
    {capture name="pagination_open"}N{/capture}
{elseif $smarty.capture.pagination_open != "Y"}
    {capture name="pagination_open"}Y{/capture}
{/if}

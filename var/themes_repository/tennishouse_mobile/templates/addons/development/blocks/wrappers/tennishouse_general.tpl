{if $content|trim}
    <div class="ty-tennishouse-container ty-products-scroller clearfix{if isset($hide_wrapper)} cm-hidden-wrapper{/if}{if $hide_wrapper} hidden{/if}{if $details_page} details-page{/if}{if $block.user_class} {$block.user_class}{/if}{if $content_alignment == "RIGHT"} ty-float-right{elseif $content_alignment == "LEFT"} ty-float-left{/if}">
        {if $title || $smarty.capture.title|trim}
            <h2 class="ty-mainbox-title">
                {hook name="wrapper:mainbox_general_title"}
                {if $smarty.capture.title|trim}
                    {$smarty.capture.title nofilter}
                {else}
                    {$title|fn_read_title nofilter}
                {/if}
                <div class="ty-sidebox__title-toggle cm-combination visible-phone" id="sw_sidebox_{$block.block_id}">
                    <i class="ty-sidebox__icon-hide ty-icon-up-open"></i>
                    <i class="ty-sidebox__icon-open ty-icon-down-open"></i>
                </div>
                {/hook}
            </h2>
        {/if}
        <div class="ty-tennishouse-body" id="sidebox_{$block.block_id}">{$content nofilter}</div>
    </div>
{/if}
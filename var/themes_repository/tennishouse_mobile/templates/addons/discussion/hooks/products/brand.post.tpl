{if $product.discussion_type && $product.discussion_type != 'D' && $product.discussion.posts}
    <div class="ty-discussion__rating-wrapper" id="average_rating_product">
        {assign var="rating" value="rating_`$obj_id`"}{$smarty.capture.$rating nofilter}
        <a class="ty-discussion__review-a cm-external-click" data-ca-scroll="content_discussion" data-ca-external-click-id="discussion">({$product.discussion.posts|count})</a>
        {*<a class="ty-discussion__review-write cm-external-click cm-dialog-opener cm-dialog-auto-size" data-ca-external-click-id="discussion" data-ca-target-id="new_post_dialog_{$obj_id}" rel="nofollow">{__("write_review")}</a>*}
    <!--average_rating_product--></div>
{/if}
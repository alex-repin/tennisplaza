{$obj_prefix = "force_"}
<div class="ty-product-add-review-wrapper ty-force-review-wrapper" id="add_review_{$obj_prefix}{$obj_id}">
    <div class="ty-product-review-title">{if $allow_reward}{capture name="reward_amount"}{$setting_name = "review_reward_`$discussion.object_type`"}{include file="common/price.tpl" value=$addons.development.$setting_name}{/capture}{__("force_review_reward_text", ["[amount]" => $smarty.capture.reward_amount])}{else}{__("force_review_text")}{/if}</div>
    {if $allow_reward}
        <div class="ty-product-review-reward-note">{__("force_review_reward_text_note")}</div>
    {/if}
    {include file="addons/discussion/views/discussion/components/quick_post.tpl"}
</div>
<script type="text/javascript">
(function(_, $) {
    $(function() {
        $.processForms($('#add_review_{$obj_prefix}{$obj_id}'));
    });
}(Tygh, Tygh.$));
</script>

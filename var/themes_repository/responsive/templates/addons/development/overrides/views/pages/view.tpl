<div class="ty-wysiwyg-content">
    {hook name="pages:page_content"}
    <div class="ty-page-description" {live_edit name="page:description:{$page.page_id}"}>
        {$page.description|fn_check_vars|fn_render_page_blocks:$smarty.capture nofilter}
    </div>
    {/hook}
</div>

{capture name="mainbox_title"}<span {live_edit name="page:page:{$page.page_id}"}>{$page.page}</span>{/capture}
    
{hook name="pages:page_extra"}
{/hook}
{if $show_tooltip}<span class="ty-tooltip-block"><a class="cm-tooltip" data-ceTooltipPosition="ssl" title="{__('ssl_seal_text')}">{/if}<div class="ty-ssl-cert" id="ssl-cert"></div>{if $show_tooltip}</a></span>{/if}
{if $smarty.session.display_ssl_tooltip == 'Y'}
<script type="text/javascript">
    Tygh.$(document).ready(function() {$ldelim}
        setTimeout(function() {$ldelim}
            $('#ssl-cert').trigger('mouseover');
        {$rdelim}, 300);
        setTimeout(function() {$ldelim}
            $('#ssl-cert').trigger('mouseleave');
        {$rdelim}, 5000);
    {$rdelim});
</script>
{/if}
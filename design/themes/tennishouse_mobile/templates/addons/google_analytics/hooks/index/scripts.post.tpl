{script src="js/addons/google_analitycs/google_analitycs.js"}
<script type="text/javascript">
(function(i,s,o,g,r,a,m){
    i['GoogleAnalyticsObject']=r;
    i[r]=i[r]||function(){$ldelim}(i[r].q=i[r].q||[]).push(arguments){$rdelim},i[r].l=1*new Date();
    a=s.createElement(o), m=s.getElementsByTagName(o)[0];
    a.async=1;
    a.src=g;
    m.parentNode.insertBefore(a,m)
})(window,document,'script','//www.google-analytics.com/analytics.js','ga');

{$url = fn_url($config.current_url, 'C', 'rel')}
ga('create', '{$addons.google_analytics.tracking_code}'{if $auth.user_id}, {$ldelim}'userId': '{$auth.user_id}'{$rdelim}{/if});
ga('send', 'pageview', '{$url|escape:javascript nofilter}');
</script>
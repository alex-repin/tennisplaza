{** block-description:tmpl_copyright **}
<p class="bottom-copyright">&copy; {if $smarty.const.TIME|date_format:"%Y" != $settings.Company.company_start_year}{$settings.Company.company_start_year}-{/if}{$smarty.const.TIME|date_format:"%Y"} <img src="{$images_dir}/addons/development/black_white_logo.png" style="height: 25px; margin-top: -6px" />{*. &nbsp;{__("powered_by")} <a class="bottom-copyright" href="{$config.resources.product_url}" target="_blank">{__("copyright_shopping_cart", ["[product]" => $smarty.const.PRODUCT_NAME])}</a>*}
</p>
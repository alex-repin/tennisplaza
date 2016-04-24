{strip}
{assign var="foreach_name" value="item_`$iid`"}

<div class="top-menu">

<div class="top-menu ty-menu-vertical">

    <div class="ty-menu__header">{__("products_catalog")}</div>
    <ul class="ty-menu__items">
        {assign var="items_tree" value=""|fn_get_catalog_panel_categoies}
        {include file="addons/development/common/catalog_panel_menu.tpl" items_tree=$items_tree level=1 type="C"}
    </ul>
    
    <div class="ty-menu__header">{__("my_account")}</div>
    <ul class="ty-menu__items ty-menu__items-profile">
        {include file="addons/development/common/my_account.tpl"}
    </ul>
    
    <div class="ty-menu__header">{__("useful_info")}</div>
    <ul class="ty-menu__items ty-menu__items-profile">
        {assign var="items_tree" value=""|fn_get_catalog_panel_pages}
        {include file="addons/development/common/catalog_panel_menu.tpl" items_tree=$items_tree level=1 type="A"}
    </ul>
</div>

</div>
{/strip}
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<meta name="Keywords" content="{$keywords}" />
<meta name="Description" content="{$description}" />
<!-- TemplateBeginEditable name="doctitle" -->
<title>{$page_title}</title>
<!-- TemplateEndEditable --><!-- TemplateBeginEditable name="head" --><!-- TemplateEndEditable -->
<link rel="shortcut icon" href="favicon.ico" />
<link rel="icon" href="animated_favicon.gif" type="image/gif" />
<link href="{$ecs_css_path}" rel="stylesheet" type="text/css" />
{* 包含脚本文件 *}
{insert_scripts files='common.js'}
</head>
<body>
<!-- #BeginLibraryItem "/library/page_header.lbi" --><!-- #EndLibraryItem -->
<!--当前位置 start-->
<div class="block box">
 <div id="ur_here">
  <!-- #BeginLibraryItem "/library/ur_here.lbi" --><!-- #EndLibraryItem -->
 </div>
</div>
<!--当前位置 end-->
<div class="blank"></div>
<div class="block clearfix">
  <!--right start-->
  <div class="AreaR">
   <div class="box">
   <div class="box_1">
    <h3><span>{$lang.treasure_info}</span></h3>
    <div class="boxCenterList">
      <ul class="group clearfix">
      <li style="margin-right:8px; text-align:center;">
      <a href="{$snatch_goods.url}"><img src="{$snatch_goods.goods_thumb}" alt="{$snatch_goods.goods_name|escape:html}" /></a>
      </li>
      <li style="width:555px; line-height:23px;">
       {insert_scripts files='lefttime.js'}
     <a href="{$snatch_goods.url}"><strong>{$snatch_goods.goods_name|escape:html}</strong>{if $snatch_goods.product_id > 0}&nbsp;[{$products_info}]{/if}</a><br />
     {$lang.shop_price} <font class="shop">{$snatch_goods.formated_shop_price}</font><br />
     {$lang.market_price} <font class="market">{$snatch_goods.formated_market_price}</font> <br />
     {$lang.residual_time} <font color="red"><span id="leftTime">{$lang.please_waiting}</span></font><br />
     {$lang.activity_desc}：<br />{$snatch_goods.desc|escape:html|nl2br}
      </li>
      </ul>
    </div>
   </div>
  </div>
   <div class="blank5"></div>
   <div class="box">
   <div class="box_1">
    <h3><span>{$lang.activity_intro}</span></h3>
    <div class="boxCenterList">
    {$snatch_goods.snatch_time}<br />
    {$lang.price_extent}{$snatch_goods.formated_start_price} - {$snatch_goods.formated_end_price} <br />
    {$lang.user_to_use_up}{$snatch_goods.cost_points} {$points_name}<br />
    {$lang.snatch_victory_desc}<br />
    <!--{if $snatch_goods.max_price neq 0}-->    {$lang.price_less_victory}{$snatch_goods.formated_max_price}，{$lang.price_than_victory}{$snatch_goods.formated_max_price}，{$lang.or_can}{$snatch_goods.formated_max_price}{$lang.shopping_product}。
    <!--{else}-->
    {$lang.victory_price_product}
    <!--{/if}-->
    </div>
   </div>
  </div>
  <div class="blank5"></div>
  <div id="ECS_SNATCH">
    <!-- #BeginLibraryItem "/Library/snatch.lbi" --><!-- #EndLibraryItem -->
    </div>
  </div>
  <!--right end-->
</div>
<div class="blank"></div>
<!-- #BeginLibraryItem "/library/page_footer.lbi" --><!-- #EndLibraryItem -->
</body>
<script type="text/javascript">
var gmt_end_time = {$snatch_goods.gmt_end_time|default:0};
var id = {$id};
{foreach from=$lang.snatch_js item=item key=key}
var {$key} = "{$item}";
{/foreach}
{foreach from=$lang.goods_js item=item key=key}
var {$key} = "{$item}";
{/foreach}
<!-- {literal} -->

onload = function()
{
  try
  {
    window.setInterval("newPrice(" + id + ")", 8000);
    onload_leftTime();
  }
  catch (e)
  {}
}
<!-- {/literal} -->
</script>
</html>
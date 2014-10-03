{block "checks-initialize"}
	{config_load file="base.tpl.ini" section="checks"}
	{checks cookie=#cookie_check# css=#css_check# js=#js_check# assign=checks} {* into standard class are assigned objects of proper Check type *}
{/block}
{block "doctype"}
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN"
                      "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
{/block}
{block "html"}
	{config_load file="base.tpl.ini" section="html"}
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="{#xml_lang#}" lang="{#lang#}">
{/block}
{block "head"}
	{config_load file="base.tpl.ini" section="head"}
	<head>
		<title>{#title#}</title>
	   <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
		<meta http-equiv="cache-control" content="no-cache" />
		<meta name="description" lang="{#lang#}" content="{#description#}" />
	{if isset($css)}
		{foreach from=$css item=cssFile}
		<link rel="stylesheet" type="text/css" media="all" href="css/{$cssFile}" />
		{/foreach}
	{/if}
	{if isset($headerJs)}
		{foreach from=$headerJs item=jsFile}
		<script type="text/javascript" src="js/{$jsFile}"></script>
		{/foreach}
	{/if}
	</head>
{/block}
	<body>
{config_load file="base.tpl.ini" section="main_divs"}
		<div{if #wrapper_div_class_name#} class='{#wrapper_div_class_name#}'{/if}{if #wrapper_div_id_name#} id='{#wrapper_div_id_name#}'{/if}>
			<div{if #content_div_class_name#} class='{#content_div_class_name#}'{/if}{if #content_div_id_name#} id='{#content_div_id_name#}'{/if}>
{block "checks-results"}
	{if $checks.unsupportedTechnologies['required']} {* there is for sure unfulfilled requirements *}
		{assign var=allowContent value=FALSE}
	{else}
		{assign var=allowContent value=TRUE}
	{/if}
	{if $checks.uncheckableTechnologies['required'] || $checks.unsupportedTechnologies['required'] || $checks.uncheckableTechnologies['recommended'] || $checks.unsupportedTechnologies['recommended']}
				<div id="checks">
		{if $checks.uncheckableTechnologies['required'] || $checks.unsupportedTechnologies['required']} {* any chance for unfulfilled requirements, possible or ensured *}
					<div class="checks" id="required-list">
			{foreach from=$checks.uncheckableTechnologies['required'] key=technologyCode item=check} {* some possible unfulfilled requirements *}
						<div class="required {$technologyCode}" id="{$technologyCode}-required"{if $technologyCode == 'css'} style="display: none"{/if} >
							<h2>Pro zobrazení veškerého obsahu je nutné povolit <a href="/how_to_enable/?task={$technologyCode}#{$technologyCode}" title="návod na zprovoznění {$technologyCode}">{$technologyCode}</a></h2>
						</div>
			{/foreach}
			{foreach from=$checks.unsupportedTechnologies['required'] key=technologyCode item=check} {* ensured unfulfilled requirements *}
						<div class="required {$technologyCode}" id="{$technologyCode}-required">
							<h2>Pro zobrazení obsahu je nutné povolit <a href="/how_to_enable/?task={$technologyCode}#{$technologyCode}" title="návod na zprovoznění {$technologyCode}">{$technologyCode}</a></h2>
						</div>
			{/foreach}
					</div>
		{/if}
		{if $checks.uncheckableTechnologies['recommended'] || $checks.unsupportedTechnologies['recommended']}
					<div class="checks" id="recommended-list">
			{foreach from=$checks.unsupportedTechnologies['recommended'] key=technologyCode item=check}
						<div class="recommended {$technologyCode}" id="{$technologyCode}-recommended">
							<h3>Obsah nelze správně zobrazit kvůli chybějící podpoře <a href="/how_to_enable/?task={$technologyCode}#{$technologyCode}" title="návod na zprovoznění {$technologyCode}">{$technologyCode}</a></h3>
						</div>
			{/foreach}
			{foreach from=$checks.uncheckableTechnologies['recommended'] key=technologyCode item=check}
						<div class="recommended {$technologyCode}" id="{$technologyCode}-recommended"{if $technologyCode == 'css'} style="display: none"{/if}>
							<h3>Obsah nelze správně zobrazit kvůli chybějící podpoře <a href="/how_to_enable/?task={$technologyCode}#{$technologyCode}" title="návod na zprovoznění {$technologyCode}">{$technologyCode}</a></h3>
						</div>
			{/foreach}
					</div>
		{/if}
				</div>
	{/if}
{/block}
{if $allowContent} {* every required and PHP checkable technologies are supported *}
	{foreach from=$checks.uncheckableTechnologies['required'] key=technologyCode item=check}
				<span id="{$technologyCode}-required"{if $technologyCode != 'css'} style="display: none"{/if}> {* css and js technologies are cross-checking themselves, that means: if css and js are both unsuported, content is displayed; look at checks.js *}
	{/foreach}
	{block "allowed-content"}
		{config_load file="base.tpl.ini" section="allowed-content"}
					<div{if #main_content_div_class_name#} class="{#main_content_div_class_name#}"{/if}{if #main_content_div_id_name#} id="{#main_content_div_id_name#}"{/if}>
						BASE CONTENT
					</div>
	{/block}
	{block "footer-errors"}
	{/block}
	{foreach from=$checks.uncheckableTechnologies['required'] key=technologyCode item=check}
				</span>
	{/foreach}
{/if}
			</div>
		</div>
{block "footer"}
		<span id="footer">
	{if isset($footerJs)}
		{foreach from=$footerJs item=jsFile}
				<script type="text/javascript" src="js/{$jsFile}"></script>
		{/foreach}
	{/if}
	{if $checks}
			<script type="text/javascript" src="../universal/js/system/checks.js"></script>
	{/if}
		</span>
{/block}
	</body>
</html>

{nocache}
{config_load file="base.tpl.ini" section="checks"}
{config_load file="export-html_xls.tpl.ini" section="head"}
<html xmlns:o="urn:schemas-microsoft-com:office:office"
	xmlns:x="urn:schemas-microsoft-com:office:excel"
	xmlns="http://www.w3.org/TR/REC-html40">
	<head>
		<meta http-equiv='Content-Language' content='{if isset($contentLanguage)}{$contentLanguage}{else}{#contentLanguage#}{/if}'>
		<meta http-equiv='Content-Type' content='text/html; charset={if isset($contentCharset)}{$contentCharset}{else}{#contentCharset#}{/if}'>
{if isset($headTitle)}
		<title>{$headTitle}</title>
{/if}
{if isset($descriptionContent)}
		<meta name='description' content='{$descriptionContent}'>
{/if}
	</head>
	<body>
{config_load file="export-html_xls.tpl.ini" section="table"}
		<table x:str border={if isset($border)}{$border}{else}{#border#}{/if}
			cellspacing={if isset($cellspacing)}{$cellspacing}{else}{#cellspacing#}{/if}
			cellpading={if isset($cellpading)}{$cellpading}{else}{#cellpading#}{/if}>
{if $data->header}
			<thead>
				<tr{if $data->header->bgcolor} BGCOLOR="{$data->header->bgcolor}"{/if}>
	{foreach from=$data->header item=headerCell}<th>{$headerCell}</th>{/foreach}
				</tr>
			</thead>
{/if}
			<tbody>
{foreach from=$data item=bodyRow}
				<tr{if $bodyRow->bgcolor} BGCOLOR="{$bodyRow->bgcolor}"{/if}>
	{foreach from=$bodyRow item=bodyCell}<td>{$bodyCell}</td>{/foreach} 
				</tr>
{/foreach}
{* footer can not be builded by tfoot tag cause of no support by Excel *}
{if $data->footer}
				<tr />
				<tr{if $data->footer->bgcolor} BGCOLOR="{$data->footer->bgcolor}"{/if}>
	{foreach from=$data->footer item=footerCell}<td>{$footerCell}</td>{/foreach}
				</tr>
{/if}
			</tbody>
		</table>
	</body>
</html>
{/nocache}

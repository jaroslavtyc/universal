<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN"
                      "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="cs">
	<head>
	    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" >
		<meta http-equiv="cache-control" content="no-cache">
		<meta name="description" lang="cs" content="{$metaContent}">
{foreach from=$css item=cssFile}
		<link rel="stylesheet" type="text/css" media="all" href="css/{$cssFile}" />
{/foreach}
{foreach from=$headerJs item=jsFile}
		<script type="text/javascript" src="js/{$jsFile}"></script>
{/foreach}
	</head>
	<body>
		<div id='main-base'>
{if $cssCheck}
	{include file="system/css_check.tpl"}
{/if}
{if $cookieCheck || $javascriptCheck}
	{include file="system/js_and_cookie_check.tpl"}
{/if}
			<div id='main-content'>

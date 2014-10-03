{include file="base/header.tpl"}
{if $cookieSuport}
	<div id="checks-ok">
		<div id="citate">
		{include file="content/citate.tpl"}
		</div>
		<div id="preamble">
		{include file="content/preamble.tpl"}
		</div>
		<div id="dream">
		{include file="content/dream.tpl"}
		</div>
		<div id="closing">
		{include file="content/closing.tpl"}
		</div>
		<span id="gate" class="loaded"></span>
		<div id="hint" class="loaded"></div>
	</div>
{/if}
{* $dream->ukazZvuky() *}
{include file="base/footer.tpl"}
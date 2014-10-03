<div id="needed">
	<h3>
		Ke zprovoznění těchto stránek je zapotřebí povolit:
		<ul id="needed-list">
			<li id="javascript-needed">
				<a href="/how_to_allow/?task=javascript" title="návod na zprovoznění javascriptu">javascript</a>
			</li>
{if !$cookieSuport}
			<li id="cookies-needed">
				<a href="/how_to_allow/?task=cookie" title="návod na zprovoznění cookies">cookies</a>
			</li>
{/if}
		</ul>
	</h3>
</div>
<script src="js/system/checks.js"></script>
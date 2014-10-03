<?php
function smarty_function_echo($params, $template){
	echo '<pre style="color: red; background-color: yellow; text-align: left">';
	foreach($params as $index=>$param){
		echo("$index=");
		var_dump($param);
	}
	echo '</pre>';
}

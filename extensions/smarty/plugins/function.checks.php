<?php
/**
 * Smarty plugin
 *
 * @package Smarty
 * @subpackage PluginsFunction
 */

/**
 * Smarty {checks} plugin
 *
 * Type:     function<br>
 * Name:     checks<br>
 * Purpose:  in dependency of params checks support of technologies (css, js, cookies) - only cookie is checked in fact, other technologies are only setted as checkable, page code has to manage check and behaviour itself
 *
 * @param array $params parameters
 * @param Smarty_Internal_Template $template template object
 * @return mixed Array list of Check objects if no 'assign' parameter given, or void along with assigning array to 'assign' parameter
 */
function smarty_function_checks($params, $template)
{
	$checks = array();
	$checks['technologies'] = array();
	$checks['supportedTechnologies'] = array(\universal\Check\Check::RELEVANCE_IRRELEVANT => array(), \universal\Check\Check::RELEVANCE_RECOMMENDED => array(), \universal\Check\Check::RELEVANCE_REQUIRED => array());
	$checks['uncheckableTechnologies'] = array(\universal\Check\Check::RELEVANCE_IRRELEVANT => array(), \universal\Check\Check::RELEVANCE_RECOMMENDED => array(), \universal\Check\Check::RELEVANCE_REQUIRED => array());
	$checks['unsupportedTechnologies'] = array(\universal\Check\Check::RELEVANCE_IRRELEVANT => array(), \universal\Check\Check::RELEVANCE_RECOMMENDED => array(), \universal\Check\Check::RELEVANCE_REQUIRED => array());
	$technologies = array('css','js','cookie');
	foreach($technologies as $technology){
		switch($technology){
			case 'css':
				$checks['technologies'][$technology] = new \universal\Check\CssCheck();
				break;
			case 'js':
				$checks['technologies'][$technology] = new \universal\Check\JsCheck();
				break;
			case 'cookie':
				$checks['technologies'][$technology] = new \universal\Check\CookieCheck();
				break;
			default:
				throw new Exception('Uknown technology ' . $technology,E_USER_WARNING);
		}
		if (empty($params[$technology]))
			$checks['technologies'][$technology]->setRelevance(\universal\Check\Check::RELEVANCE_IRRELEVANT);
		else
			$checks['technologies'][$technology]->setRelevance($params[$technology]);
		if (!$checks['technologies'][$technology]->isCheckable() || $checks['technologies'][$technology]->relevance == \universal\Check\Check::RELEVANCE_IRRELEVANT) {
			$checks['uncheckableTechnologies'][$checks['technologies'][$technology]->relevance][$technology] = &$checks['technologies'][$technology];
		} elseif ($checks['technologies'][$technology]->isSupported()) //calling support test as soon as possible to avoid problems with already sent headers
			$checks['supportedTechnologies'][$checks['technologies'][$technology]->relevance][$technology] = &$checks['technologies'][$technology];
		else
			$checks['unsupportedTechnologies'][$checks['technologies'][$technology]->relevance][$technology] = &$checks['technologies'][$technology];
	}
	if (!isset($params['assign'])) {
		return $checks;
	} else {
		$template->assign($params['assign'], $checks);
	}
}

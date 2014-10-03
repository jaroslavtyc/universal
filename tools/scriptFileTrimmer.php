<?php

/**
 * Source code checker and trimer, originaly from
 * Source Codes Checker of the Nette Framework (http://nette.org)
 */

require __DIR__ . '/../autoload.php';

echo '
CodeTrimmer version 0.1
-----------------------
';

$options = getopt('d:fl'); // ":" after letter means option with required value,
// others are not expecting any option value
if (empty($options)) { //no settings given from command line
?>
Usage: php <?php echo __FILE__ ?> [options]

Options:
	-d <path>  folder to scan (optional)
	-f         fixes files instead of checking only
	-l         convert newline characters into Universal standarized newline character (<?php echo File::DEFAULT_NEWLINE_CHARACTER ?>)

<?php
}
$trimmer = new CodeTrimmer;
$trimmer->readOnly = !isset($options['f']);

// control characters checker
$trimmer->tasks[] = function($trimmer, $s) {
	if (Strings::match($s, '#[\x00-\x08\x0B\x0C\x0E-\x1F]#')) {
		$trimmer->error('contains control characters');
	}
};

// BOM remover
$trimmer->tasks[] = function($trimmer, $s) {
    if (substr($s, 0, 3) === "\xEF\xBB\xBF") {
    	$trimmer->fix('contains BOM');
    	return substr($s, 3);
    }
};

// UTF-8 checker
$trimmer->tasks[] = function($trimmer, $s) {
	if (!Strings::checkEncoding($s)) {
		$trimmer->error('in not valid UTF-8 file');
	}
};

// invalid phpDoc checker
$trimmer->tasks[] = function($trimmer, $s) {
    if ($trimmer->is('php')) {
    	foreach (token_get_all($s) as $token) {
    		if ($token[0] === T_COMMENT && Strings::match($token[1], '#/\*\s.*@[a-z]#isA')) {
    			$trimmer->warning("missing /** in phpDoc comment on line $token[2]");
    		}
    	}
    }
};

// newline characters normalizer for the current OS
if (isset($options['l'])) {
	$trimmer->tasks[] = function($trimmer, $s) {
		$new = str_replace("\n", PHP_EOL, str_replace(array("\r\n", "\r"), array("\n", ''), $s));
		if ($new !== $s) {
    		$trimmer->fix('contains non-system line-endings');
    		return $new;
		}
	};
}

// trailing ? > remover
$trimmer->tasks[] = function($trimmer, $s) {
    if ($trimmer->is('php')) {
		$tmp = rtrim($s);
		if (substr($tmp, -2) === '?>') {
    		$trimmer->fix('contains closing PHP tag ?>');
			return substr($tmp, 0, -2);
		}
    }
};

// lint Latte templates
$trimmer->tasks[] = function($trimmer, $s) {
    if ($trimmer->is('latte')) {
    	try {
			$template = new Nette\Templating\Template;
			$template->registerFilter(new Nette\Latte\Engine);
			$template->compile($s);
		} catch (Nette\Templating\FilterException $e) {
    		$trimmer->error($e->getMessage() . ($e->sourceLine ? " on line $e->sourceLine" : ''));
		}
    }
};

// lint Neon
$trimmer->tasks[] = function($trimmer, $s) {
    if ($trimmer->is('neon')) {
    	try {
    		Nette\Utils\Neon::decode($s);
		} catch (Nette\Utils\NeonException $e) {
    		$trimmer->error($e->getMessage());
		}
    }
};

// white-space remover
$trimmer->tasks[] = function($trimmer, $s) {
    $new = Strings::replace($s, "#[\t ]+(\r?\n)#", '$1'); // right trim
    if ($trimmer->is('php')) { // trailing trim
    	$new = rtrim($new) . PHP_EOL;
    } else {
    	$new = Strings::replace($new, "#(\r?\n)+$#", '$1');
    }
    if ($new !== $s) {
    	$bytes = strlen($s) - strlen($new);
   		$trimmer->fix("$bytes bytes of whitespaces");
   		return $new;
   	}
};

$ok = $trimmer->run(isset($options['d']) ? $options['d'] : getcwd());

exit($ok ? 0 : 1);

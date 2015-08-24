<?php
namespace SecondThoughts;

require_once('diff.php');
require_once('htmldiff/html_diff.php');
require_once("parsedown.php");

function markdown($text) {
	$markdown = new \Parsedown\Parsedown();
	return $markdown->text($text);
}

function isUpper($str) {
	return (bool)preg_match('/^\p{Lu}+$/u', $str);
}

function isLower($str) {
	return (bool)preg_match('/^\p{Ll}+$/u', $str);
}

function isPunc($str) {
	return (bool)preg_match('/^\p{P}+$/u', $str);
}

function isAccent($str) {
	return iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $str) !== $str;
}

/* getSpecial finds specific changes and updates the from/to text 
 * to notate capitaliation, lowercase, punctuation changes, etc.
 */

function getSpecial($deleted, $added) {
	//find single char diffs
	$fromlen = mb_strlen($deleted);
	$tolen = mb_strlen($added);

	if ($fromlen !== $tolen) {
		return "<del>$deleted</del><ins>$added</ins>";
	}
	
	$output = "";
	$subfrom = "";
	$subto = "";
	$prevspecial = null;
	for ($i=0; $i < $fromlen; $i++) {
		$from = mb_substr($deleted, $i, 1);
		$to = mb_substr($added, $i, 1);
		$special = null;
		if (mb_strtolower($from) === mb_strtolower($to)) {
			if (isUpper($from) and isLower($to)) {
				$special = 'lowercase';
			} elseif (isLower($from) and isUpper($to)) {
				$special = 'capitalize';
			}
		} elseif (isPunc($from) and isPunc($to)) {
			$special = 'punctuation';
		} elseif (isAccent($from) or isAccent($to)) {
			$special = 'diacritic';
		}
		
		if ($special === $prevspecial) {
			$subfrom .= $from;
			$subto .= $to;
		} else {
			if (strlen($subfrom) or strlen($subto)) {
				if ($subfrom === $subto) {
					$output .= $subfrom;
				} else {
					$output .= "<del class='$prevspecial'>$subfrom</del><ins class='$prevspecial'>$subto</ins>";
				}
			}
			$subfrom = $from;
			$subto = $to;
		}
		
		$prevspecial = $special;
	}

	if (strlen($subfrom) or strlen($subto)) {
		if ($subfrom === $subto) {
			$output .= $subfrom;
		} else {
			$output .= "<del class='$prevspecial'>$subfrom</del><ins class='$prevspecial'>$subto</ins>";
		}
	}
	
	$output = preg_replace("/ class=''/", '', $output);
	
	return $output;	
}

function secondDiff($from, $to) {
	$diff = html_diff($from, $to, true);
	
	preg_match_all(':(<del>(.*?)</del>)(<ins>(.*?)</ins>):', $diff, $matches, PREG_SET_ORDER);
	
	foreach ($matches as $m) {
		list($whole, $olddel, $deleted, $oldins, $added) = $m;

		//move spaces outside of element
		$addspace = '';
		if (mb_substr($deleted, -1) === ' ' and mb_substr($added, -1) === ' ') {
			$deleted = mb_substr($deleted, 0, -1);
			$added = mb_substr($added, 0, -1);
			$addspace = ' ';
		}
		
		$replacement = getSpecial($deleted, $added);
		
		if ($replacement !== $whole) {
			$diff = str_replace($whole, $replacement . $addspace, $diff);
		print "===========<br>\n";
		print ($whole) . "<br>\n-----------<br>\n" . ($replacement) . "<br>\n";
		}
	}
	
	return $diff;
}

if (PHP_SAPI === 'cli') {
	$f1 = file_get_contents($argv[1]);
	$f2 = file_get_contents($argv[2]);
	#print secondDiff($f1, $f2) . "\n";
	print $f1 . "\n\n\n";
	print markdown($f1) . "\n\n\n";
	print $f2 . "\n\n\n";
	print markdown($f2) . "\n\n\n";
	print secondDiff(markdown($f1), markdown($f2), true);
}

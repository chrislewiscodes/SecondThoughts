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
	
	$doOutput = function($from, $to, $special) {
		if (strlen($from) or strlen($to)) {
			if ($from === $to) {
				return $from;
			} elseif ($special) {
				return "<del class='$special'>$from</del><ins class='$special'>$to</ins>";
			} else {
				return "<del>$from</del><ins>$to</ins>";
			}
		}
	};
	
	$output = "";
	$subfrom = "";
	$subto = "";
	$prevspecial = null;
	
	for ($i=0; $i < $fromlen; $i++) {
		$from = mb_substr($deleted, $i, 1);
		$to = mb_substr($added, $i, 1);
		$special = null;
		if ($from !== $to) {
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
		}

		if ($special !== $prevspecial) {
			//char has definitely changed
			$output .= $doOutput($subfrom, $subto, $prevspecial);
			$prevspecial = $special;
			$subfrom = $from;
			$subto = $to;
		} else {
			$subto .= $to;
			$subfrom .= $from;
		}
	}

	$output .= $doOutput($subto, $subfrom, $prevspecial);
	
	return $output;	
}

function secondDiff($from, $to) {
	$diff = html_diff($from, $to, true);

	preg_match_all(':(<del>(.*?)</del>)(<ins>(.*?)</ins>):u', $diff, $matches, PREG_SET_ORDER);
	
	foreach ($matches as $m) {
		list($whole, $olddel, $deleted, $oldins, $added) = $m;

		// sometimes this regexp picks up multiple <del> clauses
		// so zap any extra ones
		
		$whole = preg_replace(':^.*</del>.*<del>:', '<del>', $whole);
		$olddel = preg_replace(':^.*</del>.*<del>:', '<del>', $olddel);
		$deleted = preg_replace(':^.*</del>.*<del>:', '', $deleted);

		$replacement = getSpecial($deleted, $added);
		
		if ($replacement !== $whole) {
			$diff = str_replace($whole, $replacement . $addspace, $diff);
			if (PHP_SAPI === 'cli') {
				print "===========<br>\n";
				print ($whole) . "<br>\n-----------<br>\n" . ($replacement) . "<br>\n";
			}
		}
	}

	$diff = preg_replace(': +</(ins|del)>:u', '</$1> ', $diff);

	//find added/deleted punctuation
	$diff = preg_replace(':<(ins|del)>(\p{P})</(ins|del)>:u', '<$1 class="punctuation">$2</$3>', $diff);
		
	return $diff;
}

if (PHP_SAPI === 'cli') {
	$f1 = file_get_contents($argv[1]);
	$f2 = file_get_contents($argv[2]);
	#print secondDiff($f1, $f2) . "\n";
	print $f1 . "\n-------------\n" . markdown($f1) . "\n=======================\n";
	print $f2 . "\n-------------\n" . markdown($f2) . "\n=======================\n";
	print secondDiff(markdown($f1), markdown($f2), true);
}

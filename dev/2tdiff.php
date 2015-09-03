<?php	
namespace SecondThoughts;

/*
 * Copyright 2015 Chris Lewis <chris@chrislewis.codes>
 *
 * Takes the output of HTMLDiff and adds some information about certain
 * proofreading-specific classes of differences: capitalization changes,
 * punctuation changes, diacritic changes, word transpositions.
 *
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA 02111-1307, USA.
 * or see http://www.gnu.org/
 *
 */

require_once('diff.php');
require_once('htmldiff/html_diff.php');
require_once("parsedown.php");

define('PARA', '⁋');

function markdown($text) {
	$text = str_replace("\r\n", "\n", $text);
	$text = str_replace("\r", "\n", $text);
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

function isSpace($str) {
	return (bool)preg_match('/^\s+$/', $str);
}

function isAccent($str) {
	return preg_match('/^\p{L}$/u', $str) and iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $str) !== $str;
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
			$addspace = '';
			while (substr($from, -1) === ' ' and substr($to, -1) === ' ') {
				$addspace .= ' ';
				$from = substr($from, 0, -1);
				$to = substr($to, 0, -1);
			}
			if ($from === $to) {
				return $from . $addspace;
			} elseif ($special) {
				return "<del class='$special'>$from</del><ins class='$special'>$to</ins>$addspace";
			} else {
				return "<del>$from</del><ins>$to</ins>$addspace";
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
			$diff = str_replace($whole, $replacement, $diff);
			if (PHP_SAPI === 'cli') {
				print ($whole) . "|  --->  " . ($replacement) . "|\n";
			}
		}
	}

	//$diff = preg_replace(': +</(ins|del)>:u', '</$1> ', $diff);

	//find added/deleted punctuation
	$diff = preg_replace(':<(ins|del)>(\p{P})</(ins|del)>:u', '<$1 class="punctuation">$2</$3>', $diff);
		
	return $diff;
}

function strToArray($str) {
	$result = array();
	for ($i=0, $l=mb_strlen($str); $i<$l; $i++) {
		$result[] = mb_substr($str, $i, 1);
	}
	return $result;
}

function cleanupText($text) {
	$text = trim($text);
	$text = str_replace("\r\n", "\n", $text);
	$text = str_replace("\r", "\n", $text);
	$text = preg_replace('/\n\s+\n/', "\n\n", $text);
	return $text;
}

function handleParagraphs(&$deleted, &$added) {
	//paragraphs get the actual line breaks removed. We'll handle the spacing in CSS.
	$deleted = str_replace(PARA . PARA, "</del><del class='paragraph'></del><del>", $deleted);
	$added = str_replace(PARA . PARA, "</ins><ins class='paragraph'></ins><ins>", $added);

	// single line breaks are preserved
	$deleted = str_replace(PARA, "</del>\n<del>", $deleted);
	$added = str_replace(PARA, "</ins>\n<ins>", $added);
}

function plainTextDiff($t1, $t2, $htmlize=false) {
	$MARKDOWN = '[\*\#\-\+]';

	$result = array();

	$t1 = cleanupText($t1);
	$t2 = cleanupText($t2);
	
	$links = array();
	
	//find links and replace them with hashes
	//world's most confusing regexp
	preg_match_all('/\!?\[[^\]]+\]\([^\)]+\)/', $t1 . $t2, $matches, PREG_SET_ORDER);
	foreach ($matches as $m) {
		$links[$m[0]] = md5($m[0]);
	}
	foreach ($links as $link => $hash) {
		$t1 = str_replace($link, " LINK:$hash:LINK ", $t1);
		$t2 = str_replace($link, " LINK:$hash:LINK ", $t2);
	}
	
	// call out paragraph separations as real chars
	$t1 = str_replace("\n", PARA, $t1);
	$t2 = str_replace("\n", PARA, $t2);
	
	$words1 = preg_split('/\s+/', $t1);
	$words2 = preg_split('/\s+/', $t2);
	
	$diffwords = \PaulButler\diff($words1, $words2, false);

	foreach ($diffwords as $worddiff) {
		if (is_array($worddiff)) {
			$deleted = implode(' ', $worddiff['d']);
			$added = implode(' ', $worddiff['i']);

			//print "'$deleted' => '$added'\n";
			
			$subdiffs = \PaulButler\diff(strToArray($deleted), strToArray($added), true);

			$subresult = array();
			$anyspecial = strpos($deleted, PARA) !== false || strpos($added, PARA) !== false;
			foreach ($subdiffs as $i => $subdiff) {
				if (is_array($subdiff)) {
					$subdeleted = implode('', $subdiff['d']);
					$subadded = implode('', $subdiff['i']);
					$special = null;

					//print "'$subdeleted' -> '$subadded'\n";

					$l1 = count($subdiff['d']);
					$l2 = count($subdiff['i']);
					
					if ($subdeleted === $subadded) {
						//this happens sometimes
						if (strlen($subadded)) {
							$subresult[] = $subadded;
						}
					} elseif (mb_strtolower($subdeleted) === mb_strtolower($subadded)) {
						if (isUpper($subdeleted) and isLower($subadded)) {
							$special = 'lowercase';
						} elseif (isLower($subdeleted) and isUpper($subadded)) {
							$special = 'capitalize';
						}
					} elseif ($l1 <= 1 and $l2 <= 1 and (isPunc($subdeleted) or isPunc($subadded))) {
						$special = 'punctuation';
					} elseif ($l1 <= 1 and $l2 <= 1 and (isAccent($subdeleted) or isAccent($subadded))) {
						$special = 'diacritic';
					}

					$anyspecial = $anyspecial || $special;

					//handle paragraph breaks
					handleParagraphs($subdeleted, $subadded);

					if ($subdeleted !== "") {
						$subresult[] = "<del" . ($special ? " class='$special'" : "") . ">$subdeleted</del>";
					}
					if ($subadded !== "") {
						$subresult[] = "<ins" . ($special ? " class='$special'" : "") . ">$subadded</ins>";
					}
				} else {
					$subresult[] = $subdiff;
				}
			}

			if ($anyspecial) {
				$result[] = implode('', $subresult);
			} else {
				handleParagraphs($deleted, $added);
				$result[] = "<del>$deleted</del><ins>$added</ins>";
			}
		} else {
			$result[] = $worddiff;
		}
	}
	
	$result = implode(' ', $result);
	$result = str_replace('<ins></ins>', '', $result);
	$result = str_replace('<del></del>', '', $result);
	
	$result = str_replace(PARA, "\n", $result);

	//try not to fuck up markdown syntax
	$result = preg_replace("~<(?:ins|del) class='paragraph'></(?:ins|del)>(<(?:ins|del)>$MARKDOWN)~", "\n\n\$1", $result);
	$result = preg_replace('/^<(del|ins)>(' . $MARKDOWN . '+)/m', '$2<$1>', $result);
	$result = preg_replace('/^(' . $MARKDOWN . '+)<(del|ins)>\s*/m', '$1 <$2>', $result);
	
	//restore links
	$result = preg_replace_callback('/LINK:(.+?):LINK/', function($m) {
		$str = $m[0];
		$str = preg_replace(':<del.*?>.+?</del>:', '', $str);
		$str = preg_replace(':</?ins.*?>:', '', $str);
		return $str;
	}, $result);
	
	foreach ($links as $link => $hash) {
		$result = str_replace("LINK:$hash:LINK", $link, $result);
	}
	
	
	if ($htmlize) {
		$result = markdown($result);

		//fix bug that inserts mailto links
		$result = preg_replace('~<a href="mailto:(/?(?:ins|del)>[^"]*)">.*?</a>~', '<$1>', $result);
	}

	return $result;
}

if (PHP_SAPI === 'cli') {
	$f1 = file_get_contents($argv[1]);
	$f2 = file_get_contents($argv[2]);
	#print secondDiff($f1, $f2) . "\n";
	print "$f1\n=======================\n";
	print "$f2\n=======================\n";
	//print secondDiff(markdown($f1), markdown($f2), true);
	print plainTextDiff($f1, $f2);
	//print "\n=====================\n";
	//print plainTextDiff($f1, $f2, true);
	print "\n";
}

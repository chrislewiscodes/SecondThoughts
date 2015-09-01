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

function isParagraphBreak($str) {
	return strpos($str, "\n\n") !== false;
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

function plainTextDiff($t1, $t2) {
	$MARKDOWN = '[\*\#\-\+]';
	$result = array();

	$t1 = cleanupText($t1);
	$t2 = cleanupText($t2);
	
	$startsame = '';
	$endsame = '';
	
	$i = 0;
	$l = min(mb_strlen($t1), mb_strlen($t2));
	while ($i < $l && mb_substr($t1, $i, 1) === mb_substr($t2, $i, 1)) {
		++$i;
	}

	if ($i) {
		$startsame = mb_substr($t1, 0, $i);
		$t1 = mb_substr($t1, $i);
		$t2 = mb_substr($t2, $i);
		$l -= $i;
		$result[] = $startsame;
	}

	$i=0;
	while ($i < $l && mb_substr($t1, -$i-1, 1) === mb_substr($t2, -$i-1, 1)) {
		++$i;
	}

	if ($i) {
		$endsame = mb_substr($t1, -$i);
		$t1 = mb_substr($t1, 0, -$i);
		$t2 = mb_substr($t2, 0, -$i);
		$l -= $i;
	}	

	// call out paragraph separations as real chars
	$words1 = preg_split('/[^\S\n]+/', $t1);
	$words2 = preg_split('/[^\S\n]+/', $t2);
	
	$diffwords = \PaulButler\diff($words1, $words2);
	
	foreach ($diffwords as $worddiff) {
		if (is_array($worddiff)) {
			$deleted = implode(' ', $worddiff['d']);
			$added = implode(' ', $worddiff['i']);
			
			$diffs = \PaulButler\diff(strToArray($deleted), strToArray($added));
			
			$subresult = array();
			foreach ($diffs as $i => $diff) {
				if (is_array($diff)) {
					$deleted = implode('', $diff['d']);
					$added = implode('', $diff['i']);
					$special = null;
					
					$l1 = count($diff['d']);
					$l2 = count($diff['i']);
					
					if ($deleted === $added) {
						//this happens sometimes
						if (strlen($added)) {
							$subresult[] = $added;
						}
					} elseif (mb_strtolower($deleted) === mb_strtolower($added)) {
						if (isUpper($deleted) and isLower($added)) {
							$special = 'lowercase';
						} elseif (isLower($deleted) and isUpper($added)) {
							$special = 'capitalize';
						}
					} elseif ($l1 <= 1 and $l2 <= 1 and (isPunc($deleted) or isPunc($added))) {
						$special = 'punctuation';
					} elseif ($l1 <= 1 and $l2 <= 1 and (isAccent($deleted) or isAccent($added))) {
						$special = 'diacritic';
					}
					
					//paragraph insertion/deletion
					$deleted = preg_replace('/\n{2,}/', "</del><del class='paragraph'></del>\n\n<del>", $deleted);
					$added = preg_replace('/\n{2,}/', "</ins><ins class='paragraph'></ins>\n\n<ins>", $added);

					//don't cross lines
					$deleted = preg_replace(':(?<!</del>)\n(?!<del>):', "</del>\n<del>", $deleted);
					$added = preg_replace(':(?<!</ins>)\n(?!<ins>):', "</ins>\n<ins>", $added);
		
					if ($deleted !== "") {
						$subresult[] = "<del" . ($special ? " class='$special'" : "") . ">$deleted</del>";
					}
					if ($added !== "") {
						$subresult[] = "<ins" . ($special ? " class='$special'" : "") . ">$added</ins>";
					}
				} else {
					$subresult[] = $diff;
				}
			}
			
			$result[] = implode('', $subresult);
		} else {
			$result[] = $worddiff;
		}
	}
	
	$result[] = $endsame;
	
	$result = implode(' ', $result);
	$result = str_replace('<ins></ins>', '', $result);
	$result = str_replace('<del></del>', '', $result);
	
	//try not to fuck up markdown syntax
	$result = preg_replace('/^<(del|ins)>(' . $MARKDOWN . '+)/m', '$2<$1>', $result);
	$result = preg_replace('/^(' . $MARKDOWN . '+)<(del|ins)>\s*/m', '$1 <$2>', $result);

	//only do paragraph breaks in actual paragraphs
	$result = preg_replace(':^(' . $MARKDOWN . '+.*)<(ins|del) class=[\'"]paragraph["\']><(ins|del)>$:', '$1', $result);

	return $result;
}

if (PHP_SAPI === 'cli') {
	$f1 = file_get_contents($argv[1]);
	$f2 = file_get_contents($argv[2]);
	#print secondDiff($f1, $f2) . "\n";
	print $f1 . "\n-------------\n" . markdown($f1) . "\n=======================\n";
	print $f2 . "\n-------------\n" . markdown($f2) . "\n=======================\n";
	//print secondDiff(markdown($f1), markdown($f2), true);
	print plainTextDiff($f1, $f2);
}

<?php
namespace SecondThoughts;

require_once('diff.php');

function strToArray($str) {
	$result = array();
	for ($i=0, $l=mb_strlen($str); $i<$l; $i++) {
		$result[] = mb_substr($str, $i, 1);
	}
	return $result;
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

function getDiffs($arr1, $arr2, $sep=' ', $depth=0) {
	$special = null;
	$result = array();
	$diffs = \PaulButler\diff($arr1, $arr2);
	foreach ($diffs as $i => $diff) {
		if (is_array($diff)) {
			$deleted = implode($sep, $diff['d']);
			$added = implode($sep, $diff['i']);
			
			if ($deleted === $added) {
				//this happens sometimes
				if (strlen($added)) {
					$result[] = $added;
				}
				continue;
			}
			
			//find single char diffs
			if (mb_strlen($deleted) <= 1 and mb_strlen($added) <= 1) {
				if (mb_strtolower($deleted) === mb_strtolower($added)) {
					if (isUpper($deleted) and isLower($added)) {
						$special = 'lowercase';
					} elseif (isLower($deleted) and isUpper($added)) {
						$special = 'capitalize';
					}
				} elseif (isPunc($deleted) and isPunc($added)) {
					$special = 'punctuation';
				} elseif (isAccent($deleted) or isAccent($added)) {
					$special = 'diacritic';
				}
				$result[] = array($deleted, $added, $special, $sep);
			} elseif ($depth===0) {
				//print "$deleted => $added\n";
				$subdiffs = getDiffs(strToArray($deleted), strToArray($added), '', $depth+1);
				//avoid over-optimizing simple substitutions
				$specials = false;
				foreach ($subdiffs as $sd) {
					if (is_array($sd) and $sd[2]) {
						$specials = true;
						break;
					}
				}
				if ($specials) {
					$result = array_merge($result, $subdiffs);
				} else {
					$result[] = array($deleted, $added, null, $sep);
				}
			} else {
				$result[] = array($deleted, $added, null, $sep);
			}
		} else {
			$result[] = $diff;
		}
	}
	
	$final = array();
	$string = array();
	foreach ($result as $word) {
		if (is_array($word)) {
			$final[] = implode($sep, $string);
			$string = array();
			$final[] = $word;
		} else {
			$string[] = $word;
		}
	}
	$final[] = implode($sep, $string);
	
	return $final;
}

if (PHP_SAPI === 'cli') {
	$f1 = file_get_contents($argv[1]);
	$f2 = file_get_contents($argv[2]);
	print htmlDiff($f1, $f2) . "\n";
}

function htmlDiff($f1, $f2) {
	$diffs = getDiffs(preg_split('/\s+/', $f1), preg_split('/\s+/', $f2));
	
	$l = count($diffs);
	
	ob_start();
	foreach ($diffs as $i => $diff) {
		if (is_array($diff)) {
			list($d, $a, $s, $sep) = $diff;
			if (($i>=2 and $diffs[$i-2]===array($a,$d,$s,$sep)) or ($i<$l-2 and $diffs[$i+2]===array($a,$d,$s,$sep))) {
				$s = 'transpose';
			}
			if ($d) {
				print "$sep<del class='$s'>$d</del>$sep";
			}
			if ($a) {
				print "$sep<ins class='$s'>$a</ins>$sep";
			}
		} else {
			print $diff;
		}
	}
	return ob_get_clean();
}
<?php
namespace PaulButler;

/*
    Paul's Simple Diff Algorithm v 0.1
    (C) Paul Butler 2007 <http://www.paulbutler.org/>
    May be used and distributed under the zlib/libpng license.
    
    This code is intended for learning purposes; it was written with short
    code taking priority over performance. It could be used in a practical
    application, but there are a few ways it could be optimized.
    
    Given two arrays, the function diff will return an array of the changes.
    I won't describe the format of the array, but it will be obvious
    if you use print_r() on the result of a diff on some test data.
    
    htmlDiff is a wrapper for the diff command, it takes two strings and
    returns the differences in HTML. The tags used are <ins> and <del>,
    which can easily be styled with CSS.  
*/

function diff($old, $new, $optimize=false, $dontoptimize=null){
    $matrix = array();
    $maxlen = 0;
    //print "===============================\n" . count($old) . " " . count($new) . "\nMemory: " . number_format(memory_get_usage()) . "\n";

	$startsame = array();
	$endsame = array();

	$i = 0;
	$l = min(count($old), count($new));
	while ($i < $l && $old[$i] === $new[$i]) {
		++$i;
	}

	if ($i) {
		$startsame = array_splice($old, 0, $i);
		array_splice($new, 0, $i);
		$l -= $i;
	}

	$i = count($old)-1;
	$j = count($new)-1;
	$c = 0;
	while ($i >= 0 and $j >= 0 and $old[$i] === $new[$j]) {
		--$i;
		--$j;
		++$c;
	}
	
	if ($c) {
		$endsame = array_splice($old, -$c);
		array_splice($new, -$c);
		$l -= $c;
	}	

    foreach($old as $oindex => $ovalue){
        $nkeys = array_keys($new, $ovalue);
        foreach($nkeys as $nindex){
            $matrix[$oindex][$nindex] = isset($matrix[$oindex - 1][$nindex - 1]) ?
                $matrix[$oindex - 1][$nindex - 1] + 1 : 1;
            if($matrix[$oindex][$nindex] > $maxlen){
                $maxlen = $matrix[$oindex][$nindex];
                $omax = $oindex + 1 - $maxlen;
                $nmax = $nindex + 1 - $maxlen;
            }
        }   
    }
    
    unset($matrix); //this is a huge memory hog that we can forget before recursing
    
    if($maxlen == 0) return array_merge(
    	$startsame,
    	array(array('d'=>$old, 'i'=>$new)),
    	$endsame
    );

    $result = array_merge(
	    $startsame,
        diff(array_slice($old, 0, $omax), array_slice($new, 0, $nmax), false),
        array_slice($new, $nmax, $maxlen),
        diff(array_slice($old, $omax + $maxlen), array_slice($new, $nmax + $maxlen), false),
        $endsame
    );

	if ($optimize) {
		//add a dummy same on the end
		do {
			$changed = false;
			$insame = false;
			$chunk = -1;
			$sames = array($chunk => array('length' => 0, 'start' => 0, 'rightChanges' => 0, ));
			foreach ($result as $i => $diff) {
				if (is_array($diff)) {
					if ($insame) {
						$insame = false;
					}
					$sames[$chunk]['rightChanges'] += (count($diff['d']) + count($diff['i']))/2;
				} else {
					if (!$insame) {
						$insame = true;
						$sames[++$chunk] = array(
							'start' => $i,
							'length' => 0,
							'rightChanges' => 0,
						);
					}
					$sames[$chunk]['length'] += 1;
				}
			}

			for ($i=$chunk; $i >= 0; $i--) {
				$same = $sames[$i];
				if ($same['length'] === 0) {
					continue;
				}
				if ($sames[$i-1]['rightChanges'] + $same['rightChanges'] > $same['length']) {
					//small match in the middle of large changes; break into insert/delete
					$slice = array_slice($result, $same['start'], $same['length']);
					
					//but don't optimize if it contains special chars
					if (!is_null($dontoptimize) and in_array($dontoptimize, $slice)) {
						continue;
					}
					
					array_splice($result, $same['start'], $same['length'], array(array('d' => $slice, 'i' => $slice)));
					$changed = true;
				}
			}

			#var_dump(implode('', $old));
			#var_dump(implode('', $new));
			#var_dump($sames);
			#var_dump($result);

			if ($changed) {
				for ($i=count($result)-2; $i >= 0; $i--) {
					if (is_array($result[$i]) and is_array($result[$i+1])) {
						$result[$i] = array_merge_recursive($result[$i], $result[$i+1]);
						array_splice($result, $i+1, 1);
					}
				}
			}
		} while ($changed);
	}

	return $result;
}

function htmlDiff($old, $new){
    $ret = '';
    $diff = diff(preg_split("/[\s]+/", $old), preg_split("/[\s]+/", $new));
    foreach($diff as $k){
        if(is_array($k))
            $ret .= (!empty($k['d'])?"<del>".implode(' ',$k['d'])."</del> ":'').
                (!empty($k['i'])?"<ins>".implode(' ',$k['i'])."</ins> ":'');
        else $ret .= $k . ' ';
    }
    return $ret;
}

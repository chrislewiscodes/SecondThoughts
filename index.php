<?php 
namespace SecondThoughts;

require_once("dev/2tdiff.php");

if (isset($_GET['refresh'])) {
	if (isset($_COOKIE['visited'])) {
		unset($_COOKIE['visited']);
	}
}

$diffs = array();
$revisions = array();
$revisiondates = array();

$languages = array('ES' => 'spanish', 'EN' => 'english');

$language = isset($_GET['lang']) && isset($languages[$_GET['lang']]) ? $_GET['lang'] : 'ES';

$translations = array(
	'updated' => 'actualizado',
	'update' => 'actualización',
	'project' => 'proyecto',
	'curatorial' => 'curatorial',
	'credits' => 'créditos',
	'program' => 'programa',
	'artists' => 'artistas',
	'more info' => 'mas info',
	'contact' => 'contact',
);

setcookie('visited', 'yes', null, '/');

function urlWithArgs($newargs) {
	parse_str($_SERVER['QUERY_STRING'], $oldargs);
	return '?' . http_build_query(array_merge($oldargs, $newargs));
}

function translate($text, $case='original') {
	global $language, $translations;
	if (!isset($translations[$text])) {
		return $text;
	}
	if ($language === 'ES') {
		$text = $translations[$text];
	}
	if (function_exists($case)) {
		return $case($text);
	} else {
		return $text;
	}
}

function lastUpdated() {
	global $revisiondates;
	print translate('updated', 'ucfirst') . ": ";
	print "<span class='revision rev-0'>" . date('j–n–Y', $revisiondates[0]) . "</span>";
	print "<span class='revision rev-1'>" . date('j–n–Y', $revisiondates[1]) . "</span>";
	print "<span class='revision rev-2'>" . date('j–n–Y', $revisiondates[2]) . "</span>";
}

function section($section) {
	global $revisions, $revisiondates, $diffs, $languages, $language;
	
	$dir = "content/{$languages[$language]}/$section";
	$files = @scandir($dir, SCANDIR_SORT_DESCENDING);
	if (!$files) {
		//either directory does not exist, or has no files in it
		print "No section “{$section}” found!";
		return false;
	}

	foreach ($files as $i => $filename) {
		if ($i>=3) {
			return;
		}

		$fullpath = "$dir/$filename";

		if (preg_match('/\b\d{2,4}\D\d{2}\D\d{2}\b/', $filename, $m)) {
			$date = strtotime($m[0]);
		} else {
			$date = filemtime($fullpath);
		}

		if (!isset($revisiondates[$i]) or $date > $revisiondates[$i]) {
			$revisiondates[$i] = $date;
		}
		
		$revisions[$i][$section] = markdown(file_get_contents($fullpath));
		if ($i > 0) {
			$diffs[$i][$section] = secondDiff($revisions[$i][$section], $revisions[$i-1][$section]);
		} 

		print "<div class='revision rev-{$i}'>";
		if ($i===0) {
			print $revisions[$i][$section];
		} else {
			print $diffs[$i][$section];
		}
		print "</div>";
	}
}

?>
<!DOCTYPE html>
<html>
<head>
	<meta charset="UTF-8">
	<title>Second Thoughts</title>
	<link rel="stylesheet" type="text/css" href="css/main.css">
	<meta name="viewport" content="initial-scale=1">
</head>
<body class='rev-0'>
<div id="extra-parameters">
	<div id="nav-toggle"><span></span></div>
	<div id="nav">
		<ul>
			<li>
				<a href="#" data-link="gen-description"><?php translate('project', 'ucfirst'); ?></a>
			</li>
			<li>
				<a href="#" data-link="curatorial"><?php translate('curatorial', 'ucfirst'); ?></a>
			</li>
			<li>
				<a href="#" data-link="credits"><?php translate('credits', 'ucfirst'); ?></a>
			</li>
			<li>
				<a href="#" data-link="program"><?php translate('program', 'ucfirst'); ?></a>
			</li>
			<li>
				<a href="#" data-link="artists"><?php translate('artists', 'ucfirst'); ?></a>
			</li>
			<li>
				<a href="#" data-link="more-info"><?php translate('more info', 'ucfirst'); ?></a>
			</li>
			<li>
				<a href="#" data-link="contact"><?php translate('contact', 'ucfirst'); ?></a>
			</li>
		</ul>	
	</div>
	<div id="language-toggle"><?php 
		foreach ($languages as $short=>$long) { 
			if ($short !== $language) {
				print "<a href='" . urlWithArgs(array('lang' => $short, 'shownote' => '1')) . "'>$short</a>";
				break;
			}
		}
	?></div>
	<h1 id="logo"><?php section('header'); ?></h1>
	<h1 id="background-type">Second Thoughts</h1>
	
	<div class="content">
		<div class="col colone">
			<div class="gen-description section">
				<h2><?php print translate('project', 'ucfirst'); ?></h2>			
				<?php section('proyecto'); ?>
			</div>
			<div class="curatorial section">
				<h2><?php print translate('curatorial', 'ucfirst'); ?></h2>
				<?php section('curatorial'); ?>
			</div>
			<div class="credits section">
				<h2><?php print translate('credits', 'ucfirst'); ?></h2>
				<?php section('creditos'); ?>
			</div>
		</div>
		<div class="col coltwo">
			<h2 class="section-title"><?php print translate('program', 'ucfirst'); ?></h2>		
			<?php section('programa'); ?>
		</div>
		<div class="col colthree">
			<h2 class="section-title"><?php print translate('artists', 'ucfirst'); ?></h2>
			<?php section('artists'); ?>
		</div>
		<div class="col colfour">
			<div class="more-info section">
				<h2><?php print translate('more info', 'ucfirst'); ?></h2>
				<?php section('info'); ?>
			</div>
			<div class="contact section">
				<h2><?php print translate('contact', 'ucfirst'); ?></h2>
				<?php section('contacto'); ?>
			</div>
		</div>		
	</div>

	<div id="counter">
		<?php lastUpdated(); ?>
	</div>
</div>

<div id="overlay"<?php if (!isset($_COOKIE['visited']) or isset($_GET['shownote'])): ?> class="overlay-on" <?php endif ?>>
	<div id="message">
		<div id="close">
			<span></span>
		</div>
		<p><?php if ($language === 'EN'): ?>
			This site is constantly changing; its content is edited and published in real time. Constantly refresh it to stay informed of our updates.
		<?php else: ?>
			Este sitio está en constante cambio; su contenido es editado y publicado en tiempo real. Asegúrate de revisarlo constantemente para mantenerte informado de futuras actualizaciones.
		<?php endif; ?><br /><br />
			<?php lastUpdated(); ?>
		</p>
	</div>
</div>

<?php 
	print "<!-- "; 
	var_dump($revisions); 
	var_dump($diffs); 
	print " -->";
?>

<script src="https://ajax.googleapis.com/ajax/libs/jquery/2.1.4/jquery.min.js"></script>
<script src="js/init.js"></script>
</body>
</html>

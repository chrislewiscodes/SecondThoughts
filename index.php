<?php 
namespace SecondThoughts;

require_once("dev/2tdiff.php");

$diffs = array();
$revisions = array();
$revisiondates = array();

$languages = array('ES' => 'spanish', 'EN' => 'english');

$language = isset($_GET['lang']) && isset($languages[$_GET['lang']]) ? $_GET['lang'] : 'ES';

setcookie('visited', 'yes', null, '/');

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
			$diffs[$i][$section] = html_diff($revisions[$i][$section], $revisions[$i-1][$section]);
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
				<a href="#" data-link="gen-description">Proyecto</a>
			</li>
			<li>
				<a href="#" data-link="curatorial">Curatorial</a>
			</li>
			<li>
				<a href="#" data-link="credits">Creditos</a>
			</li>
			<li>
				<a href="#" data-link="visit-1">Visita 1</a>
			</li>
			<li>
				<a href="#" data-link="visit-2">Visita 2</a>
			</li>
			<li>
				<a href="#" data-link="visit-3">Visita 3</a>
			</li>
			<li>
				<a href="#" data-link="exhibition">Exhibición</a>
			</li>
			<li>
				<a href="#" data-link="publication">Publicación</a>
			</li>
			<li>
				<a href="#" data-link="artist-1">David Reinfurt</a>
			</li>
			<li>
				<a href="#" data-link="artist-2">Daniel van der Velden</a> 
			</li>
			<li>
				<a href="#" data-link="artist-3">Constant Dullaart</a>
			</li>
			<li>
				<a href="#" data-link="more-info">Mas Info</a>
			</li>
			<li>
				<a href="#" data-link="contact">Contact</a>
			</li>
		</ul>	
	</div>
	<div id="language-toggle"><?php 
		foreach ($languages as $short=>$long) { 
			if ($short !== $language) {
				$args = parse_str($_SERVER['QUERY_STRING']);
				$args['lang'] = $short;
				print "<a href='?" . http_build_query($args) . "'>$short</a>";
				break;
			}
		}
	?></div>
	<h1 id="logo"><?php section('header'); ?></h1>
	<h1 id="background-type">Second Thoughts</h1>
	
	<div class="content">
		<div class="col colone">
			<div class="gen-description section">
				<h2>Proyecto</h2>			
				<?php section('proyecto'); ?>
			</div>
			<div class="curatorial section">
				<h2>Curatorial</h2>
				<?php section('curatorial'); ?>
			</div>
			<div class="credits section">
				<h2>Créditos</h2>
				<?php section('creditos'); ?>
			</div>
		</div>
		<div class="col coltwo">
			<h2 class="section-title">Programa</h2>		
			<?php section('programa'); ?>
		</div>
		<div class="col colthree">
			<h2 class="section-title">Artistas</h2>
			<?php section('artists'); ?>
		</div>
		<div class="col colfour">
			<div class="more-info section">
				<h2>Mas info</h2>
				<?php section('info'); ?>
			</div>
			<div class="contact section">
				<h2>Contact</h2>
				<?php section('contacto'); ?>
			</div>
		</div>		
	</div>

	<div id="counter">
		Last Updated: 
		<!-- this has to be done after the sections have been output above -->
		<span class='revision rev-0'><?php echo date('F j, Y', $revisiondates[0]); ?></span>
		<span class='revision rev-1'><?php echo date('F j, Y', $revisiondates[1]); ?></span>
		<span class='revision rev-2'><?php echo date('F j, Y', $revisiondates[2]); ?></span>
	</div>
</div>

<div id="overlay"<?php if (!isset($_COOKIE['visited'])): ?> class="overlay-on" <?php endif ?>>
	<div id="message">
		<div id="close">
			<span></span>
		</div>
		<p>
			Este sitio está en constante cambio; su contenido es editado y publicado en tiempo real. Asegúrate de revisarlo constantemente para mantenerte informado de futuras actualizaciones.<br /><br />
			Last Updated: 
			<!-- this has to be done after the sections have been output above -->
			<span class='revision_date'><?php echo date('F j, Y', $revisiondates[0]); ?></span>
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

<?php 
namespace SecondThoughts;

require_once("dev/2tdiff.php");

$diffs = array();
$plaintext = array();
$revisions = array();
$revisiondates = array();

$languages = array('ES' => 'spanish', 'EN' => 'english');

if (PHP_SAPI === 'cli') {
	$language = isset($argv[1]) && isset($languages[$argv[1]]) ? $argv[1] : 'ES';
} else {
	$language = isset($_GET['lang']) && isset($languages[$_GET['lang']]) ? $_GET['lang'] : 'ES';
}

# all lowercase here; you can specify case in calls to translate()
$translations = array(
	'updated' => 'actualizado',
	'update' => 'actualización',
	'project' => 'proyecto',
	'curatorial abstract' => 'resumen curatorial',
	'credits' => 'créditos',
	'program' => 'programa',
	'participants' => 'participantes',
	'more references' => 'más referencias',
	'contact' => 'contacto',
	'subscribe' => 'suscríbete',
);

function urlWithArgs($newargs) {
	parse_str($_SERVER['QUERY_STRING'], $oldargs);
	return '?' . http_build_query(array_merge($oldargs, $newargs));
}

# call translate with the English word, optionally specify case with 'ucfirst', 'ucwords', or 'upper'
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
	print "<span class='last-updated rev-0'>" . date('d/m/Y', $revisiondates[0]) . "</span>";
	print "<span class='last-updated rev-1'>" . date('d/m/Y', $revisiondates[1]) . "</span>";
	print "<span class='last-updated rev-2'>" . date('d/m/Y', $revisiondates[2]) . "</span>";
}

function section($section) {
	global $revisions, $plaintext, $revisiondates, $diffs, $languages, $language;
	
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
	
		$plaintext[$i][$section] = file_get_contents($fullpath);
		$revisions[$i][$section] = markdown($plaintext[$i][$section]);
		if ($i > 0) {
			$diffs[$i][$section] = plainTextDiff($plaintext[$i][$section], $plaintext[$i-1][$section], true);
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
	<script src="https://ajax.googleapis.com/ajax/libs/jquery/2.1.4/jquery.min.js"></script>
	<script src="js/init.js"></script>

	<link rel="canonical" href="http://secondthoughts.mx/">

	<!-- adding favicons -->
	<link rel="apple-touch-icon" sizes="57x57" href="apple-icon-57x57.png">
	<link rel="apple-touch-icon" sizes="60x60" href="apple-icon-60x60.png">
	<link rel="apple-touch-icon" sizes="72x72" href="apple-icon-72x72.png">
	<link rel="apple-touch-icon" sizes="76x76" href="apple-icon-76x76.png">
	<link rel="apple-touch-icon" sizes="114x114" href="apple-icon-114x114.png">
	<link rel="apple-touch-icon" sizes="120x120" href="apple-icon-120x120.png">
	<link rel="apple-touch-icon" sizes="144x144" href="apple-icon-144x144.png">
	<link rel="apple-touch-icon" sizes="152x152" href="apple-icon-152x152.png">
	<link rel="apple-touch-icon" sizes="180x180" href="apple-icon-180x180.png">
	<link rel="icon" type="image/png" sizes="192x192"  href="android-icon-192x192.png">
	<link rel="icon" type="image/png" sizes="32x32" href="favicon-32x32.png">
	<link rel="icon" type="image/png" sizes="96x96" href="favicon-96x96.png">
	<link rel="icon" type="image/png" sizes="16x16" href="favicon-16x16.png">
	<link rel="manifest" href="manifest.json">
	<meta name="msapplication-TileColor" content="#ffffff">
	<meta name="msapplication-TileImage" content="ms-icon-144x144.png">
	<meta name="theme-color" content="#ffffff">

	<!-- Google Analytics -->
	<script>
	(function(i,s,o,g,r,a,m){i['GoogleAnalyticsObject']=r;i[r]=i[r]||function(){
	(i[r].q=i[r].q||[]).push(arguments)},i[r].l=1*new Date();a=s.createElement(o),
	m=s.getElementsByTagName(o)[0];a.async=1;a.src=g;m.parentNode.insertBefore(a,m)
	})(window,document,'script','//www.google-analytics.com/analytics.js','ga');
	ga('create', 'UA-67917323-1', 'auto');
	ga('send', 'pageview');
	</script>
</head>
<body class='rev-0'>

<script> 
	//apply this before anything becomes visible
	if (window.location.href.indexOf('fade') >= 0) {
		document.getElementsByTagName('body')[0].style.opacity = 0;
	}
</script>

<div id="overlay" class="overlay-on">
	<div id="message" style='visibility:hidden'>
		<div id="close">
			<span></span>
		</div>
		<?php if ($language === 'EN'): ?>
			<p>This site is constantly changing; it records and displays its edits and revisions over time. Please keep checking back to see our updates.</p>
		<?php else: ?>
			<p>Este sitio está en constante cambio; registra y muestra su proceso de edición y variaciones conforme pasa el tiempo. No dejes de visitarlo para ver nuestras actualizaciones.</p>
		<?php endif; ?>
	</div>
</div>

<div id="extra-parameters">
	<div id="nav-toggle"><span></span></div>
	<div id="nav">
		<ul>
			<li>
				<a href="#" data-link="gen-description"><?php print translate('project', 'ucfirst'); ?></a>
			</li>
			<li>
				<a href="#" data-link="curatorial"><?php print translate('curatorial abstract', 'ucfirst'); ?></a>
			</li>
			<li>
				<a href="#" data-link="credits"><?php print translate('credits', 'ucfirst'); ?></a>
			</li>
			<li>
				<a href="#" data-link="program"><?php print translate('program', 'ucfirst'); ?></a>
			</li>
			<li>
				<a href="#" data-link="participants"><?php print translate('participants', 'ucfirst'); ?></a>
			</li>
			<li>
				<a href="#" data-link="more-info"><?php print translate('more references', 'ucfirst'); ?></a>
			</li>
			<li>
				<a href="#" data-link="contact"><?php print translate('contact', 'ucfirst'); ?></a>
			</li>
		</ul>	
	</div>
	<div id="language-toggle"><?php 
		foreach ($languages as $short=>$long) { 
			if ($short !== $language) {
				if (PHP_SAPI === 'cli') {
					// in cron job, use static link
					print "<a class='fade' href='{$long}.html'>$short</a>";
				} else {
					print "<a class='fade' href='?lang=$short'>$short</a>";
				}
				break;
			}
		}
	?></div>
	<h1 id="logo"><?php section('header'); ?></h1>
	<a id="alumnos" href="http://alumnos47.org/" target="_blank" alt="Fundación Alumnos47">Alumnos</a>
	<h1 id="background-type">Second Thoughts</h1>
	
	<div class="content">
		<div class="col colone">
			<div class="gen-description section">
				<h2><?php print translate('project', 'ucfirst'); ?></h2>			
				<?php section('proyecto'); ?>
			</div>
			<div class="curatorial section">
				<h2><?php print translate('curatorial abstract', 'ucfirst'); ?></h2>
				<?php section('curatorial'); ?>
			</div>
			<div class="credits section">
				<h2><?php print translate('credits', 'ucfirst'); ?></h2>
				<?php section('creditos'); ?>
			</div>
		</div>
		<div class="col coltwo">
			<div class="program section">
				<h2><?php print translate('program', 'ucfirst'); ?></h2>		
				<?php section('programa'); ?>
			</div>
		</div>
		<div class="col colthree">
			<div class="participants section">
				<h2><?php print translate('participants', 'ucfirst'); ?></h2>
				<?php section('participantes'); ?>
			</div>
		</div>
		<div class="col colfour">
			<div class="more-info section">
				<h2><?php print translate('more references', 'ucfirst'); ?></h2>
				<?php section('info'); ?>
			</div>
			<div class="contact section">
				<h2><?php print translate('contact', 'ucfirst'); ?></h2>
				<?php section('contacto'); ?>
			</div>

			<!-- MAILCHIMP -->
			<form action="//secondthoughts.us11.list-manage.com/subscribe/post?u=e34c5ba197f6d60dce12b81b4&amp;id=a7ccdb3077" method="post" id="mc-embedded-subscribe-form" name="mc-embedded-subscribe-form" class="validate" target="_blank" novalidate>
			    <div style="position: absolute; left: -5000px;"><input type="text" name="b_e34c5ba197f6d60dce12b81b4_a7ccdb3077" tabindex="-1" value=""></div>
				<input type="email" value="" name="EMAIL" class="required email" id="mce-EMAIL" required placeholder="Email">
			    <input type="submit" value="<?php print translate('subscribe', 'ucfirst'); ?>" name="subscribe" id="mc-embedded-subscribe" class="button">
			    </div>
			</form>
			<!-- /MAILCHIMP -->			
			
			<!--
			<div>
			<a class="twitter-timeline" data-dnt="true" href="https://twitter.com/2ndthoughtsmx/lists/second-thoughts" data-widget-id="645010383527284736" data-tweet-limit="3" data-chrome="nofooter noborder noscrollbar transparent">Tweets from https://twitter.com/2ndthoughtsmx/lists/second-thoughts</a>
			<script>!function(d,s,id){var js,fjs=d.getElementsByTagName(s)[0],p=/^http:/.test(d.location)?'http':'https';if(!d.getElementById(id)){js=d.createElement(s);js.id=id;js.src=p+"://platform.twitter.com/widgets.js";fjs.parentNode.insertBefore(js,fjs);}}(document,"script","twitter-wjs");</script>
			</div>
			-->
		
		</div>		
	</div>

	<div id="counter">
		<?php lastUpdated(); ?>
	</div>
</div>

<script> 
	var lastupdated = $('#counter').clone();
	lastupdated.attr('id','');
	$('#message').append(lastupdated);
	positionMessage(); 
</script>

<!-- spacer in case some revisions are longer than the current version -->
<div id='bodybuilder' style='height:0px'></div>
</body>
</html>

function getUrlParameter(sParam)
{
	var result;
	$.each(window.location.search.substr(1).split('&'), function(i, equal) {
		var keyval = equal.split('=');
		if (keyval[0] === sParam) {
			result = keyval.length > 1 ? keyval[1] : true;
			return false;
		}
	})
	return result;
}  


var fadeDuration = 0.25;
var doFade = getUrlParameter('fade');

if (doFade) {
	$('body').css('opacity', 0);
}

var $window = $(window);
var windowWidth = $window.width();
var windowHeight = $window.height();

$window.on('resize', function() {
	windowWidth = $window.width();
	windowHeight = $window.height();
})

function positionMessage() {
	var messageHeight = $('#message').height();
	$('#message').css({
		'top': windowHeight/2-messageHeight/1.5+'px',
		'visibility': 'visible'
	});
}

$(function() {

var body = document.getElementsByTagName('body')[0];
var $body = $('body');

/* randomly rotate second thoughts */
var number = 1 + Math.floor(Math.random() * 360);

var widthNumber	 = 1 + (Math.random() * windowWidth);
var heightNumber = 1 + (Math.random() * windowHeight);

$('h1#background-type').css({'transform': 'rotate('+number+'deg)', 'left': widthNumber+'px', 'top': heightNumber+'px'});


/* toggle nav */
$('#nav-toggle').click(function() {
	$(this).toggleClass( 'active' );
	$('#nav').toggleClass( 'active' );
})

$('#logo').click(function() {
	$('#nav-toggle').removeClass('active');
	$('#nav').removeClass('active'); 
	body.className = 'rev-0';

	$('#nav').find('a').removeClass('selected');
	$('.section').removeClass('selected');

})

/* nav links */
$('#nav a').click(function(){
	var a = $(this);
	
	// close nav
	$('#nav-toggle').removeClass('active');
	$('#nav').removeClass('active');   
	navItem = a.attr('data-link');
	$body.removeClass('navigated');	
	//$('.section.selected').removeClass('selected');

	if(a.hasClass('selected')) {
		a.removeClass('selected');
		$body.animate({'scrollTop':0}, {'duration': 500});
		$('.section.selected').removeClass('selected');
	} else {
		var section = $('.section.'+navItem);
		$body.addClass('navigated');
		//$('#nav a.selected').removeClass('selected');
		a.addClass('selected');
		section.addClass('selected');
		
		$body.animate({'scrollTop':section.offset().top}, {'duration': 500});
	}

	if (event.preventDefault) { event.preventDefault(); } else { event.returnValue = false; }
})

/* make links open new windows */

$('a').each(function() {
	var a = $(this);
	if (a.attr('href').substr(0, 4) === 'http') {
		a.attr('target', '_blank');
	}
});

/* wrap images in .image */

$('ins img, del img').wrap('<div class="image"></div>');


var marks = getUrlParameter('marks');
var debug = getUrlParameter('debug');

var steps = ['old-version', 'old-hidden', 'new-hidden', 'new-marks', 'new-version'];
var stepcount = steps.length;

var tick = 0;
var interval = parseInt(marks) || 250;
var going = false;
var timeout;

var revs = 0;
while ($('.rev-' + (++revs)).length) {}
--revs;

function doMarks() {
	if (tick >= 0) {
		var rev = revs - (Math.floor(tick / stepcount) % revs);
		var step = tick % stepcount;
		var phase = steps[step];

		//skip "new to old" step between revisions which is the same
		if (rev > 1 && phase === 'new-version') {
			++tick;
			doMarks();
			return;
		}

		body.className = 'marks rev-' + rev + ' ' + phase;
	} else {
		body.className = 'rev-0';
	}

	debug && $('#bodyclass').text(body.className);

	if (going) {
		++tick;
		timeout = setTimeout(doMarks, interval);
	}
}

function startRotation() {
	going = true;
	tick = 0;
	doMarks();
}

function endRotation() { 
	going = false;
	clearTimeout(timeout); 
	tick = -1;
	doMarks();
}

function endRotationDelayed() {
	setTimeout(endRotation, interval*1.5);
}

window.startRotation = startRotation;
window.endRotation = endRotation;

debug && $body.append("<div id='bodyclass' style='position:absolute;top:0;left:0;padding:0.5em;'></div>")

// go back and forth responding to arrow keys
$(document).on('keyup', function(evt) {
	if (going) {
		return;
	}
	switch (evt.which) {
		case 37: //left
			going = false;
			tick = Math.max(-1, tick - 1);
			doMarks();
			break;
		
		case 39: //right
			going = false;
			++tick;
			doMarks();
			break;
	}
});


//scroll magic
var scrollTimeout;
$window.on('scroll', function(evt) {
	if ($body.hasClass('navigated') || $('#overlay.overlay-on').length) {
		return;
	}
	var nowScroll = $window.scrollTop();
	going || startRotation();
	scrollTimeout && clearTimeout(scrollTimeout);
	scrollTimeout = setTimeout(endRotationDelayed, interval);
});


// give image containers class

$('img').each(function() {
	$(this).parent().addClass('image-holder');
})

//only do this stuff if the overlay is showing
$('#overlay.overlay-on').each(function() {
	//just in case any dimensions have changed
	positionMessage();
	startRotation();

	function closeOverlay() {
		$('#overlay').removeClass('overlay-on');
		$('h1#background-type').addClass('show');
		$(document).off('.close');	
		$window.off('.close');
		endRotation();
	}
	

	$(document).on('click.close', function(evt) {
		var target = $(evt.target);
		//close overlay by clicking either the close button or outside of the overlay
		if (target.closest('#close').length || !target.closest('#message').length) {
			closeOverlay();
		}
	});
	
	$(document).on('keyup.close', function(evt) {
		if (evt.which === 27) { //ESC
			closeOverlay();
		}
	});
	
	$window.on('resize.close', positionMessage);
});

// fade in/out on certain links
$('a.fade').on('click', function(evt) {
	var url = this.href + (this.href.indexOf('?') >= 0 ? '&' : '?') + 'fade';
	//start the fade
	$body.css('opacity', 1);
	$body.animate({'opacity':0}, {'duration':fadeDuration*1000});
	setTimeout(function() {		
		window.location.href = url;
	}, fadeDuration*1000);
	return false;
});

if (doFade) {
	$body.css('opacity', 0);
	$body.animate({'opacity':1}, {'duration':fadeDuration*1000});
}

});


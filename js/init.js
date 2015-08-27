var windowWidth = $(window).width();
var windowHeight = $(window).height();

$(window).on('resize', function() {
	windowWidth = $(window).width();
	windowHeight = $(window).height();
})

function positionMessage() {
	var messageHeight = $('#message').height();
	$('#message').css('top', windowHeight/2-messageHeight/1.5+'px');
}

$(document).ready(function() {

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
	$('body').removeClass();	

	$('#nav').find('a').removeClass('selected');
	$('.section').removeClass('selected');

})

/* nav links */
$('#nav').find('a').click(function(){

	// close nav
	$('#nav-toggle').removeClass('active');
	$('#nav').removeClass('active');   
	navItem = $(this).attr('data-link');
	$('body').removeClass();	

	if( $(this).hasClass('selected')) {
		$('#nav').find('a').removeClass('selected');
		$('.section').removeClass('selected');

	} else {
		$('body').addClass('navigated');
		$(this).addClass('selected');
		$('.'+navItem+'').addClass('selected');
	}

	if (event.preventDefault) { event.preventDefault(); } else { event.returnValue = false; }
})

$('#close').click(function(){
	$('#overlay').removeClass('overlay-on');
	window.pageCycle && clearInterval(pageCycle); //clear interval	 
	$('h1#background-type').addClass('show');

});


//just in case any dimensions have changed
positionMessage();

/* make links open new windows */

$('.more-info a, .contact a').attr('target', '_blank');

/* wrap images in .image */

$('ins img, del img').wrap('<div class="image"></div>');


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

var marks = getUrlParameter('marks');

var steps = ['old-0', 'old-2', 'new-2', 'new-1', 'new-0'];
var stepcount = steps.length;
var body = document.getElementsByTagName('body')[0];

var revs = 0;
while ($('.rev-' + (++revs)).length) {}
--revs;

function doMarks(tick) {
	if (tick >= 0) {
		var rev = revs - (Math.floor(tick / stepcount) % revs);
		var step = tick % stepcount;
		var phase = steps[step];

		body.className = 'marks rev-' + rev + ' ' + phase;
	} else {
		body.className = 'rev-0';
	}

	$('#bodyclass').text(body.className);
}

$('body').append("<div id='bodyclass' style='position:absolute;top:0;left:0;padding:0.5em;'></div>")

if (marks = parseInt(marks)) {
	//if marks is a number, rotate through the revisions at time intervals
	var start = Date.now();
	setInterval(function() {
		var time = Date.now() - start;
		doMarks(Math.round(time / marks));
	}, marks);
} else {
	//otherwise go back and forth responding to arrow keys
	var tick = -1;
	$(document).on('keyup', function(evt) {
		switch (evt.which) {
			case 37: //left
				tick = Math.max(-1, tick - 1);
				doMarks(tick);
				break;
			
			case 39: //right
				++tick;
				doMarks(tick);
				break;
		}
	});
}


});


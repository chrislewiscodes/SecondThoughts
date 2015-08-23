$(document).ready(function() {

/* randomly rotate second thoughts */
var number = 1 + Math.floor(Math.random() * 360);
var windowWidth = $(window).width();
var windowHeight = $(window).height();

var widthNumber  = 1 + (Math.random() * windowWidth);
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

/* position message */

messageHeight = $('#message').height();
windowHeight = $(window).height();

$('#message').css('top', windowHeight/2-messageHeight/1.5+'px');


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

var rotate = getUrlParameter('rotate');
var stop = getUrlParameter('stop');
var layout = getUrlParameter('layout');
var marks = getUrlParameter('marks');
var show = getUrlParameter('show');
var hide = getUrlParameter('hide');
var sans = getUrlParameter('sans');
var logo = getUrlParameter('logo');
var image = getUrlParameter('image');
var invert = getUrlParameter('invert');

console.log(marks);

if (layout === 'true') {
	$('#extra-parameters').addClass('layout');
}

if (sans === 'true') {
	$('#extra-parameters').addClass('sans');
    $('#nav').addClass('sans');
}


if (image === 'true') {
    // make position sensitive to size and document's width
    var posx = (Math.random() * ($(document).width())).toFixed();
    var posy = (Math.random() * ($(document).height())).toFixed();

    $('#extra-parameters').addClass('image');
    $('.image').each(function(){
        $(this).css({'top': +posy+'px', 'left': +posx+'px'});
    });

}

if (invert === 'true') {
    $('#extra-parameters').addClass('invert');
    $('body').css({'color': 'black', 'background-color': 'white'});
    $('h1#background-type').css({'opacity': '.1'});    
}


var rev=0;
var step=0;

var lastFixPos = 0;


function scrollHandler() {
    var diff = Math.abs($(window).scrollTop() - lastFixPos);
    if(diff > rotate && !$('body').hasClass('navigated')) {
        // cycle page class
        $('body').removeClass();
        $('body').addClass('v'+i);
        $('#logo').addClass('logo'+i);

        if($('#logo').hasClass('logo2')) {
            $('#logo').removeClass();
            $('#logo').html('Second Thoughts <span class="delete">(Edición 2015–16)</span>');
        }

        if($('#logo').hasClass('logo1')) {
            $('#logo').removeClass();
            $('#logo').html('<span class="delete">The</span> Second Thoughts <span class="delete">Series</span> (Edición 2015–16)');
        }

        if($('#logo').hasClass('logo0')) {
            $('#logo').removeClass();
            $('#logo').html('Second Thoughts');
        }
    
        versionHeight = $('#v'+i).height();

        $('#extra-parameters').height(versionHeight+'px');  
        
        // increment counter
        $('.number').html(i);

        if ( marks === 'true' && hide !== 'true' ) {
            // every 2+0 itterations move to the next version
            if (n%2 === 0) {
                i++;
            }
            // every 2+1 itterations move add marks
            if (n%2 === 1 ) {
                $('body').addClass('marks');
            }

        } else if ( marks === 'true' && hide === 'true' ) {
            // every 3 itterations move to the next version
            if (n%3 === 0) {
                i++;
            }

            // every 3+1 itterations move add marks
            if (n%3 === 1 ) {
                $('body').addClass('marks');
            }

            // every 3+2 itterations move add marks and hide content
            if (n%3 === 2) {
                $('body').addClass('marks');
                $('body').addClass('hide');
            }
        }

        else {
            // every 3 itterations move to the next version
            i++;
        }    

        n++
        
        // reset cycle  
        if (i > 2) {
            i=0;
        }

        lastFixPos = $(window).scrollTop();

    }

    /*

    var hoverInterval;

    function doStuff() {
        $('body').removeClass();
        $('body').addClass('v'+i);

        // increment counter
        $('.number').html(i);   

        i++
        
        // reset cycle  
        if (i > 2) {
            i=0;
        }         
    }

    $( '.section' ).hover(
      function() {
        hoverInterval = setInterval(doStuff, 500);    
        $(this).addClass('hover-item');
      }, function() {
        clearInterval(hoverInterval);
        $('body').removeClass();        
        $(this).removeClass('hover-item');

      }
    );
    */

}

/*
window.onscroll = scrollHandler;  
var scrollTimer;
$(window).scroll(function() {
    scrollTimer && clearTimeout(scrollTimer);
    scrollTimer = setTimeout(function() {
        if(!$('body').hasClass('navigated')) {
            $('body').removeClass();
        }
        // set counter to 0
        i=0;
    }, stop);
});

var pageCycle = setInterval(scrollHandler, 10);
*/

if (marks) {
	var steps = 6; //old-0 old-1 old-2 new-2 new-1 new-0
	var body = document.getElementsByTagName('body')[0];
	
	var revs = 0;
	while ($('.rev-' + (++revs)).length) {}
	--revs;

	function doMarks(tick) {
		if (tick >= 0) {
			var rev = revs - (Math.floor(tick / steps) % revs);
			var step = tick % steps;
			var oldnew = step < steps/2 ? 'old' : 'new';
			var phase = oldnew === 'old' ? step : steps - step - 1;

			body.className = 'marks rev-' + rev + ' ' + oldnew + '-' + phase;
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
}


});


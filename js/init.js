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
    clearInterval(pageCycle); //clear interval   
    $('h1#background-type').addClass('show');

});

/* position message */

messageHeight = $('#message').height();
windowHeight = $(window).height();

$('#message').css('top', windowHeight/2-messageHeight/1.5+'px');


function getUrlParameter(sParam)
{
    var sPageURL = window.location.search.substring(1);
    var sURLVariables = sPageURL.split('&');
    for (var i = 0; i < sURLVariables.length; i++) 
    {
        var sParameterName = sURLVariables[i].split('=');
        if (sParameterName[0] == sParam) 
        {
            return sParameterName[1];
        }
    }
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


i=0;
n=0;

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
            // every 1 itterations move to the next version
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


window.onscroll = scrollHandler;  

$(window).scroll(function() {
    clearTimeout($.data(this, 'scrollTimer'));
    $.data(this, 'scrollTimer', setTimeout(function() {
        if(!$('body').hasClass('navigated')) {
            $('body').removeClass();
        }
        // set counter to 0
        //i=0;
        $('#number').html(i);
    }, stop));
});

var pageCycle = setInterval(scrollHandler, 10);



});


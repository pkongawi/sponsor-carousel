(function (sponsorCarousel, $, undefined) {
    
    $('.sponsors').slick({
        infinite: true,
        slidesToShow: 3,
        slidesToScroll: 1,
        dots: true,
        arrows: false,
      });

}(window.sponsorCarousel = window.sponsorCarousel || {}, jQuery));

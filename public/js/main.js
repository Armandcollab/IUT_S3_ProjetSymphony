$(function() {
    $('.season_nav').on('click', function(e) {
        e.preventDefault();
        $(".episodes").slideToggle();
    });
});
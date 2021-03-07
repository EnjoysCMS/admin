$(document).ready(function () {

    $('.nav-sidebar').addClass('nav-flat');
    $('.nav-sidebar').addClass('nav-child-indent');

    let url = window.location;
    let element = $('.nav-link').filter(function () {
        return this.href === url || url.href.indexOf(this.href) === 0;
    }).addClass('active').parents().addClass('menu-open');

});

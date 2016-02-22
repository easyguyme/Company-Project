$(function() {

  function getQueryString(name) {
      var reg = new RegExp('(^|&)' + name + '=([^&]*)(&|$)', 'i');
      var r = window.location.search.substr(1).match(reg);
      if (r != null) {
          return unescape(r[2]);
      }
      return null;
  }

  $('.all-cases').delegate('.case-item', 'click', function() {
    if (!$(this).hasClass('active')) {
      $(this).addClass('active').siblings().removeClass('active');
      $('#' + $(this).attr('id') + '_mod').addClass('show').siblings().removeClass('show');
    }
  });

  function init() {
    if (brand = getQueryString('brand')) {
      $('#' + brand).addClass('active').siblings().removeClass('active');
      $('#' + brand + '_mod').addClass('show').siblings().removeClass('show');
    }
  }

  init();

});

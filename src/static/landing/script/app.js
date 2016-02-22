$(function() {
  /**
   * Get item from localstorage
   * @param  string The unique identification in localstorage
   * @return Object The value matched the key
   */
  function getLocalItem(key) {
    var key = Base64.encode(key);
    var itemJson = window.localStorage.getItem(key);
    if(itemJson) {
      itemJson = Base64.decode(itemJson);
    }
    var item = JSON.parse(itemJson);
    return item;
  };
 /**
   * Remove item from localstorage
   * @param  string The unique identification in localstorage
   */
  function removeLocalItem(key){
    var key = Base64.encode(key);
    window.localStorage.removeItem(key);
  }

  /**
   * Check tokenInfo from server  whether token has expired or not
   */
  function checkAccessToken() {
    $.ajax({
      type: "GET",
      url: "/api/site/get-accesstoken",
      success: function(data) {
        if(data.tokenInfo === null){
          removeLocalItem('currentUser');
        }
        checkLoginStatus();
      },
      error: function(XMLHttpRequest, textStatus, errorThrown) {
      }
    });

  }

  /**
   * Check the user whether has logined from the localstorage.
   */
  function checkLoginStatus() {
    var currentUser = getLocalItem('currentUser');

    if(currentUser) {
      $('#useLink').hide();
      $('#loginLink').hide();
      $('#avatarLink').show();
      $('#avatarLink img').attr('src', currentUser.avatar + '?imageView/1/w/30/h/30');
      $('#avatarLink .avatar-name').text(currentUser.name);
    } else {
      $('#useLink').show();
      $('#loginLink').show();
      $('#avatarLink').hide();
    }
  }

  /**
   * Display the back to top button when user scroll leave the top
   */
  function addBackToTopBtn() {
    $(window).on('scroll', function() {
      if ($(document).scrollTop() > 0) {
        $('.button-backtotop').show();
      } else {
        $('.button-backtotop').hide();
      }
    });

    $('.button-backtotop').on('click', function() {
      $('body').animate({scrollTop: 0}, 500);
    });
  }

  /**
   * Bind the event on element which has class '.helpdesk-consultation' to popup a helpdesk dialog
   */
  function bindHelpdeskEvent() {
    $('.helpdesk-consultation').on('click', function() {
      var left = window.innerWidth - 400, top = window.innerHeight - 450;
      var domain = location.protocol + '//' + location.host;
      var cid = helpdeskAccountId ? helpdeskAccountId : '54f6cfef8f5e88b96a8b4567';
      var url = domain + '/chat/client?cid=' + cid + '#bottom';
      var params = 'height=450,width=400,left=' + left + ',top=' + top + ',toolbar=no,menubar=no,scrollbars=no,resizable=no,location=no,status=no';
      window.open(url, 'newwindow', params);
    });
  }

  /**
   * Bind the event on element which has class '.helpdesk-feedback' to popup a feedback dialog
   */
  function bindFeedbackEvent() {
    $('.helpdesk-feedback').on('click', function() {
      var options = {
        host: location.hostname,
        user: {
          accountId: '54f6cfef8f5e88b96a8b4567',
          origin: 'visitor',
          fields: [{
              label: 'feedback_customer_name',
              name: 'name',
              value: '',
              type: 'text',
              readonly: false
            }, {
              label: 'feedback_customer_email',
              name: 'email',
              value: '',
              type: 'email',
              readonly: false
            }]
        }
      };
      Feedback.config(options);
      Feedback.open();
    });
  }

  /**
   * Bind the toggle event to the right corner icon
   */
  function bindToggleIcon() {
    var menu = document.getElementById('menu'),
        WINDOW_CHANGE_EVENT = ('onorientationchange' in window) ? 'orientationchange' : 'resize';

    function toggleHorizontal() {
      [].forEach.call(
        document.getElementById('menu').querySelectorAll('.custom-can-transform'),
        function(el){
          el.classList.toggle('pure-menu-horizontal');
        }
      );
    };

    function toggleMenu() {
      // set timeout so that the panel has a chance to roll up
      // before the menu switches states
      if (menu.classList.contains('open')) {
        setTimeout(toggleHorizontal, 500);
      } else {
        toggleHorizontal();
      }
      menu.classList.toggle('open');
      document.getElementById('toggle').classList.toggle('x');
    };

    function closeMenu() {
      if (menu.classList.contains('open')) {
        toggleMenu();
      }
    }

    document.getElementById('toggle').addEventListener('click', function (e) {
      toggleMenu();
    });

    window.addEventListener(WINDOW_CHANGE_EVENT, closeMenu);
  }

  /**
   * Detect the current device whether is mobile, if it is mobile, hide the fixed right sidebar
   */
  function detectMobileDevice() {
    var md = new MobileDetect(window.navigator.userAgent);
    if (md.phone() !== null) {
      $('.extra-wrapper').hide();
      $('.header').addClass('mobile');
      $('.footer').removeClass('landing-page');
    }
  }

  function init() {
    checkAccessToken();
    addBackToTopBtn();
    bindHelpdeskEvent();
    bindFeedbackEvent();
    bindToggleIcon();
    detectMobileDevice();
  }

  init();
});

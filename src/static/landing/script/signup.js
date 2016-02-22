$(document).ready(function () {
  var verificationCode, validate_pic, get_verification;

  // Default form
  var current_form = '.form-signup';
  // Required field default error prompt message
  var prompt_info = '请输入该字段';
  // Error field default selector
  var prompt_form_control = '.form-control-error';

  // storage verification code which return from backend api
  verificationCode = '';

  function getQueryString(name) {
      var reg = new RegExp('(^|&)' + name + '=([^&]*)(&|$)', 'i');
      var r = window.location.search.substr(1).match(reg);
      if (r != null) {
          return unescape(r[2]);
      }
      return null;
  }

  // validate the verification code and display error tip when can not pass the validation
  validate_pic = function(code, showTip) {
    var flag, selector_verification;

    selector_verification = '.form-control-verification';
    flag = true;
    code = $.trim(code);
    if (typeof code === 'undefined' || !code) {
      if (showTip) {
        validation_prompt($(selector_verification), false, prompt_info);
      }
      flag = false;
    }
    return flag;
  };

  get_verification = function(successCallBack, failedCallBack) {
    var $iconVerificationCode;

    $iconVerificationCode = $('.icon-verification-code');

    return $.ajax({
      type: 'GET',
      url: '/api/captchas',
      dataType: 'json',
      success: function(result) {
        $iconVerificationCode.attr('src', result.data);
        verificationCode = result.codeId;
        if (successCallBack) {
          return successCallBack();
        }
      },
      error: function(xMLHttpRequest, errorType, error) {
        if (failedCallBack) {
          return failedCallBack();
        }
      }
    });
  };


  // To monitor all necessary fields
  $(current_form + ' input[demanded]').on('blur', function() {
    var current_field_val = $(this).val();

    if (current_field_val == '') {
      validation_prompt($(this), false, prompt_info);
    } else {
      if ($(this).hasClass('form-control-email')) {// Listening to email
        var email = current_field_val;
        var email_reg = /^([a-zA-Z0-9]+[_|\-|\.]?)*([a-zA-Z0-9])+@([a-zA-Z0-9]+[_|\-|\.]?)*([a-zA-Z0-9])+\.[a-zA-Z]{2,3}$/;

        if (!email_reg.test(email)) {
          prompt_info = '请输入正确的邮箱地址';
          validation_prompt($(this), false, prompt_info);
        } else {
          validation_prompt($(this), true);
        }
      } else if ($(this).hasClass('form-control-phone')) {// Listening to phone number
        var phone_num = current_field_val;
        var phone_reg = /^0?1[0-9]{10}$/;

        if (!phone_reg.test(phone_num)) {
          prompt_info = '请输入正确的手机号码';
          validation_prompt($(this), false, prompt_info);
        } else {
          validation_prompt($(this), true);
        }
      } else {
        validation_prompt($(this), true);
      }
    }
  });

  // Get message authentication code
  var wait_time = 60;
  var get_checkcode = function (btn) {
    if (wait_time == 0) {
      btn.removeAttr('disabled');
      btn.removeClass('obtaining');
      btn.text('获取验证码');
      wait_time = 60;
    } else {
      if (wait_time == 60) {
        var selector = current_form + ' .form-control-phone';
        $(selector).next('span').text('');
        btn.attr('disabled', true);
        btn.addClass('obtaining');
      }
      btn.text(wait_time + 's重新获取');
      wait_time--;
      setTimeout(function() {
          get_checkcode(btn);
      }, 1000);
    }
  }

  // Check the field error prompt
  var validation_prompt = function (element, correct, error_info) {
    if (!correct) {
      if (!$(element).next('span').is(prompt_form_control)) {
        var append_element = '<span class=' + prompt_form_control.substr(1) + '></span>';
        $(element).after(append_element);
      }
      $(element).parent().addClass("has-error");
      $(element).next('span').text(error_info).show();
      prompt_info = '请输入该字段';
    } else {
      $(element).parent().removeClass("has-error");
      $(element).next('span').hide();
    }
  }

  // Ajax request error prompt
  var request_error_handler = function (XMLHttpRequest) {
    var isVerificationError;

    isVerificationError = false;
    if (XMLHttpRequest.status = 440) {
      var response = $.parseJSON(XMLHttpRequest.responseText);
      var resp_message_str = response.message;
      var message_attr = resp_message_str.substring(2, resp_message_str.indexOf(':') - 1);
      var response_message = $.parseJSON(resp_message_str);
      var message = response_message[message_attr];

      // console.log(message_key +'---' + prompt_info);
      validation_prompt($('.form-control-' + message_attr), false, message);

      if (message_attr === 'verification') {
        isVerificationError = true;
      }

      wait_time = 0;
    }

    if (!isVerificationError) {
      get_verification();
    }
  }

  // Listen to get captcha button
  $('#getCaptcha').on('click', function() {
    var selector_verification, code, phone_text, verification_text, canSend;

    var selector_phone = '.form-control-phone';
    var phone_number = $(selector_phone).val();

    canSend = true;

    selector_verification = '.form-control-verification'
    code = $(selector_verification).val();

    if(phone_number === '') {
      validation_prompt($(selector_phone), false, prompt_info);
      canSend = false;
    }

    if(code === '') {
      validation_prompt($(selector_verification), false, prompt_info);
      canSend = false;
    }

    if(canSend) {
      phone_text = current_form + ' .form-control-phone';
      verification_text = current_form + ' .form-control-verification';
      if (!$(phone_text).parent().hasClass('has-error') && !$(verification_text).parent().hasClass('has-error')) {
        wait_time = 60;
        get_checkcode($(this));

        // Send ajax request
        $.ajax({
          type: "POST",
          url: "/api/mobile/send-captcha",
          data: JSON.stringify({
            "mobile": phone_number,
            "type": "signup",
            "codeId": verificationCode,
            "code": code
          }),
          dataType: "json",
          success: function(data) {
            // TODO: don't use
            if(data.message === 'Error') {
              // if return error, then restore send captcha btn
              wait_time = 0;
              get_verification();
            } else {
              captcha = data;
            }
          },
          error: function (XMLHttpRequest, textStatus, errorThrown) {
            // if return error, then restore send captcha btn
            request_error_handler(XMLHttpRequest);
          }
        });// ajax end
      }
    }
  });

  // remove error tip and restore input initial status when focus the input
  $(current_form + ' input[demanded]').on('focus', function(){
    var $this;

    $this = $(this);
    validation_prompt($this, true, '');
  })

  // change validation code picture
  $('#getVerificationCode').on('click', function(){
    get_verification();
  });

  // Listen to the form submit button
  $('#submit').on('click', function() {
    $(current_form + ' input[demanded]').trigger('blur');
    if (!$(current_form + ' div.has-error').length) {
      var request_name = $('.form-control-name').val();
      var request_email = $('.form-control-email').val();
      var request_company = $('.form-control-company').val();
      var request_phone = $('.form-control-phone').val();
      var request_captcha = $('.form-control-captcha').val();
      var request_position = $('.form-control-position').val();

      // Send ajax request
      $.ajax({
        type: "POST",
        url: "/api/site/register",
        data: JSON.stringify({
          "name" : request_name,
          "email" : request_email,
          "position": request_position,
          "company" : request_company,
          "phone" : request_phone,
          "captcha" : request_captcha
        }),
        dataType: "json",
        success: function(msg) {
          if (getQueryString('from') === 'wechat') {
            location.href = '/site/message?from=wechat';
          } else {
            location.href = '/site/message';
          }
        },
        error: function (XMLHttpRequest, textStatus, errorThrown) {
          request_error_handler(XMLHttpRequest);
        }
      });// ajax end
    }
  });

  function init() {
    if (getQueryString('email')) {
      $('.form-control-email').val(getQueryString('email'));
    }

    // Fix input and button height not equal bug in some devices
    if ($('#getCaptcha').outerHeight() !== $('.form-control-captcha').outerHeight()) {
      $('#getCaptcha').outerHeight($('.form-control-captcha').outerHeight());
    }

    get_verification();
  }

  init();

});

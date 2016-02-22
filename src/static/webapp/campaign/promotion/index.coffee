$(()->

  $historyBtn = $('#history-btn')
  $exchangeBtn = $('#exchange-btn')
  $code = $('#code')
  $tip = $('.form-tip')

  DEFAULT_TIP = '英文字母不区分大小写'
  EMPTY_TIP = '请填写此字段'
  EXCHANGE_NAME = '立即兑换'

  $loading = $ '<span class="m-loading m-spin"></span>'

  init = ->
    queryObj = $.extend {}, util.queryMap
    query = $.param queryObj
    $historyBtn.attr 'href', "/mobile/campaign/history?#{query}"

  renderFormTip = (msg)->
    $tip.text(msg).addClass('error-tip')

  resetFormTip = ->
    $tip.text(DEFAULT_TIP).removeClass('error-tip')

  $exchangeBtn.click (event) ->
    $exchangeBtn.addClass('mb-btn-disabled').html($loading)
    memberId = util.queryMap.memberId
    channelId = util.queryMap.channelId
    code = $code.val().trim()

    if code is ''
      renderFormTip EMPTY_TIP
      $exchangeBtn.removeClass('mb-btn-disabled').html(EXCHANGE_NAME)
    else if not channelId
      $exchangeBtn.removeClass('mb-btn-disabled').html(EXCHANGE_NAME)
      throw new Error 'Lack of channel id'
    else if not memberId
      $exchangeBtn.removeClass('mb-btn-disabled').html(EXCHANGE_NAME)
      throw new Error 'Lack of member id'
    else
      params =
        memberId: memberId
        channelId: channelId
        code: code

      rest.post '/product/promotion-code/exchange', params, (data)->
        if data.result is 'success'
          $historyBtn.trigger 'click'
        else if data.result is 'error'
          renderFormTip data.message
        $exchangeBtn.removeClass('mb-btn-disabled').html(EXCHANGE_NAME)

      , (xhr)->
        status = xhr.status
        resp = JSON.parse xhr.response
        switch status
          when 440
            for key, value of JSON.parse resp.message
              $("##{key}").text(value).addClass('error-tip')
          else
            renderFormTip resp.message

        $exchangeBtn.removeClass('mb-btn-disabled').html(EXCHANGE_NAME)

  $code.keyup ()->
    resetFormTip()

  init()
  return
)

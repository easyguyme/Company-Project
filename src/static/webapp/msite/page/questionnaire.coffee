$ ->
  frameDOM = window.frameElement
  isPC = !!frameDOM

  resizeHeight = ->
    height = $('.success-area').height() - $('.success-center-area').height()
    if height > 0
      $('.success-center-area').css('top', height * 0.4)
      $('.another-area').css('margin-top', height * 0.15)

  questionType =
    'radio': '单选',
    'checkbox': '多选'
  nowTime = new Date()

  initHTML = '<div class="m-questionnaire-blank"></div>
    <label class="init-tip">问卷示例</label>
    <li class="m-questionnaire-item">
        <div class="m-questionnaire-question">
            1.问题名称
        </div>
        <div class="m-questionnaire-answer clearfix">
            <input type="radio" class="real-radio" checked="checked"/>
            <i class="m-questionnaire-radio m-radio"></i>
            <span class="m-questionnaire-answer-content">选项1</span>
        </div>
        <div class="m-questionnaire-answer clearfix">
            <i class="m-questionnaire-radio m-radio"></i>
            <span class="m-questionnaire-answer-content">选项2</span>
        </div>
        <div class="m-questionnaire-answer clearfix">
            <i class="m-questionnaire-radio m-radio"></i>
            <span class="m-questionnaire-answer-content">选项3</span>
        </div>
        <div class="m-questionnaire-answer clearfix">
            <i class="m-questionnaire-radio m-radio"></i>
            <span class="m-questionnaire-answer-content">选项4</span>
        </div>
    </li>
    <div class="m-questionnaire-btn m-border-color m-bgcolor">
        提交
    </div>
    <div class="m-questionnaire-blank"></div>'

  searchMsg = window.location.search if window.location.search
  searchArray = searchMsg.slice(1).split '&'
  openId = ''
  channelId = ''
  origin = ''
  searchArray.forEach (item) ->
    if item.indexOf('openId') isnt -1
      openId = item.split('=')[1]
    if item.indexOf('channelId') isnt -1
      channelId = item.split('=')[1]
    if item.indexOf('origin') isnt -1
      origin = item.split('=')[1]

  message =
    title: ''
    imgUrl: '/images/microsite/share_picture.png'
    link: ''

  $questionnaire = $('.m-questionnaire')
  questions = []
  effectiveNum = 0
  apiParam = ''
  if openId and channelId
    apiParam = '&openId=' + openId + '&channelId=' + channelId
  $questionnaire.each (index) ->
    $wrapper = $(this)
    id = $wrapper.data('questionnaire')
    style = $wrapper.data('style')
    if not id or id is '0'
      $wrapper.html initHTML
    else
      questions.push id
      $.ajax
        type: 'GET'
        url: '/api/questionnaire/' + id + '?time=' + new Date().getTime() + apiParam
        success: (data) ->
          if data
            for item, flag in questions
              questions[flag] = data if item is data.id

            message.title = data.name
            questionsHTML = '<label class="description-font clearfix">' + data.description + '</label><ul class="m-questionnaire-items">'

            showStartTime = data.startTime
            data.startTime = data.startTime.replace(/-/g, '/')
            data.endTime = data.endTime.replace(/-/g, '/')

            effectiveNum++
            if not isPC
              if not data.isPublished
                location.href = '/mobile/common/404'
              else if data.isAnswered
                questionsHTML += '<label class="tip-font">您于' + data.answerTime + '已参与过调查</label>'
                effectiveNum--
              else if nowTime > new Date(data.endTime)
                questionsHTML += '<label class="tip-font">问卷已过期</label>'
                effectiveNum--
              else if nowTime < new Date(data.startTime)
                questionsHTML += '<label class="tip-font">问卷未开始</br>' + showStartTime + '开始</label>'
                effectiveNum--


            for question, idx in data.questions
              questionsHTML += '<li class="m-questionnaire-item"><div class="m-questionnaire-question" id="q' + idx + '">' + (idx + 1) + '.' + question.title

              if question.type in ['radio', 'checkbox']
                questionsHTML += '(' + questionType[question.type] + ')</div>'
                for option in question.options
                  questionsHTML += '<div class="m-questionnaire-answer clearfix">
                      <i class="real-click"></i>
                      <input type="' + question.type + '" class="real-' + question.type + '" name="' + index + '' + idx + '" value="' + option.content + '"/>
                      <i class="m-questionnaire-radio m-' + question.type + '" value="' + option.content + '"></i>'
                  questionsHTML += '<i class="m-questionnaire-radio" style="background-image: url(\'/images/content/icon_' + option.icon + '.png\');width: .48rem;height: .56rem;"></i>' if option.icon
                  questionsHTML += '<span class="m-questionnaire-answer-content">' + option.content + '</span>
                  </div>'

              else
                questionsHTML += '</div><textarea class="m-questionnaire-textarea" name="' + index + '' + idx + '" placeholder="请输入您的答案"></textarea>'

              questionsHTML += '</li>'

            if not isPC
              if nowTime > new Date(data.endTime) or nowTime < new Date(data.startTime) or data.isAnswered
                questionsHTML += '<div class="m-questionnaire-btn disabled-btn">提交</div></ul><div class="m-questionnaire-blank"></div>'
                $wrapper.html questionsHTML
                $wrapper.find('.real-radio').remove()
                $wrapper.find('.real-checkbox').remove()
                $wrapper.find('.m-questionnaire-textarea').attr('disabled', 'disabled')
              else
                if style is 1
                  questionsHTML += '<div class="m-questionnaire-btn m-border-color m-bgcolor">提交</div></ul><div class="m-questionnaire-blank"></div>'
                else
                  questionsHTML += '<div class="m-questionnaire-btn m-border-color m-color">提交</div></ul><div class="m-questionnaire-blank"></div>'
                $wrapper.html questionsHTML
                $wrapper.find('.m-questionnaire-btn').on('click', ->
                  submit($wrapper, index)
                )
                $wrapper.find('.real-click').on('click', ->
                  $(this).next().click()
                )

            else
              if style is 1
                questionsHTML += '<div class="m-questionnaire-btn m-border-color m-bgcolor">提交</div></ul><div class="m-questionnaire-blank"></div>'
              else
                questionsHTML += '<div class="m-questionnaire-btn m-border-color m-color">提交</div></ul><div class="m-questionnaire-blank"></div>'
              $wrapper.html questionsHTML

            frameDOM = window.frameElement
            if frameDOM and $('#cpt-wrap').data('type') is 'questionnaire'
              cptHeight = $('#cpt-wrap').height()
              frameDOM.style.height = cptHeight + 'px'
              if not frameDOM.parentNode.parentNode.classList.contains('mobile-content')
                frameDOM.parentNode.parentNode.style.height = ($('#cpt-wrap').height() + 2) + 'px'
                frameDOM.style.height = $('#cpt-wrap').height() + 'px' if $('#cpt-wrap').height() isnt cptHeight
              else
                frameDOM.parentNode.style.height = ($('#cpt-wrap').height() + 2) + 'px'
                frameDOM.style.height = $('#cpt-wrap').height() + 'px' if $('#cpt-wrap').height() isnt cptHeight

          else
            $wrapper.html '<label class="">delete</label>'

  submit = ($wrapper, index) ->
    answers = []
    if $wrapper.find('.error-tip').length is 0 and $wrapper.find('.error-response-tip').length is 0
      for question, idx in questions[index].questions
        answer = ''
        if question.type in ['radio', 'checkbox']
          answer = $wrapper.find('input[name="' + index + '' + idx + '"]:checked').val()
        else
          answer = $wrapper.find('textarea[name="' + index + '' + idx + '"]').val()

        if answer
          if question.type is 'checkbox'
            answer = []
            $wrapper.find('input[name="' + index + '' + idx + '"]:checked').each ->
              answer.push $(this).val()

          answers.push {
            questionId: question.id,
            type: question.type,
            value: answer
          }

        else
          $wrapper.find('#q' + idx).after('<label class="error-tip">请回答此问题</label>')

      if answers.length isnt questions[index].questions.length
        $errors = $wrapper.find('.error-tip')
        if $errors.length > 0
          top = getPositionTop $errors[0].parentNode
          setTimeout ->
            $('body').scrollTop top
          , 100
        setTimeout ->
          $errors.remove()
        , 3000

      else
        successHTML = '<div class="m-questionnaire-success">
              <div class="success-area">
                  <div class="success-center-area">
                      <div class="success-icon"></div>
                      <div class="success-font">调查完成，感谢参与！</div>
                      <div class="success-font another-area">感谢您参加【' + questions[index].name + '】</div>
                      <div class="success-font">我们再次向您表示诚挚的谢意。</div>
                      <div class="success-font">请领取您的奖品，祝您生活愉快！</div>
                  </div>
              </div>
          </div>'

        param =
          questionnaireId: questions[index].id,
          answers: answers

        if channelId and openId
          param.user =
            channelId: channelId,
            openId: openId,
            origin: origin

        $.ajax
          type: 'POST'
          url: '/api/questionnaire/answer'
          data: JSON.stringify param
          dataType: 'json'
          success: (data) ->
            if effectiveNum > 1
              effectiveNum--
              $wrapper.remove()
            else
              $('body').html successHTML

              resizeHeight()
              window.onorientationchange =
              window.onresize = ->
                resizeHeight()
                return
          error: (data) ->
            errorCode = JSON.parse data.response
            $wrapper.find('.m-questionnaire-btn').before('<label class="error-response-tip">' + errorCode.message + '</label>')
            setTimeout ->
              $wrapper.find('.error-response-tip').remove()
            , 3000

  getPositionTop = (elem) ->
    offset = elem.offsetTop
    if elem?.offsetParent
      offset += getPositionTop elem.offsetParent
    offset

  if window.wx
    wx.config({
      appId: options.appId
      timestamp: options.timestamp
      nonceStr: options.nonceStr
      signature: options.signature
      jsApiList: [
        'onMenuShareAppMessage',
        'onMenuShareTimeline',
        'onMenuShareQQ',
        'onMenuShareWeibo'
      ]
    })

    wx.ready( ->
      wx.onMenuShareAppMessage message
      wx.onMenuShareQQ message
      wx.onMenuShareWeibo message
      wx.onMenuShareTimeline message
      return
    )
  return

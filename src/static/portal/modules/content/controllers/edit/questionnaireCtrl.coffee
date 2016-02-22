define [
  'wm/app'
  'wm/config'
], (app, config) ->
  app.registerController 'wm.ctrl.content.edit.questionnaire', [
    'restService'
    'notificationService'
    '$location'
    '$stateParams'
    '$scope'
    '$timeout'
    'validateService'
    (restService, notificationService, $location, $stateParams, $scope, $timeout, validateService) ->
      vm = this

      _init = ->
        vm.id = $stateParams.id
        vm.isShow = false
        vm.isNull = true
        vm.isCreating = not $stateParams.id
        vm.questionnaireTitle = if vm.id then 'content_edit_questionnaire' else 'content_questionnaire_add_item'
        vm.breadcrumb = [
          text: 'content_questionnaire_list'
          href: '/content/questionnaire'
          ,
          vm.questionnaireTitle
        ]

        vm.questionTypes = [

            name: 'content_questionnaire_choice'
            value: 'choice'
            status: 'on'
          ,
            name: 'content_questionnaire_question_and_answer'
            value: 'answer'
            status: 'off'
        ]

        vm.columns = [

            icon: ''
            value: ''
          ,
            icon: '/images/content/icon_support.png'
            value: 'support'
          ,
            icon: '/images/content/icon_opposition.png'
            value: 'opposition'
        ]

        vm.questionnaires =
          name: ''
          startTime: null
          endTime: null
          description: ''
          questions: [
            id: ''
            type: vm.questionTypes[0].value
            title: ''
            options: [
              icon: vm.columns[0].name
              content: ''
            ]
            isOpenCheck: false
          ]

        vm.questionStatus = [
            name: 'content_questionnaire_temporarily_not'
            value: 'notNow'
            status: 'on'
          ,
            name: 'content_questionnaire_right_now'
            value: 'now'
            status: 'off'
        ]

        vm.questionState = vm.questionStatus[0].value

        _getQuestionnaire() if $stateParams.id

        if not $stateParams.id
          vm.showQuestions()

      vm.getUeditor = (ueditor) ->
        vm.ueditor = ueditor

        ueditor.addListener 'focus', ->
          validateService.restore($(".ueditor > div"))
          $('.question-description').removeClass 'question-description-tip'
          return

      vm.checkName = ->
        formTip = ''
        if vm.questionnaires.name and vm.questionnaires.name.length < 4
          formTip = 'content_name_tip'
        formTip

      vm.checkQuestion = (value) ->
        formTip = ''
        if value and value.length < 4
          formTip = 'content_question_tip'
        formTip

      vm.checkQuestionAsk = (value) ->
        formTip = ''
        if value and value.length < 4
          formTip = 'content_question_tip_ask'
        formTip

      vm.showQuestions = ->
        vm.isShow = not vm.isShow

      vm.clearQuestion = (index) ->
        vm.questionnaires.questions[index].title = ''
        vm.questionnaires.questions[index].options = [
          icon: vm.columns[0].name
          content: ''
        ]

      vm.addQuestionOption = (index) ->
        vm.questionnaires.questions[index].options.push {icon: vm.columns[0].name, content: ''}

      vm.addQuestion = ->
        vm.questionnaires.questions.push {
          id: ''
          type: vm.questionTypes[0].value
          title: ''
          options: [
            {
              icon: vm.columns[0].name
              content: ''
            }
          ]
          isOpenCheck: false
        }

      vm.removeQuestion = (index, $event) ->
        notificationService.confirm $event,{
          title: 'content_question_delete_confirm'
          submitCallback: _removeQuestionHandler
          params: [index]
        }

      _removeQuestionHandler = (index) ->
        $scope.$apply( ->
          vm.questionnaires.questions.splice index, 1
        )

      vm.removeQuestionOption = (questionIndex, index, $event) ->
        notificationService.confirm $event,{
          title: 'content_option_delete_confirm'
          submitCallback: _removeOptionHandler
          params: [questionIndex, index]
        }

      _removeOptionHandler = (questionIndex, index) ->
        $scope.$apply( ->
          vm.questionnaires.questions[questionIndex].options.splice index, 1
        )

      vm.removeErrorTip = ->
        validateService.restore($(".ueditor > div"))
        $('.question-description').removeClass 'question-description-tip'
        return

      _getQuestionnaire = ->
        restService.get config.resources.questionnaire + '/' + $stateParams.id, (data) ->
          if data
            if data.questions.length isnt 0
              vm.showQuestions()
            vm.questionnaires.startTime = moment(data.startTime).valueOf()
            vm.questionnaires.endTime = moment(data.endTime).valueOf()
            vm.isDisabledStartPicker = vm.questionnaires.startTime < moment().valueOf()
            vm.isDisabledEndPicker = vm.questionnaires.endTime < moment().valueOf()

            if not vm.isDisabledStartPicker
              vm.startPickerConfig =
                minDate: moment()

            if not vm.isDisabledEndPicker
              vm.endPickerConfig =
                minDate: moment()

            vm.questionnaires.name = data.name if data.name?
            vm.questionnaires.description = data.description if data.description?

            if data.isPublished is true
              vm.questionState = 'now'
            else
              vm.questionState = 'notNow'

            questions = []
            angular.forEach data.questions, (questionItem, index) ->

              question = {}
              question.title = questionItem.title if questionItem.title?
              question.id = questionItem.id if questionItem.id?

              if questionItem.type is 'radio' or questionItem.type is 'checkbox'
                question.type = 'choice'

                if questionItem.type is 'radio'
                  question.isOpenCheck = false
                else
                  question.isOpenCheck = true

                question.options = angular.copy questionItem.options

              else
                question.type = 'answer'

              questions.push question
            vm.questionnaires.questions = angular.copy questions

      _checkQuestionOption = ->
        indexFlag = -1
        for question, index in vm.questionnaires.questions
          if question.type is 'choice' and question.options.length < 2
            $($('.question-item-repeat')[index]).addClass 'question-item-error'
            indexFlag = index if indexFlag < 0

        if indexFlag >= 0
          height = $($('.question-item-repeat')[indexFlag]).offset().top - 60
          $('body').animate {'scrollTop': height}, 250
        return indexFlag

      vm.removeOptionError = (index) ->
        $($('.question-item-repeat')[index]).removeClass 'question-item-error' if $($('.question-item-repeat')[index]).hasClass 'question-item-error'
        return

      vm.submit = ->
        vm.isNull = true
        cannotSubmit = false
        cannotSubmit = true if _checkQuestionOption() >= 0
        cannotSubmit = true if vm.checkName() isnt ''

        for question in vm.questionnaires.questions
          cannotSubmit = true if vm.checkQuestion(question.title) isnt ''

        if not vm.questionnaires.startTime or not vm.questionnaires.endTime or vm.questionnaires.startTime > vm.questionnaires.endTime
          cannotSubmit = true

        if vm.ueditor.getContentTxt().length > 120
          cannotSubmit = true
          validateService.highlight($(".ueditor > div"))
          $('.question-description').addClass 'question-description-tip'

        if cannotSubmit
          return

        options = []
        question = []

        questionnaire =
          name: vm.questionnaires.name
          startTime: vm.questionnaires.startTime
          endTime: vm.questionnaires.endTime
          description: vm.questionnaires.description
          question: question
          isPublished: false

        if vm.questionState is 'now'
          questionnaire.isPublished = true
        else
          questionnaire.isPublished = false

        angular.forEach vm.questionnaires.questions, (questionItem, index) ->

          switch questionItem.type

            when 'choice'
              ques = []
              options = []
              questionChoice =
                id: ''
                title: ''
                type: ''
                order: 0
                options: options

              if questionItem.isOpenCheck is false
                questionChoice.type = 'radio'
              else questionChoice.type = 'checkbox'

              questionChoice.title = questionItem.title
              questionChoice.order = index
              questionChoice.id = questionItem.id

              if questionItem.title isnt ''
                vm.isNull = false
              else validateService.highlight($(".question-choice#{index}"))

              angular.forEach questionItem.options, (optionItem) ->
                option =
                  icon: ''
                  content: ''

                option.icon = optionItem.icon
                option.content = optionItem.content

                if option.icon isnt '' or option.content isnt ''
                  vm.isNull = false

                questionChoice.options.push option
              ques = questionChoice

            when 'answer'
              ques = []
              options = []
              questionAnswer =
                id: ''
                title: ''
                type: ''
                order: 0

              questionAnswer.title = questionItem.title
              questionAnswer.type = 'input'
              questionAnswer.order = index
              questionAnswer.id = questionItem.id

              if questionItem.title isnt ''
                vm.isNull = false
              else validateService.highlight($(".question-answer#{index}"))

              ques = questionAnswer

          questionnaire.question.push ques

        if vm.isNull is true
          questionnaire.question = []
          angular.forEach vm.questionnaires.questions, (question, index) ->
            validateService.restore($('.question-answer-choice'))

        url = config.resources.questionnaires
        method = 'post'

        if $stateParams.id
          method = 'put'
          url = config.resources.questionnaire + '/' + vm.id

        restService[method] url, questionnaire, (data) ->
          if method is 'post'
            notificationService.success 'content_questionnaire_create_success', false
            $timeout(->
              window.location.href = '/content/questionnaire'
            ,500)
          else
            notificationService.success 'content_questionnaire_update_success', false
            $timeout(->
              window.location.href = '/content/questionnaire'
            ,500)

      vm.cancel = ->
        window.location.href = '/content/questionnaire'

      _init()

      vm
  ]

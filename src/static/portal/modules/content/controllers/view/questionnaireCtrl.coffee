define [
  'wm/app'
  'wm/config'
], (app, config) ->
  app.registerController 'wm.ctrl.content.view.questionnaire', [
    'restService'
    '$scope'
    '$stateParams'
    '$sce'
    '$filter'
    '$modal'
    (restService, $scope, $stateParams, $sce, $filter, $modal) ->
      vm = this

      _init = ->
        vm.breadcrumb = [
          text: 'content_questionnaire_list'
          href: '/content/questionnaire'
          icon: 'questionnaire'
        ,
          'content_questionnaire_detail'
        ]

        vm.questionnaireId = $stateParams.id
        restService.get config.resources.questionnaire + '/' + vm.questionnaireId, (data) ->
          if data
            vm.data = angular.copy data
            if vm.data.questions
              for question in vm.data.questions
                if question.type is 'input'
                  question.title += ' (' + $filter('translate')('content_questionnaire_question_and_answer') + ')'

                total = 0
                if question.type is 'checkbox' or question.type is 'radio'
                  for option in question.options
                    total += option.count

                for option in question.options
                  option.total = total
                  option.height = '15px'
                  option.showTip = true
            vm.data.description = $sce.trustAsHtml data.description

      vm.showAnswer = (questionId, title) ->
        modalInstance = $modal.open(
          templateUrl: 'answer.html'
          controller: 'wm.ctrl.content.answer'
          windowClass: 'answer-dialog'
          resolve:
            modalData: ->
              questionnaireId: vm.questionnaireId
              questionId: questionId
              title: title.substr 0, title.length - 6
        )

      _init()
      vm
  ]
  .registerController 'wm.ctrl.content.answer', [
    'restService'
    '$scope'
    '$modalInstance'
    'modalData'
    '$filter'
    '$timeout'
    'debounceService'
    (restService, $scope, $modalInstance, modalData, $filter, $timeout, debounceService) ->
      vm = $scope

      _init = ->
        vm.questionnaireId = modalData.questionnaireId
        vm.questionId = modalData.questionId
        vm.title = modalData.title
        vm.currentPage = 1
        vm.pageSize = 15
        vm.answers = []
        _getAnswers()

      _getAnswers = ->
        param =
          'page': vm.currentPage,
          'per-page': vm.pageSize
          'questionnaireId': vm.questionnaireId
          'questionId': vm.questionId
        restService.get config.resources.questionAnswers, param, (data) ->
          if data
            vm.totalPages = data._meta.pageCount if data._meta
            for item in data.items
              item.name = $filter('translate')('content_questionnaire_anonymous_user') if not item.name
              vm.answers.push item

      vm.hideModal = ->
        $modalInstance.dismiss('cancel')
        return

      $timeout( ->
        $('.modal-body').scroll debounceService.callback( ->
          if $(".modal-body")[0].scrollHeight - $(".modal-body")[0].clientHeight - $(".modal-body")[0].scrollTop < 20
            if vm.currentPage < vm.totalPages
              vm.currentPage += 1
              _getAnswers()
        )
      , 1000)

      _init()
      return
  ]

define [
  'wm/app'
  'wm/config'
  'core/directives/wmCharts'
], (app, config) ->
  app.registerController 'wm.ctrl.content.statistics.questionnaire', [
    'restService'
    '$scope'
    'notificationService'
    '$location'
    '$stateParams'
    (restService, $scope, notificationService, $location, $stateParams) ->
      vm = this

      vm.breadcrumb = [
        text: 'content_questionnaire_list'
        href: '/content/questionnaire'
        icon: 'questionnaire'
      ,
        'statistics'
      ]

      vm.questionnaireId = $stateParams.id
      vm.questionnaireLineChartOptions =
        color: ['#57C6CD']
        categories: []
        series: [
          name: 'content_participants'
          data: []
        ]
        startDate: ''
        endDate: ''
        config:
          legend:
            show: false

      vm.questionnaireBarChartOptions =
        color: ['#88C6FF']
        categories: []
        series: [
          name: 'content_votes'
          data: []
        ]
        config:
          legend:
            show: false

      vm.changeTarget = (val, idx) ->
        vm.target = val
        _getQuestionsStatistics()

      vm.selectDate = ->
        _getQuestionsStatistics()

      vm.selectQuestionnaireDate = ->
        _getQuestionnaireStatistics()

      _getQuestionnaireStatistics = ->
        params =
          startTime: vm.questionnaireStartDate
          endTime: vm.questionnaireEndDate
        restService.get config.resources.questionnaireStatistic + '/' + vm.questionnaireId, params, (data) ->
          if data
            vm.questionnaireLineChartOptions.categories = angular.copy data.date
            vm.questionnaireLineChartOptions.series[0].data = angular.copy data.count
            vm.questionnaireLineChartOptions.startDate = moment(vm.questionnaireStartDate).format('YYYY-MM-DD')
            vm.questionnaireLineChartOptions.endDate = moment(vm.questionnaireEndDate).format('YYYY-MM-DD')

      _getQuestions = ->
        params =
          questionnaireId: vm.questionnaireId
        restService.get config.resources.questions, params, (data) ->
          vm.targetOptions = []
          if data.length > 0
            vm.targetOptions = angular.copy data
            vm.target = vm.targetOptions[0].id
            _getQuestionsStatistics()

      _getQuestionsStatistics = ->
        if vm.target
          params =
            questionId: vm.target
            startTime: vm.startDate
            endTime: vm.endDate
          restService.get config.resources.questionStatistic, params, (data) ->
            if data
              vm.questionnaireBarChartOptions.categories = angular.copy data.options
              vm.questionnaireBarChartOptions.series[0].data = angular.copy data.count

      _init = ->
        start = moment().subtract(7, 'days').startOf('day').valueOf()
        end = moment().subtract(1, 'days').startOf('day').valueOf()
        vm.startDate = start
        vm.endDate = end
        vm.questionnaireStartDate = start
        vm.questionnaireEndDate = end
        _getQuestionnaireStatistics()
        _getQuestions()

      _init()

      vm
  ]

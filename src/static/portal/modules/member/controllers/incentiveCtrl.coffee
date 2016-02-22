define [
  'wm/app'
  'wm/config'
], (app, config) ->
  app.registerController 'wm.ctrl.member.incentive', [
    'restService'
    '$location'
    'notificationService'
    (restService, $location, notificationService) ->
      vm = this

      vm.breadcrumb = [
        'member_incentive'
      ]

      # Point rules list
      vm.ruleList = {
        columnDefs: [
          {
            field: 'name'
            label: 'member_excitation_condition'
            type: 'translate'
          }
          {
            field: 'rewardType'
            label: 'member_excitation_type'
            type: 'translate'
          }
          {
            field: 'count'
            label: 'customer_rule_count'
          }
          {
            field: 'status'
            label: 'channel_wechat_status'
            type: 'status'
          }
          {
            field: 'operations'
            label: 'operations'
            type: 'operation'
          }
        ]
        data: []
        selectable: false

        editHandler: (idx) ->
          $location.path '/member/edit/incentive/' + vm.ruleList.data[idx].id

        deleteHandler: (idx) ->
          restService.del config.resources.scoreRule + '/' + vm.ruleList.data[idx].id, (data) ->
            _getRuleList()

        switchHandler: (idx) ->
          item = vm.ruleList.data[idx]
          data =
            isEnabled: item.status is 'DISABLE'
          restService.put config.resources.scoreRule + '/' + item.id, data, (data) ->
            notificationService.success 'customer_score_rule_status_update_success', false
          return
      }

      _getRuleList = ->
        vm.ruleList.hasLoading = true
        param =
          orderBy: {createAt: 'asc'}
        restService.get config.resources.scoreRules, param, (data) ->
          items = angular.copy data.items
          _transferToTable(items)
          vm.ruleList.hasLoading = false
        return

      _transferToTable = (data) ->
        defaultLine = '-'
        items = []
        for item in data
          listItem = {
            name: {}
            rule: {
              values: {}
            }
          }

          listItem.name = if item.isDefault then 'member_' + item.name else item.name
          listItem.rewardType = if not item.rewardType? or item.rewardType is 'score' then 'customer_members_score' else item.rewardType

          if item.score
            if item.name isnt 'birthday'
              listItem.rule =
                key: 'customer_score_full_rule'
                values:
                  score: item.score
            else
              switch item.triggerTime
                when 'day' then listItem.rule.key = 'customer_score_birthday_rule'
                when 'week' then listItem.rule.key = 'customer_score_birthday_week_rule'
                when 'month' then listItem.rule.key = 'customer_score_birthday_month_rule'
              listItem.rule.values.score = item.score
          else
            listItem.rule =
              key: 'customer_score_default'
              values: {}
          listItem.operations = [
            name: 'edit'
          ]
          listItem.operations.push({name: 'delete'}) if not item.isDefault
          listItem.id = item.id
          listItem.count = if item.memberCount then item.memberCount else defaultLine
          listItem.status = if item.isEnabled then 'ENABLE' else 'DISABLE'
          items.push listItem

        vm.ruleList.data = items

        return

      _init = ->
        _getRuleList()

      _init()

      vm
  ]

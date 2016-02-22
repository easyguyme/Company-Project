define [
  'wm/app'
  'wm/config'
], (app, config) ->
  app.registerController 'wm.ctrl.member.card', [
    'restService'
    '$filter'
    '$location'
    'notificationService'
    'utilService'
    (restService, $filter, $location, notificationService, utilService) ->
      vm = this

      vm.tableCards = []

      vm.breadcrumb = [
        'member_card'
      ]

      # Card list
      vm.cardList =
      {
        columnDefs: [
          {
            field: 'name'
            label: 'customer_card_name'
            type: 'link'
          }, {
            field: 'number'
            label: 'customer_card_extend_number'
          }, {
            field: 'createdAt'
            label: 'customer_card_create_time'
            sortable: true
            desc: true
            type: 'date'
          }, {
            field: 'operations'
            label: 'operations'
            type: 'operation'
          }
        ],
        data: vm.tableCards
        deleteTitle: 'customer_member_delete_confirm'

        sortHandler: (colDef) ->
          key = colDef.field
          value = if colDef.desc then 'desc' else 'asc'
          vm.orderBy = '{"' + key + '":' + '"' + value + '"}'
          _getCardList()

        editHandler: (idx) ->
          $location.url '/member/edit/card/' + vm.cardList.data[idx].id

        deleteHandler: (idx) ->
          if vm.cardList.data[idx].isDefault
            notificationService.warning 'customer_card_default_card_delete_fail'
          else
            restService.del config.resources.card + '/' + vm.cardList.data[idx].id, (data) ->
              vm.cardList.data.splice idx, 1
              _init()
              notificationService.success 'customer_card_delete_success'

        defaultcardHandler: (idx) ->
          if vm.cardList.data[idx].isDefault
            notificationService.info 'customer_card_repeat_setting_default_card'
          else
            params =
              id: vm.cards[idx].id
            restService.put config.resources.defaultCard, params, (data) ->
              _getCardList()
              notificationService.success 'customer_card_set_as_default_success'
      }

      vm.newCard = ->
        $location.url '/member/edit/card'

      _getCardList = ->
        vm.cardList.hasLoading = true
        param =
          unlimited: true
        if vm.orderBy
          param.orderBy = vm.orderBy
        restService.get config.resources.cards, param, (data) ->
          vm.cards = data.items
          _translateToTableParam(data.items)
          vm.cardList.hasLoading = false

      _translateToTableParam = (data) ->
        vm.tableCards.length = 0
        operations = []
        if data.length > 0
          for item, i in data
            vm.tableCards[i] = {}
            vm.tableCards[i].trueName = item.name
            vm.tableCards[i].name =
              text: item.name
              link: '/member/view/card/' + item.id
              name: item.name
            vm.tableCards[i].id = item.id
            vm.tableCards[i].isDefault = item.isDefault
            if item.isDefault is true
              vm.tableCards[i].name.tooltip = item.name
              vm.tableCards[i].name.tag = 'customer_card_default_card'
              vm.tableCards[i].name.text = utilService.formateString 5, item.name
            vm.tableCards[i].number = item.provideCount
            vm.tableCards[i].createdAt = item.createdAt

            operations = [
              {
                name: 'edit'
              }
              {
                name: 'defaultcard'
                title: 'customer_card_set_as_default'
              }
              {
                name: 'delete'
              }
            ]

            vm.tableCards[i].operations = operations

      _init = ->
        _getCardList()

      _init()

      vm
  ]

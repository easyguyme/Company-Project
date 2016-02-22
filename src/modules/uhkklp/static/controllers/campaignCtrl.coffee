define [
  'wm/app'
  'wm/config'
  'core/directives/wmCharts'
], (app, config) ->
  app.registerController 'wm.ctrl.uhkklp.campaign', [
    'restService'
    '$scope'
    'exportService'
    (restService, $scope, exportService) ->
      vm = this

      vm.breadcrumb = [
        icon: 'statcampaign'
        text: 'analytic_campaign'
      ]

      _init = ->
        vm.yearSKUPerOperator = moment().valueOf()

        vm.endMonthPullAndFT = moment().valueOf()
        vm.beginMonthPullAndFT = moment().subtract(5, 'months').valueOf()

        defaultQuarter =
          year: parseInt moment().format('YYYY')
          quarter: Math.ceil(parseInt(moment().format('MM')) / 3)

        vm.quarterKLPInVolume = angular.copy defaultQuarter
        vm.quarterKLPInAcct = angular.copy defaultQuarter
        vm.quarterPromotionSKUSummary = angular.copy defaultQuarter
        vm.quarterSKUSummary = angular.copy defaultQuarter

        vm.PullAndFTOptions =
          color: ['#7E56B5', '#9374BE']
          categories: []
          stack: true
          type: 'percent'
          series: []

        vm.SKUPerOperatorOptions =
          categories: []
          series: []

        vm.KLPInVolumeOptions =
          color: ['#AFDB51', '#37C3AA', '#88C6FF', '#8660BB', '#F29C9F', '#FFBD5A', '#FACD89', '#F8E916']
          title: "KLP Channel Penetration in Volume"
          series: []

        vm.KLPInAcctOptions =
          color: ['#AFDB51', '#37C3AA', '#88C6FF', '#8660BB', '#F29C9F', '#FFBD5A', '#FACD89', '#F8E916']
          title: "KLP Channel Penetration in Acct"
          series: []

        vm.promotionSKUSummaryOptions =
          color: ['#E6CF11']
          categories: []
          series: []

        vm.SKUSummaryOptions =
          color: ['#F0867E', '#D15046']
          categories: []
          series: []


        _getKLPInVolumeStats()
        _getKLPInAcctStats()
        _getPromotionSKUSummaryStats()
        _getSKUSummaryStats()

        return

      _getPullAndFTStats = ->
        params =
          start: vm.beginMonthPullAndFT
          end: vm.endMonthPullAndFT

        restService.get config.resources.statPullAndFT, params, (data) ->
          vm['part'] = (data.data.length is 0)

          if data and data.month
            if angular.isArray(data.month) and data.month.length > 0
              vm.PullAndFTOptions.categories = angular.copy data.month

              series = []
              for key, value of data.data
                item =
                  name: key
                  data: value
                series.push item
              vm.PullAndFTOptions.series = angular.copy series

      _getSKUPerOperatorStats = ->
        params =
          year: moment(vm.yearSKUPerOperator).format('YYYY')

        restService.get config.resources.statSKUPerOperator, params, (data) ->
          vm['operator'] = (data.data.length is 0)

          if data
            vm.SKUPerOperatorOptions.categories = angular.copy data.date
            series = []

            for key, value of data.data
              item =
                name: key
                data: value
              series.push item
            vm.SKUPerOperatorOptions.series = angular.copy series

      _getKLPInVolumeStats = ->
        params =
          year: vm.quarterKLPInVolume.year
          quarter: vm.quarterKLPInVolume.quarter

        restService.get config.resources.statKLPInVolume, params, (data) ->
          vm['volum'] = (data.data.length is 0)

          if data
            series = []

            for key, value of data.data
              item =
                name: key
                value: value
              series.push item
            vm.KLPInVolumeOptions.series = angular.copy series


      _getKLPInAcctStats = ->
        params =
          year: vm.quarterKLPInAcct.year
          quarter: vm.quarterKLPInAcct.quarter

        restService.get config.resources.statKLPInAcct, params, (data) ->
          vm['acct'] = (data.data.length is 0)

          if data
            series = []

            for key, value of data.data
              item =
                name: key
                value: value
              series.push item
            vm.KLPInAcctOptions.series = angular.copy series

      _getPromotionSKUSummaryStats = ->
        params =
          year: vm.quarterPromotionSKUSummary.year
          quarter: vm.quarterPromotionSKUSummary.quarter

        restService.get config.resources.statPromotionSKUSummary, params, (data) ->
          vm['promotion'] = (data.data.length is 0)

          if data and data.products
            series = []
            if angular.isArray(data.products) and data.products.length > 0
              vm.promotionSKUSummaryOptions.categories = angular.copy data.products
              series = [{
                name: 'Redeemed'
                data: data.data
              }]

            vm.promotionSKUSummaryOptions.series = angular.copy series

      _getSKUSummaryStats = ->
        params =
          year: vm.quarterSKUSummary.year
          quarter: vm.quarterSKUSummary.quarter

        restService.get config.resources.statSKUSummary, params, (data) ->
          vm['campaignSku'] = (data.data.length is 0)

          if data and data.productName
            series = []
            if angular.isArray(data.productName) and data.productName.length > 0
              vm.SKUSummaryOptions.categories = angular.copy data.productName

              for key, value of data.data
                value = angular.copy value.map (item) ->
                  result = item
                  if result
                    result = new Number(result)
                    result = result.toFixed(1)
                  result

                item =
                  name: key
                  data: value
                series.push item

            vm.SKUSummaryOptions.series = angular.copy series

      vm.exportPullAndFT = ->
        vm['part'] = true
        params =
          start: vm.beginMonthPullAndFT
          end: vm.endMonthPullAndFT
        exportService.export 'campaign_part_summary', config.resources.exportPullAndFT, params, false

      vm.exportSKUPerOperator = ->
        vm['operator'] = true
        params =
          year: moment(vm.yearSKUPerOperator).format('YYYY')
        exportService.export 'campaign_sku_per_operator', config.resources.exportSKUPerOperator, params, false

      vm.exportKLPInVolume = ->
        vm['volum'] = true
        params =
          year: vm.quarterKLPInVolume.year
          quarter: vm.quarterKLPInVolume.quarter
        exportService.export 'campaign_klp_pene_volum', config.resources.exportKLPInVolume, params, false

      vm.exportKLPInAcct = ->
        vm['acct'] = true
        params =
          year: vm.quarterKLPInAcct.year
          quarter: vm.quarterKLPInAcct.quarter
        exportService.export 'campaign_klp_pene_acct', config.resources.exportKLPInAcct, params, false

      vm.exportPromotionSKUSummary = ->
        vm['promotion'] = true
        params =
          year: vm.quarterPromotionSKUSummary.year
          quarter: vm.quarterPromotionSKUSummary.quarter
        exportService.export 'promotion_sku_summary', config.resources.exportPromotionSKUSummary, params, false

      vm.exportSKUSummary = ->
        vm['campaignSku'] = true
        params =
          year: vm.quarterSKUSummary.year
          quarter: vm.quarterSKUSummary.quarter
        exportService.export 'campaign_sku_summary', config.resources.exportSKUSummary, params, false

      $scope.$on 'exportDataPrepared', (event, type) ->
        switch type
          when 'campaign_part_summary'
            vm['part'] = false
          when 'campaign_sku_per_operator'
            vm['operator'] = false
          when 'campaign_klp_pene_volum'
            vm['volum'] = false
          when 'campaign_klp_pene_acct'
            vm['acct'] = false
          when 'promotion_sku_summary'
            vm['promotion'] = false
          when 'campaign_sku_summary'
            vm['campaignSku'] = false


      vm.selectDate = (type) ->
        switch type
          when 'PullAndFT' # Change FT and Pull Participant Summary statistics data
            _getPullAndFTStats()
          when 'SKUPerOperator' # Change SKU per Operator FT vs Pull statistics data
            _getSKUPerOperatorStats()

      vm.selectQuarter = (type) ->
        switch type
          when 'KLPInVolume' # Change KLP Channel Penetration in Volume statistics data
            _getKLPInVolumeStats()
          when 'KLPInAcct' # Change KLP Channel Penetration in Acct statistics data
            _getKLPInAcctStats()
          when 'PromotionSKUSummary' # Change Promotion SKU Summary statistics data
            _getPromotionSKUSummaryStats()
          when 'SKUSummary' # Change SKU Summary Pull vs FT statistics data
            _getSKUSummaryStats()

      _init()

      vm
  ]

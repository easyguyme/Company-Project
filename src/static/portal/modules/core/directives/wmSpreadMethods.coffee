define ['core/coreModule'], (mod) ->
  mod.directive 'wmSpreadMethods', [
    'restService'
    '$modal'
    '$location'
    (restService, $modal, $location) ->
      restrict: 'EA'
      replace: true
      scope:
        ngModel: '=ngModel'
      templateUrl: '/build/modules/core/partials/spreadMethods.html'
      link: (scope, elem, attr, ctrl) ->
        vm = scope

        DOMAIN = location.protocol + '//' + location.host
        OAUTHLINK = "#{DOMAIN}/api/mobile/base-oauth?channelId={{channelId}}&redirect={{redirectLink}}"

        methods =
          GRAPHIC: 'graphic'
          MENU: 'menu'
          URL: 'url'
          QRCODE: 'qrcode'

        defaultOptions =
          menu:
            type: 'menu'
            icon: '/images/core/spread_menu_icon.png'
            title: 'spread_menu_title'
            description: ''
            submitHandler: ''
            oauthLink: OAUTHLINK
            redirectLink: ''
            modal:
              title: 'select_channel'
              description: ''
              menuName: ''
          url:
            type: 'url'
            icon: '/images/core/spread_url_icon.png'
            title: 'spread_url_title'
            description: ''
            submitHandler: ''
            oauthLink: OAUTHLINK
            redirectLink: ''
            modal:
              description: ''
          qrcode:
            type: 'qrcode'
            icon: '/images/core/spread_qrcode_icon.png'
            title: 'spread_qrcode_title'
            description: ''
            submitHandler: ''
            oauthLink: OAUTHLINK
            redirectLink: ''
            modal:
              prefix: ''
              description: ''
        ###
        graphic:
          type: 'graphic'
          icon: '/images/core/spread_graphic_icon.png'
          title: 'spread_graphic_title'
          description: ''
          submitHandler: ''
          oauthLink: OAUTHLINK
          redirectLink: ''
        ###

        _packageMethods = (methods) ->
          ways = []

          if methods and angular.isArray(methods) and methods.length
            for method in methods
              if method.type and defaultOptions[method.type]
                if method.redirectLink
                  method.redirectLink = encodeURIComponent(decodeURIComponent(method.redirectLink))
                method = $.extend true, {}, defaultOptions[method.type], method
              ways.push method

          ways


        _findMethodAccordingType = (type) ->
          method = null

          for item in vm.methods
            if item.type is type
              method = angular.copy item
              break

          method

        submitHandlers =
          pickedmenu: (transferLink) ->
            $location.url transferLink
          pickedurl: (authLink) ->
            console.log authLink if console and authLink
          pickedqrcode: (authLink) ->
            console.log authLink if console and authLink

        vm.$watch 'ngModel', (newVal, oldVal) ->
          if newVal
            vm.methods = _packageMethods vm.ngModel
            vm.pickedMethod = vm.methods[0].type if vm.methods.length


        vm.selectMethod = (type) ->
          vm.pickedMethod = type
          return

        vm.submit = ->
          method = _findMethodAccordingType vm.pickedMethod
          params = {}

          if method
            params.oauthLink = method.oauthLink if method.oauthLink
            params.redirectLink = method.redirectLink if method.redirectLink

            fileName = ''

            switch vm.pickedMethod
              when methods.MENU
                fileName = 'spreadByAppendMenu'
              when methods.QRCODE
                fileName = 'spreadByDownloadQrcode'
              when methods.URL
                fileName = 'spreadByCopyURL'

            params = $.extend true, {}, params, method.modal

            modalInstance = $modal.open(
              templateUrl: "/build/modules/core/partials/#{fileName}.html"
              controller: "wm.ctrl.core.#{fileName} as #{vm.pickedMethod}"
              windowClass: "spread-methods-dialog"
              resolve:
                modalData: ->
                  params
            )

            modalInstance.result.then (data) ->
              submitHandlers["picked#{vm.pickedMethod}"](data) if submitHandlers["picked#{vm.pickedMethod}"]

        vm
  ]

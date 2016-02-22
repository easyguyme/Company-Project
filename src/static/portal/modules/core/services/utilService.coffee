define [
  'core/coreModule'
  'wm/config'
], (mod, config) ->
  mod.factory 'utilService', [
    'restService'
    '$q'
    'notificationService'
    (restService, $q, notificationService) ->
      util = {}

      origins =
        WECHAT: 'wechat'
        WEIBO: 'weibo'
        ALIPAY: 'alipay'
        PORTAL: 'portal' # offline
        APP_ANDROID: 'app:android'
        APP_IOS: 'app:ios'
        APP_WEB: 'app:web' # mobile browser
        APP_WEBVIEW: 'app:webview' # mobile browser
        OTHERS: 'others' # pc

      appTextMap =
        app_android: 'app_android'
        app_ios: 'app_ios'
        app_web: 'app_web'
        app_webview: 'app_webview'

      util.formateString = (length, str) ->
        newStr = str
        if str and str.length > length
          newStr = str.substr(0, length) + '...'
        newStr

      util.getArrayElemIndex = (array, elem, field) ->
        result = -1
        for item, index in array
          if elem? and field?
            target = item
            source = elem

            if typeof target is 'object' and target.hasOwnProperty(field)
              target = target[field]
            if typeof source is 'object' and source.hasOwnProperty(field)
              source = source[field]
            if target is source
              result = index
              break

          else if elem is item
            result = index
            break

        return result

      util.getArrayElem = (array, elem, field) ->
        result = @getArrayElemIndex(array, elem, field)
        return if result isnt -1 then array[result] else null

      #Format channel list
      util.formatChannels = (channels, hasOffline) ->
        if hasOffline? and not hasOffline
          accounts = []
        else
          accounts = [
            id: 'portal'
            name: 'portal'
            icon: 'portal'
            tip: 'portal'
            enable: true
          ,
            id: 'app:android'
            name: 'app_android'
            icon: 'app_android'
            tip: 'app_android'
            enable: true
          ,
            id: 'app:ios'
            name: 'app_ios'
            icon: 'app_ios'
            tip: 'app_ios'
            enable: true
          ,
            id: 'app:webview'
            name: 'app_webview'
            icon: 'app_webview'
            tip: 'app_webview'
            enable: true
          ,
            id: 'app:web'
            name: 'app_web'
            icon: 'app_web'
            tip: 'app_web'
            enable: true
          ,
            id: 'others'
            name: 'others'
            icon: 'others'
            tip: 'others'
            enable: true
          ]

        angular.forEach channels, (account) ->
          type = account.origin or account.type
          type = type.toLowerCase() if type

          switch type
            when origins.WECHAT
              if not account.isService?
                serviceAccounts = ['service_auth_account', 'service_account']
                account.isService = $.inArray(account.type.toLowerCase(), serviceAccounts) > -1

              account.origin = account.type = type
              account.icon = angular.copy origins.WECHAT

              account.icon += if account.isService then '_service' else '_subscription'

              if account.status and account.status isnt 'enable'
                account.icon += '_disabled'

              account.tip = 'follower_wechat_' + (if account.isService then 'service' else 'subscription') + '_number'
            when origins.WEIBO
              account.origin = account.type = type
              account.icon = angular.copy origins.WEIBO

              if account.status and account.status isnt 'enable'
                account.icon += '_disabled'

              account.tip = 'follower_weibo_service_number'
            when origins.ALIPAY
              account.origin = account.type = type
              account.icon = angular.copy origins.ALIPAY
              account.tip = 'follower_alipay_number'

          account.id = account.channelId if not account.id and account.channelId
          account.enable = true

          if account.status and account.status isnt 'enable'
            account.enable = false

          accounts.splice -1, 0, account

        accounts

      #Format single channel
      util.formatChannel = (channel) ->
        item = {}
        if channel
          accountType = channel.type?.toLowerCase()
          socialAccountType = ''
          tip = ''
          origin = channel.origin
          switch origin
            when origins.WECHAT
              serviceAccounts = ['service_auth_account', 'service_account']
              subscriptionAccounts = ['subscription_account', 'subscription_auth_account']
              socialAccountType = 'wechat_service' if jQuery.inArray(accountType, serviceAccounts) > -1
              socialAccountType = 'wechat_subscription'if jQuery.inArray(accountType, subscriptionAccounts) > -1
              type = channel.type?.toLowerCase()
            when origins.WEIBO
              socialAccountType = origin
              type = socialAccountType
            when origins.ALIPAY
              socialAccountType = origin
              type = socialAccountType
            when origins.PORTAL
              socialAccountType = origin
              tip = 'portal'
            when origins.APP_ANDROID, origins.APP_IOS, origins.APP_WEB, origins.APP_WEBVIEW
              socialAccountType = origin.replace ':', '_'
              tip = appTextMap[socialAccountType]
            else
              socialAccountType = origins.OTHERS
              tip = origins.OTHERS

          status = channel.status
          if status and status is 'disable'
            icon = "/images/customer/#{origin}_disabled.png"
          else
            icon = "/images/customer/#{socialAccountType}.png"

          item =
            text: channel.name
            type: type if type
            tip: tip if tip
            icon: icon if icon
            status: channel.status is 'enable'
        item

      util.replaceCharacter = (string) ->
        if string
          reg = /\<|\>|\"|\'|\&/g
          string = string.replace reg, (matchStr) ->
            switch matchStr
              when '<'
                return '&lt;'
              when '>'
                return '&gt;'
              when '\"'
                return '&quot;'
              when '\''
                return '&#39;'
              when '&'
                return '&amp;'
          string = string.replace /\n/g, '<br>'
        return string

      util.webhooksObj = ->
        maps = {}
        defered = $q.defer()
        restService.get config.resources.webhooks, (data) ->
          for item in data
            maps[item.name] = item.useWebhook or false
          defered.resolve(maps)
        defered.promise

      util.checkLocationIllegal = (location) ->
        specialProvinces = ['台湾省', '台灣省', '香港特别行政区', '香港特別行政區', '澳门特别行政区', '澳門特別行政區']
        specialProvince = '海外'

        if specialProvince is location.province
          return false
        else if $.inArray(location.province, specialProvinces) > -1 and location.city
          return false
        else if not location.province or not location.city or not location.county
          notificationService.warning 'management_store_select_location_msg'
          return true

      util
  ]

define [
  'wm/app'
  'wm/config'
  'wm/modules/microsite/controllers/articleChannelCtrl'
], (app, config) ->
  ###
  'directives/wmPicUpload'
  'directives/wmLinkSelect'
  'directives/wmLocation'
  'directives/wmUEditor'
  ###

  ###Wrap your controllers with the name comment block###

  ###Vincent###

  app.registerController('wm.ctrl.microsite.page.config.title', [
    '$scope'
    ($scope) ->
      vm = this
      vm.titleStyles = [
        {
          label: 'content_component_config_title_default_style'
          value: 'plain'
        }
        {
          label: 'content_component_config_title_style_one'
          value: 'dot'
        }
        {
          label: 'content_component_config_title_style_two'
          value: 'flag'
        }
        {
          label: 'content_component_config_title_style_three'
          value: 'arrow'
        }
      ]

      vm.selectStyle = (idx) ->
        vm.data.style = vm.titleStyles[idx].value
        return

      vm.data =
        name: ''
        link: ''
        style: 'plain'

      $scope.$on 'refreshData', (e, data) ->
        vm.data = data
        return
      $scope.$emit 'cptLoaded'
      return
  ]).registerController('wm.ctrl.microsite.page.config.cover1', [
    '$scope'
    'notificationService'
    ($scope, notificationService) ->
      vm = this
      vm.data =
        slideInfo: [
          {
            name: ''
            pic: ''
          }
          {
            name: ''
            pic: ''
          }
        ]
        navInfo: [
          {
            name: ''
            iconUrl: ''
            linkUrl: ''
          }
          {
            name: ''
            iconUrl: ''
            linkUrl: ''
          }
          {
            name: ''
            iconUrl: ''
            linkUrl: ''
          }
          {
            name: ''
            iconUrl: ''
            linkUrl: ''
          }
          {
            name: ''
            iconUrl: ''
            linkUrl: ''
          }
          {
            name: ''
            iconUrl: ''
            linkUrl: ''
          }
        ]

      vm.addSlide = ->
        if vm.data.slideInfo.length < 6
          vm.data.slideInfo.push {}
        else
          notificationService.error 'content_component_config_cover1_slide_most'
        return

      vm.removeSlide = (index, $event) ->
        notificationService.confirm $event,{
          title: 'content_delete_slide_title'
          submitCallback: _removeSlideHandler
          params: [index]
        }

      _removeSlideHandler = (index) ->
        $scope.$apply ->
          if vm.data.slideInfo.length > 2
            vm.data.slideInfo.splice index, 1
          else
            notificationService.error 'content_component_config_cover1_slide_least'
          return

      vm.addInfo = ->
        if vm.data.navInfo.length < 6
          item = {
            name: ''
            iconUrl: ''
            linkUrl: ''
          }
          vm.data.navInfo.push item

        else
          notificationService.error 'content_component_config_cover1_nav_most'
        return

      vm.deleteInfo = (index, $event) ->
        notificationService.confirm $event,{
          title: 'content_delete_info_title'
          submitCallback: _deleteInfoHandler
          params: [index]
        }

      _deleteInfoHandler = (index) ->
        $scope.$apply ->
          if vm.data.navInfo.length > 3
            vm.data.navInfo.splice index, 1
          else
            notificationService.error 'content_component_config_cover1_nav_least'
          return

      $scope.$on 'refreshData', (e, data) ->
        vm.data = data if data.slideInfo and data.navInfo
        vm.data.setting = vm.data.setting or 3000
        return
      $scope.$emit 'cptLoaded'
      return
  ]).registerController('wm.ctrl.microsite.page.config.cover2', [
    '$scope'
    '$modal'
    'notificationService'
    ($scope, $modal, notificationService) ->
      vm = this
      vm.data = navInfo: [
        {}
        {}
        {}
        {}
      ]

      vm.addInfo = ->
        if vm.data.navInfo.length < 8
          vm.data.navInfo.push {}
        else
          notificationService.error 'MICROSITE_COVER2_NAV_COUNT_MOST'
        return

      vm.deleteInfo = (index) ->
        if vm.data.navInfo.length > 1
          vm.data.navInfo.splice index, 1
        else
          notificationService.error 'MICROSITE_COVER2_NAV_COUNT_LEAST'
        return

      $scope.$on 'refreshData', (e, data) ->
        vm.data = data if data. navInfo
        return
      $scope.$emit 'cptLoaded'
      return
  ]).registerController('wm.ctrl.microsite.page.config.cover3', [
    '$scope'
    '$modal'
    'notificationService'
    ($scope, $modal, notificationService) ->
      vm = this
      vm.data =
        navs: [
          {
            name: ''
            pic: ''
            icon: ''
            linkUrl: ''
          }
        ]

      vm.addNav = ->
        if vm.data.navs.length < 6
          vm.data.navs.push {}
        else
          notificationService.error 'content_component_config_cover3_most'
        return

      vm.removeNav = (index, $event) ->
        notificationService.confirm $event,{
          title: 'content_delete_info_title'
          submitCallback: _deleteInfoHandler
          params: [index]
        }

      _deleteInfoHandler = (index) ->
        $scope.$apply ->
          if vm.data.navs.length > 1
            vm.data.navs.splice index, 1
          else
            notificationService.error 'content_component_config_cover3_least'
          return

      $scope.$on 'refreshData', (e, data) ->
        vm.data = data if data.navs
        return
      $scope.$emit 'cptLoaded'
      return
  ]).registerController 'wm.ctrl.microsite.page.config.tab', [
    '$scope'
    ($scope) ->
      vm = this
      vm.data =
        tabs: [
          {
            name: ''
            active: true
          }
          {
            name: ''
            active: false
          }
        ]
      $scope.$on 'refreshData', (e, data) ->
        vm.data = data
        return
      $scope.$emit 'cptLoaded'
      return
  ]

  ###Vincent###

  ###Sara###

  app.registerController('wm.ctrl.microsite.page.config.slide', [
    '$scope'
    '$modal'
    '$timeout'
    'notificationService'
    'validateService'
    '$filter'
    ($scope, $modal, $timeout, notificationService, validateService, $filter) ->
      vm = this

      vm.settings = [
        {
          text: 'content_component_config_slide_no_carousel'
          value: '0'
        }
        {
          text: 'content_slide_carousel_3_sec'
          value: '3000'
        }
        {
          text: 'content_slide_carousel_5_sec'
          value: '5000'
        }
        {
          text: 'content_slide_carousel_10_sec'
          value: '10000'
        }
      ]

      vm.data =
        info: [
          {
            name: ''
            pic: ''
            linkUrl: ''
          }
          {
            name: ''
            pic: ''
            linkUrl: ''
          }
        ]
        setting: vm.settings[0].value

      vm.data.setting = vm.settings[0].value

      vm.addSlide = ->
        if vm.data.info.length < 6
          vm.data.info.push {name: '', pic: '', linkUrl: ''}
        else
          notificationService.error 'content_component_config_slide_max_six'
        return

      vm.removeSlide = ($event, index) ->
        if vm.data.info.length >= 3
          vm.deleteIndex = index
          notificationService.confirm $event, {submitCallback: _deleteConf, cancelCallback: _cancelDeleteConf, params: [index]}
        else
          notificationService.error 'content_component_config_slide_min_two'
        return

      vm.checkData = ->
        canSubmit = true
        for item, index in vm.data.info
          if not item.pic
            canSubmit = false
            validateService.highlight $('#pic' + index), $filter('translate')('microsite_set_image')
        $scope.$parent.$parent.cpts.sendData.apply(vm, [vm.data]) if canSubmit

      _deleteConf = (index) ->
        $timeout ->
          vm.deleteIndex = null
          vm.data.info.splice index, 1
          return
        , 0
        return

      _cancelDeleteConf = (index) ->
        $timeout ->
          vm.deleteIndex = null
          return
        , 0
        return

      $scope.$on 'refreshData', (e, data) ->
        vm.data = data
        return
      $scope.$emit 'cptLoaded'
      return
  ]).registerController('wm.ctrl.microsite.page.config.text', [
    '$scope'
    ($scope) ->
      vm = this
      _init = ->
        vm.txtStyles = [
          {
            'label': 'content_component_config_text_style_one'
            'value': 'full'
          }
          {
            'label': 'content_component_config_text_style_two'
            'value': 'part'
          }
        ]
        vm.data = setting: vm.txtStyles[0]['value']
        vm.isSelectedFull = true
        return

      _init()

      vm.selectStyle = (value) ->
        if value is vm.txtStyles[0]['value']
          vm.isSelectedFull = true
        else
          vm.isSelectedFull = false
        return

      ###$scope.$watch 'text.data.text', (newValue)->
        if newValue
          iframe = document.getElementById('ueditor_0')
          $body = $ iframe.contentWindow.document.body
          $body.css('width', '89%')
        return###

      vm.config = {
        toolbars: [
          ['pasteplain', 'fontsize', 'blockquote', 'removeformat', 'link', 'unlink', 'fullscreen'],
          ['bold', 'italic', 'underline', 'fontborder','backcolor', '|',
           'justifyleft', 'justifycenter', 'justifyright', 'justifyjustify', '|',
           'rowspacingtop', 'rowspacingbottom', 'lineheight', '|',
           'insertorderedlist', 'insertunorderedlist', '|',
           'imagenone', 'imageleft', 'imageright', 'imagecenter']
        ],
        initialStyle: 'ol, ul{width:initial!important}'
      }
      $scope.$on 'refreshData', (e, data) ->
        if data['setting'] is vm.txtStyles[0]['value']
          data['setting'] = vm.txtStyles[0]['value']
          vm.isSelectedFull = true
        else
          data['setting'] = vm.txtStyles[1]['value']
          vm.isSelectedFull = false
        vm.data = data
        return
      $scope.$emit 'cptLoaded'
      return
  ]).registerController('wm.ctrl.microsite.page.config.messages', [
    '$scope'
    'validateService'
    'notificationService'
    ($scope, validateService, notificationService) ->
      vm = this
      _init = ->
        vm.textTypes = [
          {
            text: 'MICROSITE_MESSAGE_SINGLE'
            value: 'single'
          }
          {
            text: 'MICROSITE_MESSAGE_MULTIPLE'
            value: 'multiple'
          }
        ]
        vm.data = info: [ {
          textType: vm.textTypes[0]['value']
          required: true
        } ]
        return

      _init()

      vm.addMessage = ->
        if vm.data.info.length < 8
          vm.data.info.push
            textType: vm.textTypes[0]['value']
            required: true
        else
          notificationService.error 'MICROSITE_MESSAGE_MOST'
        return

      vm.removeMessage = (index) ->
        if vm.data.info.length > 1
          vm.data.info.splice index, 1
        else
          validateService.showError 'question', 'MICROSITE_UNDELETE_DEFAULT', true
        return
      $scope.$on 'refreshData', (e, data) ->
        info = data.info
        i = 0
        while i < info.length
          if info[i]['required'] is 'true'
            info[i]['required'] = true
          i++
        vm.data = data
        return
      $scope.$emit 'cptLoaded'
      return
  ]).registerController('wm.ctrl.microsite.page.config.tel', [
    '$scope'
    'validateService'
    ($scope, validateService) ->
      vm = this
      vm.telStyles = [
        {
          'label': 'content_component_config_tel_style_one'
          'value': '1'
        }
        {
          'label': 'content_component_config_tel_style_two'
          'value': '2'
        }
      ]

      vm.data =
        tel: ''
        style: vm.telStyles[0]['value']

      vm.validateTel = ->
        validateService.checkTelNum vm.data.tel
        # telPattern = /^1[3|4|5|7|8](\d{9})$/
        # if not telPattern.test vm.data.tel
        #   return 'content_component_config_tel_style_error'

      $scope.$on 'refreshData', (e, data) ->
        if data['style'] is vm.telStyles[0]['value']
          vm.data.style = vm.telStyles[0]['value']
        else
          vm.data.style = vm.telStyles[1]['value']
        vm.data = data
        return
      $scope.$emit 'cptLoaded'
      return
  ]).registerController 'wm.ctrl.microsite.page.config.share', [
    '$scope'
    ($scope) ->
      vm = this
      $scope.$on 'refreshData', (e, data) ->
        vm.data = data
        return
      $scope.$emit 'cptLoaded'
      return
  ]

  ###Sara###

  ###HankLiu###

  app.registerController('wm.ctrl.microsite.page.config.pic', [
    '$scope'
    '$modal'
    ($scope, $modal) ->
      vm = this
      vm.data =
        name: ''
        imageUrl: ''
        linkUrl: ''

      $scope.$on 'refreshData', (e, data) ->
        vm.data = data
        return
      $scope.$emit 'cptLoaded'
      return
  ]).registerController('wm.ctrl.microsite.page.config.table', [
    '$scope'
    ($scope) ->
      vm = this

      vm.data =
        content: '<table>
                                <tbody>
                                <tr class="firstRow">
                                    <td width="139" valign="middle" style="word-break: break-all;" class="selectTdClass" align="center">内容1</td>
                                    <td width="139" valign="middle" style="word-break: break-all;" class="selectTdClass" align="center">内容2</td>
                                    <td width="139" valign="middle" style="word-break: break-all;" class="selectTdClass" align="center">内容3</td>
                                </tr>
                                <tr>
                                    <td width="139" valign="middle" style="word-break: break-all;" class="selectTdClass" align="center">内容4</td>
                                    <td width="139" valign="middle" style="word-break: break-all;" class="selectTdClass" align="center">内容5</td>
                                    <td width="139" valign="middle" style="word-break: break-all;" class="selectTdClass" align="center">内容6</td>
                                </tr>
                                <tr>
                                    <td width="139" valign="middle" style="word-break: break-all;" class="selectTdClass" align="center">内容7</td>
                                    <td width="139" valign="middle" style="word-break: break-all;" class="selectTdClass" align="center">内容8</td>
                                    <td width="139" valign="middle" style="word-break: break-all;" class="selectTdClass" align="center">内容9</td>
                                </tr>
                                </tbody>
                            </table>'

      vm.config = {
        toolbars: [
            ['inserttable', 'insertparagraphbeforetable', 'insertrow', 'deleterow',
            'mergecells', 'mergeright', 'mergedown', 'splittocells', 'splittorows', 'splittocols',
            'bold', 'italic', 'underline', 'strikethrough', 'forecolor', 'backcolor', 'justifyleft',
            'justifycenter', 'justifyright', 'justifyjustify']
        ]
      }

      vm.formatData = ->
        data = vm.data
        content = data.content
        identiferStart = '<table>'
        identiferEnd = '</table>'
        firstPosition = content.indexOf(identiferStart)
        lastPosition = content.lastIndexOf(identiferEnd) + identiferEnd.length
        Tabblecontent = content.substring firstPosition, lastPosition
        beforeTableContent = content.substring 0, firstPosition
        afterTableContent = content.substring lastPosition, content.length
        beforeTableContent = removeBlankAndP beforeTableContent
        afterTableContent = removeBlankAndP afterTableContent
        data.content = beforeTableContent + Tabblecontent + afterTableContent
        vm.data = data
        $scope.$parent.$parent.cpts.sendData.apply(vm, [vm.data])

      removeBlankAndP = (str) ->
        str = str.replace /<\/?p>/g, ''
        str = str.replace /<br ?\/>/g, ''
        return str

      $scope.$on 'refreshData', (e, data) ->
        vm.data = data
        return
      $scope.$emit 'cptLoaded'
      return
  ]).registerController('wm.ctrl.microsite.page.config.sms', [
    '$scope'
    'validateService'
    ($scope, validateService) ->
      vm = this
      vm.data =
        tel: ''
        smsText: ''

      vm.validateTel = ->
        validateService.checkTelNum vm.data.tel
        # telPattern = /^1[3|4|5|7|8](\d{9})$/

        # if not telPattern.test vm.data.tel
        #   return 'content_component_config_tel_style_error'

      $scope.$on 'refreshData', (e, data) ->
        vm.data = data
        return
      $scope.$emit 'cptLoaded'
      return
  ]).registerController('wm.ctrl.microsite.page.config.html', [
    '$scope'
    ($scope) ->
      vm = this
      vm.data = content: ''
      $scope.$on 'refreshData', (e, data) ->
        vm.data = data
        return
      $scope.$emit 'cptLoaded'
      return
  ]).registerController('wm.ctrl.microsite.page.config.contact', [
    '$scope'
    ($scope) ->
      vm = this
      vm.data =
        name: ''
        tel: ''
        email: ''
        qq: ''
        location:
            province: ''
            city: ''
            county: ''

      vm.validateQQ = ->
        qqPattern = /^\d{5,10}$/
        if vm.data.qq and not qqPattern.test vm.data.qq
          return 'content_component_confg_qq_style_error'

      $scope.$on 'refreshData', (e, data) ->
        vm.data = data
        return
      $scope.$emit 'cptLoaded'
      return
  ]).registerController('wm.ctrl.microsite.page.config.map', [
    '$scope'
    '$location'
    '$translate'
    ($scope, $location, $translate) ->
      vm = this

      path = ''
      win = null

      _init = ->
        vm.data =
          name: ''
          url: ''
          town: ''
          location:
            province: ''
            city: ''
            county: ''
          position:
            lng: null
            lat: null
          isDisplayMapIcon: true
        return

      _init()

      # Does it need to be relocation the map or not?
      vm.checkAddress = ->
        addressFormTip = ''
        if (not vm.data.position or (not vm.data.position.lat and not vm.data.position.lng))
          addressFormTip = 'content_component_config_map_relocation_msg'
        addressFormTip = '' if not vm.data.isDisplayMapIcon
        return addressFormTip

      _clearRelocationAddressMsg = ->
        $relocationAddressInput = $ '#relocationAddress'
        $relocationAddressInput.removeClass 'form-control-error'
        $relocationAddressInput.parent('.form-group').removeClass 'highlight'
        $relocationAddressInput.next('span.form-tip:first').remove() if $relocationAddressInput.next('span.form-tip:first').length isnt 0

      _showlocationAddressMsg = (message) ->
        $relocationAddressInput = $ '#relocationAddress'
        $relocationAddressInput.addClass 'form-control-error'
        $relocationAddressInput.parent('.form-group').addClass 'highlight'
        if $relocationAddressInput.next('span.form-tip:first').length isnt 0
          $relocationAddressInput.next('span.form-tip:first').text message
        else
          $relocationAddressInput.after "<span class='form-tip'>#{message}</span>"

      # Change location call back handler
      vm.changeLocation = ->
        _resetPosition()
        _clearRelocationAddressMsg()
        return

      _resetPosition = ->
        vm.data.position =
          lng: null
          lat: null

      _openMapComponent = ->
        host = $location.$$host
        path = "http://#{host}/map/microsite"
        width = 800
        height = 600
        left = (window.innerWidth - width) / 2
        top = (window.innerHeight - height) / 2
        params = "height=#{height},width=#{width},left=#{left},top=#{top},toolbar=no,menubar=no,scrollbars=no,resizable=no,location=no,status=no,directories=no,titlebar=no,alwaysRaised=yes"
        win = window.open(path, '搜索定位', params)
        win.focus()
        return

      # Show the map
      vm.relocationAddress = ->
        translations = [
          'content_component_map_fill_street_tip'
          'content_component_map_select_location_tip'
        ]
        if vm.data.location.province is ''
          $translate(translations).then (map) ->
            _showlocationAddressMsg map['content_component_map_fill_street_tip']
        else if not vm.data.town
          $translate(translations).then (map) ->
            _showlocationAddressMsg map['content_component_map_select_location_tip']
        else
          _openMapComponent()
        return

       window.addEventListener 'message', (event) ->
        data = event.data
        if typeof data is 'string' and data is 'ready'
          win.postMessage vm.data, path if win
        else if typeof data is 'object'
          vm.data.position = angular.copy data.position if data.position

          if data.position
            vm.data.url = "http://api.map.baidu.com/staticimage?center=#{data.position.lng},#{data.position.lat}&width=300&height=260&zoom=14
              &markers=#{data.position.lng},#{data.position.lat}&markerStyles=-1,http://api.map.baidu.com/images/marker_red.png,-1"
            _clearRelocationAddressMsg()

      vm.submitCallback = ->
        canSendData = false
        if vm.data.isDisplayMapIcon
          if vm.checkAddress() is ''
            canSendData = true
        else
          canSendData = true
        _clearRelocationAddressMsg() if canSendData
        canSendData

      $scope.$on 'refreshData', (e, data) ->
        vm.data = data
        dataUrl = data.url
        centerArray = dataUrl.substring(dataUrl.indexOf('center=') + 7, dataUrl.indexOf('&width')).split(',')
        if centerArray.length is 2
          vm.data.position =
            lng: parseFloat centerArray[0]
            lat: parseFloat centerArray[1]
        return
      $scope.$emit 'cptLoaded'
      return
  ]).registerController 'wm.ctrl.microsite.page.config.delimiter', [
    '$scope'
    ($scope) ->
      vm = this
      $scope.$on 'refreshData', (e, data) ->
        vm.data = data
        return
      $scope.$emit 'cptLoaded'
      return
  ]

  ###HankLiu###

  ###Mark###

  app.registerController('wm.ctrl.microsite.page.config.nav', [
    '$scope'
    '$modal'
    '$timeout'
    'notificationService'
    'restService'
    ($scope, $modal, $timeout, notificationService, restService) ->
      vm = this
      init = ->
        vm.data = infos: [
          {
            name: ''
            linkUrl: ''
          }
          {
            name: ''
            linkUrl: ''
          }
        ]
        return

      init()
      #check nav name
      vm.checkName = (name) ->
        formTip = ''
        if not name.replace(/^(\s)*|(\s)*$/g, '')
          formTip = 'content_component_config_nav_name_tip'
        formTip

      #add a editing item for navigation

      vm.addInfo = ->
        if vm.data.infos.length < 5
          vm.data.infos.push
            name: ''
            linkUrl: ''
        else
          notificationService.error 'content_component_config_nav_most_five'
        return

      #delete a editing item for navigation

      vm.deleteInfo = ($event, index) ->
        if vm.data.infos.length > 2
          vm.deleteIndex = index
          notificationService.confirm $event, {submitCallback: _deleteConf, cancelCallback: _cancelDeleteConf, params: [index]}
        else
          notificationService.error 'content_component_config_nav_least_two'
        return

      _deleteConf = (index) ->
        $timeout ->
          vm.deleteIndex = null
          vm.data.infos.splice index, 1
          return
        , 0
        return

      _cancelDeleteConf = (index) ->
        $timeout ->
          vm.deleteIndex = null
          return
        , 0
        return

      vm.checkData = ->
        $('.highlight .form-tip').text ''
        $scope.$parent.$parent.cpts.sendData.apply(vm, [vm.data])

      $scope.$on 'refreshData', (e, data) ->
        vm.data = data
        return
      $scope.$emit 'cptLoaded'
      return
  ]).registerController('wm.ctrl.microsite.page.config.album', [
    '$scope'
    '$modal'
    '$timeout'
    'notificationService'
    ($scope, $modal, $timeout, notificationService) ->
      vm = this
      init = ->
        vm.columns = [
          { name: '1' }
          { name: '2' }
          { name: '3' }
          { name: '4' }
        ]
        vm.data =
          title: ''
          album: [
            {
              url: ''
              description: ''
            }
            {
              url: ''
              description: ''
            }
            {
              url: ''
              description: ''
            }
          ]
          column: vm.columns[2].name
        return

      init()
      #add a picture item

      vm.addAlbum = ->
        if vm.data.album.length < 20
          vm.data.album.push
            url: ''
            description: ''
        else
          notificationService.error 'content_component_config_album_most_twenty'
        return

      #delete a picture item

      vm.deletePicture = ($event, index) ->
        if vm.data.album.length > 3
          vm.deleteIndex = index
          notificationService.confirm $event, {submitCallback: _deleteConf, cancelCallback: _cancelDeleteConf, params: [index]}
        else
          notificationService.error 'content_component_config_album_least_three'
        return

      _deleteConf = (index) ->
        $timeout ->
          vm.deleteIndex = null
          vm.data.album.splice index, 1
          return
        , 0
        return

      _cancelDeleteConf = (index) ->
        $timeout ->
          vm.deleteIndex = null
          return
        , 0
        return

      vm.changeSelect = (value, index) ->
        vm.data.column = value
        return

      $scope.$on 'refreshData', (e, data) ->
        vm.data = data
        return
      $scope.$emit 'cptLoaded'
      return
  ]).registerController 'wm.ctrl.microsite.page.config.link', [
    '$scope'
    ($scope) ->
      vm = this
      init = ->
        vm.displayStyles = [
          {
            'label': 'content_component_config_layout_left'
            'value': 'left'
          }
          {
            'label': 'content_component_config_layout_middle'
            'value': 'center'
          }
          {
            'label': 'content_component_config_layout_right'
            'value': 'right'
          }
        ]
        return

      init()
      vm.data =
        name: ''
        linkUrl: ''
        display: 'left'

      $scope.$on 'refreshData', (e, data) ->
        vm.data = data
        return
      $scope.$emit 'cptLoaded'
      return
  ]

  ###Mark###

  app.registerController 'wm.ctrl.microsite.page.config.articles', [
    '$scope'
    '$modal'
    '$stateParams'
    '$timeout'
    'restService'
    'validateService'
    '$q'
    ($scope, $modal, $stateParams, $timeout, restService, validateService, $q) ->
      vm = this
      vm.data = {}
      defaultOption = {id: '', 'name': 'content_component_config_articles_select_field'}
      isFirst = true
      hasNum = false

      vm.choices = {
        valueChioces: []
        funChioces: [
          {
            text: 'content_article_create_channel'
            action: ->
              modalInstance = $modal.open(
                templateUrl: '/build/modules/microsite/partials/articleChannelModal.html'
                controller: 'wm.ctrl.microsite.editArticles'
                windowClass: 'tagedit-dialog'
                resolve:
                  modalData: ->
                    {
                      channels: angular.copy vm.channels
                      index: -1
                      isEdit: false
                    }
              ).result.then( (data) ->
                if data
                  vm.choices.valueChioces.push data
                  vm.channels.push data
                  vm.data.channelId = data.id
                  getFields data.id
              )
          }
        ]
      }

      vm.showInfo = ->
        vm.info = true

      fetchArticleChannels = ->
        defered = $q.defer()
        condition =
          'orderBy': {'createdAt': 'asc'}
          'per-page': 200
        restService.get config.resources.articleChannels, condition, (data) ->
          defered.resolve data.items
        defered.promise

      getArticleChannels = ->
        fetchArticleChannels().then (channels) ->
          if channels and (isFirst or not hasNum)
            vm.channels = angular.copy channels
            vm.choices.valueChioces = angular.copy channels
            vm.data.channelId = vm.channels[0].id

      getDisplayNumbers = ->
        vm.displayNumbers = [
          {
            text: '3',
            value: 3
          }
          {
            text: '5',
            value: 5
          }
          {
            text: '10',
            value: 10
          }
          {
            text: '15',
            value: 15
          }
        ]
        vm.data.showNum = vm.displayNumbers[0].value
        return

      getDisplayStyles = ->
        vm.data.style = '1'
        return

      initFields = ->
        vm.style1 = {
          fields: []
        }
        vm.style2 = {
          fields: ['', '']
        }
        vm.style3 = {
          fields: ['', '', '', '']
        }

      resetAllSelectInputs = ->
        vm.channelFields = [defaultOption]
        defaultOptionsId = defaultOption.id
        initFields()

        return

      isFieldDel = (value, objArr, key) ->
        for obj in objArr
          if value is obj[key]
            return false
        return true

      removeDelField = ->
        for field, index in vm.data.fields
          if isFieldDel(field, vm.channelFields, 'id')
            vm.data.fields[index] = ''
        temp = angular.copy vm.data.fields
        len = temp.length
        temp = temp.filter((ele, index) ->
          return ele
        )
        tempLen = temp.length
        if tempLen < len
          for i in [tempLen..len - 1]
            temp[i] = ''
        vm.data.fields = temp

      init = ->
        initFields()
        vm.channelFields = [defaultOption]
        getArticleChannels() if isFirst or not hasNum
        getDisplayNumbers()
        getDisplayStyles()

      init()

      vm.clearSelect = ->
        initFields()

      vm.changeChannel = (channelId, idx) ->
        channel = vm.channels[idx]
        vm.data.channelId = channel.id
        if channel.fields.length > 0
          vm.channelFields = angular.copy channel.fields
          vm.channelFields.unshift defaultOption
        else
          vm.channelFields = [defaultOption]
        initFields()
        return

      #formate send data and call sendData method of parent controller
      vm.formateData = ->
        vm.data.fields = vm['style' + vm.data.style].fields
        $scope.$parent.$parent.cpts.sendData.apply(vm, [vm.data])

      #get custom fields by channel
      getFields = (id) ->
        if vm.channels
          for channel in vm.channels
            if channel.id is id
              vm.channelFields = angular.copy channel.fields
              vm.channelFields.unshift defaultOption
              break

      $scope.$on 'refreshData', (e, data) ->
        isFirst = false
        hasNum = not not data.showNum
        if data.showNum
          fetchArticleChannels().then (channels) ->
            if channels
              vm.channels = angular.copy channels
              vm.choices.valueChioces = angular.copy channels
              vm.data = data
              getFields(vm.data.channelId)
              removeDelField()
              vm['style' + vm.data.style].fields = vm.data.fields
        return
      $scope.$emit 'cptLoaded'
      vm
  ]

  ###Devin###

  app.registerController 'wm.ctrl.microsite.page.config.questionnaire', [
    '$scope'
    'restService'
    '$rootScope'
    ($scope, restService, $rootScope) ->
      vm = this

      _init = ->
        _setLanguage()
        vm.data =
          style: '1'

        vm.styles = [
          {
            'label': 'content_component_config_tel_style_one'
            'value': '1'
          }
          {
            'label': 'content_component_config_tel_style_two'
            'value': '2'
          }
        ]

        vm.columns = []

        restService.get config.resources.questionnairesUnExpired, (data) ->
          if data
            for item in data
              vm.columns.push {
                name: item.name,
                value: item.id
              }

      _setLanguage = ->
         vm.language = $scope.user.language or 'zh_cn'
         $rootScope.$on '$translateChangeSuccess', (event, data) ->
           vm.language = data.language
          return

      _init()

      $scope.$on 'refreshData', (e, data) ->
        vm.data = data
        return
      $scope.$emit 'cptLoaded'
      return
  ]

  ###Devin###

  app.registerController 'wm.ctrl.microsite.page.config.coupon', [
    '$scope'
    'restService'
    '$rootScope'
    'channelService'
    'utilService'
    ($scope, restService, $rootScope, channelService, utilService) ->
      vm = this

      _init = ->
        vm.data =
          style: '1'
          title: '优惠券名称'
          image: '/images/content/conf/webmaterial_article_defaultpicture.png'
        vm.columns = []
        _getcoupons()
        _getChannels()

      _getcoupons = ->
        param =
          'unexpired': moment().valueOf()
          'unlimited': true
          'notSoldOut': true
        restService.get config.resources.coupons, param, (data) ->
          if data
            vm.coupons = angular.copy data.items
            for item in data.items
              vm.columns.push {
                title: item.title
                couponId: item.id
                image: item.picUrl
              }

      _getChannels = ->
        channelService.getChannels().then((channels) ->
          if channels
            vm.data.channelId = angular.copy channels[0].id if not vm.data.channelId and channels[0]
            vm.channels = angular.copy channels
            for channel in vm.channels
              channel.shortName = utilService.formateString 5, channel.name
              channel.icon = channel.type
              channel.icon += '_' + channel.title.split('_')[0] if channel.type is 'wechat'
              channel.icon += '.png'
            return
        )

      _getOauthLink = (channelId, url) ->
        url = encodeURIComponent(url)
        url = "#{location.origin}/api/mobile/coupon?couponId=#{vm.data.couponId}&channelId=#{channelId}&redirect=#{url}"
        url

      vm.changeSelect = (value, index) ->
        style = vm.data.style
        channelId = vm.data.channelId
        vm.data = vm.columns[index]
        vm.data.style = style
        vm.data.channelId = channelId

      vm.createOauth = ->
        vm.data.url = _getOauthLink vm.data.channelId, location.origin + '/mobile/product/coupon?couponId=' + vm.data.couponId
        $scope.$parent.$parent.cpts.sendData.apply(vm, [vm.data])

      _init()

      $scope.$on 'refreshData', (e, data) ->
        channelId = vm.data.channelId
        vm.data = data
        vm.data.channelId = channelId if not data.channelId and channelId
        return
      $scope.$emit 'cptLoaded'
      return
  ]

  return

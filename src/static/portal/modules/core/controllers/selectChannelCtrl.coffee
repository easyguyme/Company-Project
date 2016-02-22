define [
  'core/coreModule'
], (mod) ->
  mod.controller 'wm.ctrl.core.selectChannel', [
    '$scope'
    'channelService'
    '$modalInstance'
    'modalData'
    ($scope, channelService, $modalInstance, modalData) ->
      $scope.selected = modalData.channelId

      $scope.target = modalData.target if modalData.target

      $scope.url = modalData.url if modalData.url

      $scope.text = modalData.text if modalData.text

      channelService.getChannels().then((channels) ->
        $scope.channels = channels
        $scope.selected = channels[0].id if channels.length and not modalData.channelId
      )

      $scope.cancel = ->
        $modalInstance.dismiss()

      $scope.submit = ->

        if $scope.target is 'ueditor'
          if $scope.url
            oauthLink = _getOauthLink $scope.selected, $scope.url
            $modalInstance.close(oauthLink)
        else
          oauthLink = _getOauthLink $scope.selected, $scope.url
          $modalInstance.close(oauthLink)

      _getOauthLink = (channelId, url) ->
        url = encodeURIComponent(url)
        url = "#{location.origin}/api/mobile/base-oauth?channelId=#{channelId}&redirect=#{url}"
        url

      $scope.submitLink = ->

        if $scope.target is 'ueditor'
          if $scope.url
            oauthLink = _getOauthLink $scope.selected, $scope.url
            oauthColor = $scope.colorInput
            oauthText = $scope.text
            oauthData = []
            oauthData.push oauthLink
            oauthData.push oauthColor
            oauthData.push oauthText
            $modalInstance.close(oauthData)
        else
          oauthLink = _getOauthLink $scope.selected, $scope.url
          $modalInstance.close(oauthLink)

      $scope.colors = [
        '#f2f2f2', '#f7f7f7', '#ddd9c3', '#c6d9f0', '#dbe5f1', '#f2dcdb', '#ebf1dd', '#e5e0ec', '#dbeef3', '#fdeada',
        '#d8d8d8', '#595959', '#c4bd97', '#8db3e2', '#b8cce4', '#e5b9b7', '#d7e3bc', '#ccc1d9', '#b7dde8', '#fbd5b5',
        '#bfbfbf', '#3f3f3f', '#938953', '#548dd4', '#95b3d7', '#d99694', '#c3d69b', '#b2a2c7', '#92cddc', '#fac08f',
        '#a5a5a5', '#262626', '#494429', '#1f497d', '#4f81bd', '#c0504d', '#9bbb59', '#8064a2', '#4bacc6', '#f79646',
        '#7f7f7f', '#0c0c0c', '#1d1b10', '#0f243e', '#244061', '#632423', '#4f6128', '#3f3151', '#205867', '#974806',
        '#c00000', '#ff0000', '#ffc000', '#ffff00', '#92d050', '#00b050', '#6ab3f7', '#0070c0', '#002060', '#7030a0'
      ]

      $scope.color = $scope.colors[0]

      $scope.pickColor = (color) ->
          $scope.colorInput = color

      $scope.$watch 'color', (newColor, oldColor) ->
        if newColor is ''
          $scope.colorInput = '#f2f2f2'
        else
          $scope.colorInput = newColor

  ]

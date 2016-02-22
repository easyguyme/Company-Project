define ['core/coreModule'], (mod) ->
  mod.directive 'wmFileUpload', [
    'uploadService'
    'restService'
    'notificationService'
    'judgeDeviceService'
    (uploadService, restService, notificationService, judgeDeviceService) ->
      return (
        restrict: 'A'
        scope:
          ngModel: '='
          processBar: '@'
          maxSize: '@'
          callback: '&'
          picInfo: '@'
          accept: '@'
          acceptTypeNames: '@'
        transclude: true
        template: '<div class="upload-mask-wrap"><input type="file" class="upload-mask" ng-file-select="onFileSelect(files)" ng-model="files" multiple="true" accept="{{acceptType}}"></div>\
                   <div ng-transclude class="upload-btn"></div>\
                   <div class="upload-shadow"></div>\
                   <div class="upload-progress"><div class="upload-progress-bar"></div></div>'
        link: (scope, elem, attrs) ->
          # Define the format of picture.
          imageContentTypes = ->
            return 'image/jpg, image/png, image/jpeg, image/pjpeg, image/x-png, image/gif'

          # Only support image now
          scope.acceptType = scope.accept or imageContentTypes()
          # Get the wrapper
          $btnWrapper = $(elem)
          $btn = $btnWrapper.find '.upload-btn'
          $btnMask = $btnWrapper.find '.upload-mask'
          $maskWrapper = $btnWrapper.find '.upload-mask-wrap'
          $progress = $btnWrapper.find '.upload-progress'
          $progressBar = $btnWrapper.find '.upload-progress-bar'
          $shadow = $btnWrapper.find '.upload-shadow'
          # Fit the upload mask to the customized button
          $btnMask.height $btn.height()
          $maskWrapper.height $btn.height()
          $maskWrapper.width $btn.width()
          $progress.width $btn.width()
          $btnMask.width 100 + $btn.width()
          $progress.hide()
          $shadow.hide()

          _IEHandler = ->
            browser = judgeDeviceService.judgeBrowser()
            if browser isnt "other"
              $btnMask.unbind 'click'
              return

          _showError = (maxSize) ->
            if maxSize < Math.pow(10, 3)
              values =
                maxsize: maxSize + 'K'
            else
              values =
                maxsize: parseInt(maxSize / 1024) + 'M'
            notificationService.error 'image_size_error', false, values

          _IEHandler()

          # Upload the file to qiniu server
          scope.onFileSelect = (files) ->
            if files.length isnt 0
              types = scope.acceptType.split(', ')

              size = if scope.maxSize then parseInt(scope.maxSize) else 300
              for file in files
                if($.inArray(file.type, types) is -1)
                  values =
                    acceptTypeNames: scope.acceptTypeNames or 'JPG/GIF/PNG'
                  notificationService.error 'image_type_error', false, values
                  return
                if file.size > size * 1000
                  _showError(size)
                  return
                $btnWrapper.parent().children(':first').removeClass 'form-control-error'

              $progress.width $btnWrapper.find('.upload-btn').width()
              if scope.processBar is 'true'
                $progress.show()
                $shadow.show()

              uploadService.qiniuUpload(files).then ((urls) ->
                if scope.picInfo
                  picInfos = []
                  for file, index in files
                    picInfo = {
                      name: file.name.substring(0, file.name.lastIndexOf('.'))
                      url: urls[index]
                      size: (file.size / Math.pow(10,6)).toFixed(2)
                    }
                    picInfos.push picInfo

                  scope.callback()(picInfos)
                else
                  scope.ngModel = urls[0]
                  scope.callback()(urls[0]) if scope.callback()
                $progress.hide()
                $shadow.hide()
                return
              ), null, (progress) ->
                $progressBar.width(progress + '%')
                return
              $btnMask[0].value = null
              return
          return
      )
  ]

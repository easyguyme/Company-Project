define ['core/coreModule'], (mod) ->
  mod.directive 'issueAttachmentUpload', [
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
          attachmentInfo: '@'
        transclude: true
        template: '<div class="issue-mask-wrap"><input type="file" class="issue-mask" ng-file-select="onFileSelect(files)" ng-model="files" multiple="true"></div>\
                   <div ng-transclude class="upload-btn"></div>\
                   <div class="upload-shadow"></div>\
                   <div class="upload-progress"><div class="upload-progress-bar"></div></div>'
        link: (scope, elem, attrs) ->
          # Get the wrapper
          $btnWrapper = $(elem)
          $btn = $btnWrapper.find '.upload-btn'
          $btnMask = $btnWrapper.find '.issue-mask'
          $maskWrapper = $btnWrapper.find '.issue-mask-wrap'
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

          _checkFileFormat = (attachmentInfo) ->
            imgTypes = ['png', 'jpg', 'pjpeg', 'jpeg', 'x-png', 'gif']

            if ($.inArray(attachmentInfo.type, imgTypes) > -1)
              attachmentInfo.format = 'Img'
            else if (attachmentInfo.type is 'rar')
              attachmentInfo.format = 'rar'
            else if (attachmentInfo.type is 'doc')
              attachmentInfo.format = 'doc'
            else if (attachmentInfo.type is 'xls')
              attachmentInfo.format = 'excel'
            else if (attachmentInfo.type is 'psd')
              attachmentInfo.format = 'psd'
            else
              attachmentInfo.format = 'others'

          _IEHandler = ->
            browser = judgeDeviceService.judgeBrowser()
            if browser isnt "other"
              $btnMask.unbind 'click'
              return
          _IEHandler()

          # Upload the file to qiniu server
          scope.onFileSelect = (files) ->
            if files.length isnt 0
              for file in files
                if file.name.split('.').length is 1
                  notificationService.error 'issue_file_name_error', false
                  return

              $progress.width $btnWrapper.find('.upload-btn').width()
              if scope.processBar is 'true'
                $progress.show()
                $shadow.show()

              uploadService.qiniuUpload(files).then ((urls) ->
                if scope.attachmentInfo
                  attachmentInfos = []
                  for file, index in files
                    attachmentInfo =
                      name: file.name.substring(0, file.name.lastIndexOf('.'))
                      url: urls[index]

                    typeSplit = file.name.split '.'
                    newType = typeSplit.slice(typeSplit.length - 1, typeSplit.length)
                    attachmentInfo.type = newType[0].toLowerCase()
                    _checkFileFormat attachmentInfo

                    if file.size < 1024
                      attachmentInfo.size = file.size + 'B'
                    else if file.size > 1024 and file.size < Math.pow(1024, 2)
                      attachmentInfo.size = (file.size / Math.pow(10, 3)).toFixed(1) + 'KB'
                    else
                      attachmentInfo.size = (file.size / Math.pow(10, 6)).toFixed(1) + 'MB'

                    attachmentInfos.push attachmentInfo
                  scope.callback()(attachmentInfos)
                else
                  scope.ngModel = urls[0]
                $progress.hide()
                $shadow.hide()
                return
              ), null, (progress) ->
                # Can not get the value 'progress'.
                $progressBar.width(progress + '%')
                return
              $btnMask[0].value = null
              return
          return
      )
  ]

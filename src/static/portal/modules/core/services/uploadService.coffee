define [
  'core/coreModule'
], (mod) ->
  mod.factory 'uploadService', [
    '$upload'
    '$q'
    'restService'
    'notificationService'
    ($upload, $q, restService, notificationService) ->
      #Generate UUID partial
      S4 = ->
        (((1 + Math.random()) * 0x10000) | 0).toString(16).substring 1
      #Generate UUID
      guid = ->
        S4() + S4() + S4() + S4() + S4() + S4()
      #Save uploaded pic urls for user reusing
      saveToDB = (url) ->
        data =
          url: url

        restService.post '/api/common/file', data
        , (data) ->
        return

      updateFiles = (outerDeferred, files, uploadDomain, domain, token) ->
        promises = []
        for file in files
          ( ->
            deferred = $q.defer()
            # Generate unique file name using guid
            fileName = guid() + file.name.slice(file.name.lastIndexOf('.'))
            # Upload the file to qiniu server directly
            $upload.upload(
              url: uploadDomain
              headers:
                'Content-Type': 'multipart/form-data'
              data:
                key: fileName
                token: token
              method: "POST"
              file: file
            ).progress((evt) ->
              deferred.notify parseInt(100.0 * evt.loaded / evt.total)
              return
            ).success((data, status, headers, config) ->
              deferred.resolve(domain + '/' + fileName)
              return
            ).error ->
              deferred.reject("Fail to upload #{fileName} to qiniu")
              return
            # Collect promises
            promises.push(deferred.promise)
          )()
        # Wait for all the promises are resolved
        $q.all(promises).then((urls) ->
          outerDeferred.resolve urls
        , (rejects) ->
          outerDeferred.reject(rejects)
        , (notifiation) ->
          outerDeferred.notify(notifiation)
        )

      upload = {}
      upload.saveUrl = (url) ->
        saveToDB url
        return

      upload.qiniuUpload = (files) ->
        # Support thenable
        deferred = $q.defer()
        # In case the empty string
        return deferred.promise if not files
        # Construct an file array
        files = [files] if not angular.isArray(files)
        # In case the empty array
        return deferred.promise if not files.length
        # Generate the qiniu upload token
        restService.noLoading().get '/api/qiniu-token/generate', (data) ->
          token = data.token
          domain = data.domain
          uploadDomain = data.uploadDomain
          if token and domain and uploadDomain
            updateFiles(deferred, files, uploadDomain, domain, token)
          return
        return deferred.promise

      upload
  ]
  return

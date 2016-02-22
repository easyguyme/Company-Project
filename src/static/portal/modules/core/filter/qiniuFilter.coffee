define [
  'core/coreModule'
], (mod) ->
  mod.filter 'qiniu', ->
    (src, config) ->
      targetSrc = src

      isQiniuDomain = (src) ->
        isContain = false
        qiniuDomainSuffixs = ['glb.clouddn.com', 'qbox.me', 'qiniudn.com']
        for domain in qiniuDomainSuffixs
          if src.search(domain) > 0
            isContain = true
        isContain

      # Only add query parameter for images stored on qiniu server
      if src and isQiniuDomain(src)
        # Default image view mode
        mode = 1
        if typeof config is 'string'
          [width, height, mode] = config.split ','
        else
          # Default image size
          size = 30
          size = config if !isNaN config
          width = height = size
        targetSrc = src + "?imageView/#{mode}/w/#{width}/h/#{height}"
      targetSrc
  return

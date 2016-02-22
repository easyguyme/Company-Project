define [
  'core/coreModule'
  'wm/config'
], (mod, config) ->
  mod.factory 'channelService', [
    '$rootScope'
    'restService'
    '$q'
    ($rootScope, restService, $q) ->
      rvm = $rootScope
      channel = {}
      # Harry suggestion here
      channelPath = '/channel/follower/'

      channel.getChannels = ->
        defered = $q.defer()
        if not rvm.channels or not rvm.channels.length
          restService.get config.resources.channels, (data) ->
            channels = []
            # Add channel account
            for type, accounts of data
              for account in accounts
                title = if type is 'wechat' then account.accountType else account.channel
                channels.push(
                  name: account.name
                  id: account.id
                  appId: account.appId
                  type: type
                  title: title.toLowerCase()
                  link: channelPath + account.id
                  avatar: account.headImageUrl or config.defaultAvatar
                  channel: account.channel
                  accessStatus: if account.accessStatus then account.accessStatus else ''
                )
            rvm.channels = channels
            defered.resolve(channels)
            return
        else
          defered.resolve(rvm.channels)
        defered.promise

      channel
  ]

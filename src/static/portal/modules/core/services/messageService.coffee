define [
  'core/coreModule'
  'wm/config'
], (mod, config) ->
  mod.factory 'messageService', [
    'userService'
    '$q'
    (userService, $q) ->
      ser = {}

      ser.log = ->
        console.log.apply console, arguments if console

      ser.init = ->
        user = userService.getInfo()
        self = this
        if user and user.id
          options =
            authEndpoint: config.resources.pushAuth
            authData: "u:#{user.id}"
          @engine = new Engine config.push.appId, options
          @channelName = config.push.channel + user.accountId
          @channel = @engine.subscribe(@channelName)
          @channel.bind(config.push.event.subscribeSuccess, (users) ->
            self.log "#{user.id} subscribed channel " + self.channelName
            self.defered.resolve(users) if self.defered
          )
          @channel.bind(config.push.event.subscribeFail, (err) ->
            console.error(err) if console
          )
        return

      ser.bind = (eventName, cb) ->
        if @channel and eventName
          @channel.bind eventName, cb if typeof cb is 'function'
        else
          self = this
          @defered = $q.defer()
          @defered.promise.then(->
            self.channel.bind eventName, cb if typeof cb is 'function'
          )

      ser.destory = ->
        if @engine
          user = userService.getInfo()
          if user and user.accountId
            @engine.unsubscribe(config.push.channel + user.accountId)
            @log "#{user.id} unsubscribed channel #{@channelName}"

      ser
  ]

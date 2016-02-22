define [
  'wm/app'
  'wm/config'
], (app, config) ->
  # in order to highlight webpage, in fact is article edit controller
  app.registerController 'wm.ctrl.microsite.article.edit.webpage', [
    '$stateParams'
    'restService'
    'notificationService'
    '$location'
    '$timeout'
    '$window'
    ($stateParams, restService, notificationService, $location, $timeout, $window) ->
      vm = this

      _init = ->
        vm.articleTitle = if $stateParams.id then 'content_articles_update' else 'content_articles_create'
        vm.breadcrumb = [
          icon: 'webpage'
          text: 'content_articles_management'
          href: '/microsite/webpage?active=1'
        ,
          vm.articleTitle
        ]

        vm.ueditorConfig =
          initialStyle: 'p{word-wrap: break-word;word-break: break-word;}'

        vm.article =
          name: ''
          picUrl: ''
          content: ''
          fields: []
        vm.articleId = $stateParams.id
        vm.article.channel = $location.search().channel if $location.search()?.channel?
        if vm.article.channel?
          restService.get config.resources.articleChannel + '/' + vm.article.channel, (data) ->
            if data and data.fields?
              vm.articleFields = angular.copy data.fields
              angular.forEach vm.articleFields, (field) ->
                field.url = "/build/modules/core/partials/properties/" + field.type + ".html"

            vm.article.fields = angular.copy vm.articleFields
            if vm.articleId?
              restService.get config.resources.article + '/' + vm.articleId, (article) ->
                vm.article = angular.copy article
                angular.forEach vm.articleFields, (field) ->
                  angular.forEach vm.article.fields, (item) ->
                    field.value = item.content if field.id is item.id and item.content?
                vm.article.fields = angular.copy vm.articleFields

      vm.submit = ->
        if not vm.article.name or not vm.article.content
          return
        article = null
        if vm.articleId?
          article =
            name: vm.article.name
            content: vm.article.content
            fields: vm.article.fields
          article.picUrl = vm.article.picUrl if vm.article.picUrl
        else
          article = angular.copy vm.article

        if article.fields.length isnt 0
          fields = []
          angular.forEach article.fields, (field) ->
            if field.value
              fields.push {
                id: field.id
                name: field.name
                type: field.type
                content: field.value
              }
            article.fields = []
            article.fields = angular.copy fields if fields.length isnt 0

        # Create Article
        method = 'post'
        url = config.resources.articles
        # Update Article
        if vm.articleId
          method = 'put'
          url = config.resources.article + '/' + vm.articleId

        restService[method] url, article, (data) ->
          if method is "post"
            notificationService.success 'content_article_create_success'
          else
            notificationService.success 'content_article_update_success'
          $timeout ( ->
            $window.location.href = "/microsite/webpage?channel=" + vm.article.channel + "&active=1"
          ), 500
          return

      vm.cancel = ->
        $window.location.href = "/microsite/webpage?channel=" + vm.article.channel + "&active=1"

      _init()

      vm
  ]

define [
  'core/coreModule'
  'wm/config'
  ], (mod, config) ->
  mod.directive 'wmLabel', [
    'restService'
    '$modal'
    '$q'
    'utilService'
    (restService, $modal, $q, utilService) ->
      return (
        restrict: 'EA'
        scope:
          checkedItemStore: '='
          selectedAccount: '='
          module: '@'
          onChange: '&'
          type: '@'
          boundTags: '='
          onClose: '&'
        template: '<div class="tag-modal">
                    <h3 class="tag-title" translate="select_tags"></h3>
                    <span class="core-label-icon" ng-click="setModel()"></span>
                    <div class="tag-content">
                      <div class="label-form-wrapper">
                        <form name="addTagForm">
                          <div class="form-group clearfix">
                            <div class="label-form">
                              <div class="col-md-10 clearfix">
                                <input type="text" class="form-control label-name-text tag-name-text" placeholder="{{\'core_add_label_input\' | translate}}"
                                  maxlength="5" ng-model="newTag" required without-star below-msg wm-validate="checkExistTag"/>
                                <span class="form-tip normal"></span>
                              </div>
                              <div type="button" class="label-add" ng-click="createTag()" translate="core_add_label"></div>
                            </div>
                          </div>
                        </form>
                      </div>
                      <div class="label-line"></div>
                      <ul class="row label-name-pannel tags-wrapper" ng-show="tags.length > 0">
                        <li class="col-md-4 tag-name-wrapper text-el tags-item" ng-repeat="tag in tags track by $index">
                          <wm-checkbox ng-model="tag.check"></wm-checkbox>
                          <span class="tag-name" wm-tooltip="{{tag.name}}">{{tag.name}}</span>
                        </li>
                      </ul>

                      <ul class="row label-name-pannel tags-wrapper" ng-show="tags.length == 0">
                        <li class="col-md-12 tag-name-wrapper core-no-label">
                          <span class="tag-name" translate="core_no_label"></span>
                        </li>
                      </ul>

                      <div class="confirm-select">
                        <span class="btn btn-success btn-operate-tag btn-tag-ok" translate="ok" ng-click="saveBindTag()"></span>
                        <span class="btn btn-operate-tag btn-default" translate="cancel" ng-click="cancelBindTag()"></span>
                      </div>
                    </div>
                  </div>
                  </div>'
        link: (scope, element, attrs) ->
          getAllTags = ->
            deferred = $q.defer()
            restService.get config.resources.tags, (data) ->
              if data and data.items
                deferred.resolve data.items
            deferred.promise

          checkTargetBoundTags = (boundTags) ->
            if boundTags and scope.tags
              angular.forEach scope.tags, (tag) ->
                tag.check = $.inArray(tag.name, boundTags) isnt -1

          init = ->
            getAllTags().then (data) ->
              scope.tags =  angular.copy data or []

              if scope.type and scope.type is 'single'
                scope.boundTags = scope.boundTags or []
                checkTargetBoundTags scope.boundTags

          scope.checkExistTag = (name) ->
            formTip = ''
            scope.tags = [] if not scope.tags?
            if name
              for tag in scope.tags
                if tag.name is name
                  formTip = 'customer_exist_tag'
                  break
              if name.length > 5
                formTip = 'customer_tag_character_tip'
            else
              formTip = 'required_field_tip'
            return formTip

          addTag = ->
            deferred = $q.defer()
            params =
              tags: [scope.newTag]
            restService.post config.resources.tags, params, (data) ->
              scope.tags.push
                name: scope.newTag
                check: false
              deferred.resolve data
            deferred.promise

          # remove all tags which the follower has bound
          clearFollowerBoundTags = ->
            deferred = $q.defer()

            if scope.type and scope.type is 'single'
              followers = [scope.checkedItemStore[0]]
              removeTags = []
              if scope.boundTags?.length > 0
                angular.forEach scope.boundTags, (item) ->
                  removeTags.push item if utilService.getArrayElemIndex(scope.tags, item, 'name') isnt -1

              if removeTags.length
                deleteParams =
                  channelId: scope.selectedAccount
                  tags: removeTags
                  followers: followers
                restService.post config.resources.removeTags, deleteParams, (data) ->
                  deferred.resolve()
              else
                deferred.resolve()
            else
              deferred.resolve()
            deferred.promise

          scope.afterBindTags = (tags) ->
            scope.onClose()('bind', scope.type, tags) if scope.onClose()

          # call backend api to bind tags for follower.
          bindFollowerTags = (account, tags, followers) ->
            deferred = $q.defer()
            if tags and tags.length > 0
              params =
                channelId: account
                tags: tags
                followers: followers
              restService.post config.resources.addTags, params, (data) ->
                deferred.resolve()
            else
              deferred.resolve()
            deferred.promise

          # call backend api to bind tags for member.
          bindMemberTags = (memberIds, tags) ->
            deferred = $q.defer()
            if scope.type and scope.type is 'single'
              params =
                memberId: memberIds[0]
                tags: tags
              restService.post config.resources.memberTags, params, (data) ->
                deferred.resolve()
            else
              params =
                memberIds: memberIds
                tags: tags
              restService.post config.resources.membersTags, params, (data) ->
                deferred.resolve()
            deferred.promise

          # Add label.
          scope.createTag = ->
            if not scope.checkExistTag scope.newTag
              addTag().then ->
                scope.newTag = ''
                scope.onChange() if scope.onChange
            return

          # Label management.
          scope.setModel = ->
            modalInstance = $modal.open(
              templateUrl: '/build/modules/core/partials/settag.html'
              controller: 'wm.ctrl.core.settag'
              windowClass: 'setting-dialog'
              resolve:
                modalData: ->
                  scope.tags or []
            ).result.then( (data) ->
              getAllTags().then (tags) ->
                if tags
                  scope.tags = angular.copy tags
                  scope.onChange() if scope.onChange
            , (data) ->
              getAllTags().then (tags) ->
                if tags
                  scope.tags = angular.copy tags
                  scope.onChange() if scope.onChange
            )

          scope.saveBindTag = ->
            # get checked tag names
            tags = []
            for tag in scope.tags
              tags.push tag.name if tag.check

            # bind tags
            if scope.checkedItemStore.length > 0
              if scope.module is 'follower'
                clearFollowerBoundTags().then ->
                  bindFollowerTags(scope.selectedAccount, tags, scope.checkedItemStore)
                .then ->
                  scope.afterBindTags(tags)
              else if scope.module is 'member'
                bindMemberTags(scope.checkedItemStore, tags).then ->
                  scope.afterBindTags(tags)
            return

          scope.cancelBindTag = ->
            scope.onClose()() if scope.onClose()

          init()
      )
  ]

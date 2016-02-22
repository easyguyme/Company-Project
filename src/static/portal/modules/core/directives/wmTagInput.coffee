define [
  'core/coreModule'
  ], (mod) ->
  mod.directive 'wmTagInput', [
    ->
      return (
        restrict: 'A'
        replace: true
        scope:
          placeHolder: '@'
          maxLength: '@'
          tags: '=ngModel'
          showError: '@'
        template: '<div class="input-tag">
                            <div class="input-tag-wrap" ng-click="showInput($event)">
                              <span ng-if="tags.length == 0 && inputVal.length == 0 && placeHolder" class="input-tag-placeholder text-el">{{placeHolder | translate}}</span>
                              <span ng-repeat="tag in tags track by $index" class="input-tag-item">
                                {{tag}}
                                <i class="item-delete" ng-click="deleteTag($index)"></i>
                              </span>
                              <input type="text" maxlength="{{maxLength}}" class="input-tag-box" ng-model="inputVal" onkeydown="if(event.keyCode==13){return false;}" ng-keyup="addTag($event)"/>
                          </div>
                          <span class="input-tag-error-tip" translate="required_field_tip"></span>
                          </div>'
        link: (scope, elem, attrs) ->
          scope.inputVal = ''
          scope.tags = [] if not scope.tags
          scope.maxLength = if not scope.maxLength then 50 else parseInt scope.maxLength

          scope.showInput = (event) ->
            _hideError()
            elem.find('.input-tag-box').focus()
            event.stopPropagation()
            event.preventDefault()
            return

          scope.addTag = (event) ->
            text = scope.inputVal.trim()
            if text.length > 0
              if event.which is 13 and scope.tags.indexOf(text) < 0
                scope.tags.push text
                scope.inputVal = ''
            else if event.which is 8 and scope.tags.length > 0 and elem.find('.input-tag-box').val().length is 0
              scope.tags.splice scope.tags.length - 1, 1

          scope.deleteTag = (index) ->
            scope.tags.splice(index, 1) if scope.tags.length > 0
            return

          _showError = ->
              elem.find('.input-tag-wrap').addClass 'form-control-error'
              elem.find('.input-tag-error-tip').show()
              return

          _hideError = ->
            elem.find('.input-tag-wrap').removeClass 'form-control-error'
            elem.find('.input-tag-error-tip').hide()
            return

          scope.$watch 'showError', (val) ->
            if val is 'true' and scope.tags.length is 0
              _showError()
            else if val is 'false' and elem.find('.input-tag-wrap').hasClass 'form-control-error'
              _hideError()

      )
  ]

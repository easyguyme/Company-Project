define ['core/coreModule'], (mod) ->
  mod.directive 'wmCopyInput', [
    ->
      return (
        restrict: 'EA'
        replace: true
        scope:
          text: '@'
          tooltip: '@'
        template: '<div class="copy-text-wapper">
                    <input id="copyText" class="form-control" ng-model="text" readonly/><i wm-copy class="icon-copy"
                      clipboard-text="text" tip="{{tooltip || \'content_article_copy\' | translate}}" tooltip-max-width="160"></i>
                  </div>'
        link: (scope, elem, attrs) ->
          $input = $(elem).find('input')
          $input.click ->
            $input.select()
      )
  ]

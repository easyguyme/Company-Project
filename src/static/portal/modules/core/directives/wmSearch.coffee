define ["core/coreModule"], (mod) ->
  mod.directive "wmSearch", [ ->
    return (
      restrict: "E"
      replace: true
      scope:
        model: "=ngModel"
        placeholder: "@placeholder"
        clickFunc: "&"
      template: '<div class="rel search-wrapper">\
                  <div class="pull-left search-input-container">\
                    <input type="search" class="form-control" placeholder="{{placeholder}}" maxlength="30" ng-model="model" wm-enter="clickFunc()">\
                  </div>\
                  <div class="pull-left search-icon-container clearfix cp" ng-click="clickFunc()">\
                    <div class="glyphicon glyphicon-search search-icon"></div>\
                  </div>\
                </div>'
      )
  ]

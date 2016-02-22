define ['core/coreModule'], (mod) ->
  mod.directive 'wmPictureShow', [
    ->
      return (
        replace: true
        restrict: 'EA'
        scope:
          pictures: '='
          index: '@'
        template: '<section class="pictures-wrapper clearfix">
                    <div class="picture-display-wrapper pull-left" ng-if="pictures.length > 0" ng-class="{\'max-picture-display-wrapper\':pictures.length == 5}">
                      <img class="picture-display-source absolute-center" ng-class="{\'max-picture-display-source\':pictures.length == 5}" ng-src="{{pictures[index]}}">
                    </div>
                    <div class="picture-items-wrapper pull-left" ng-if="pictures.length > 0">
                      <ul class="picture-items">
                        <li class="picture-box select-picture-item cp" ng-class="{\'picture-item-checked\': $index == index}"
                          ng-repeat="item in pictures track by $index"
                          ng-click=changePicture($index)>
                          <img wm-center-img ng-src="{{item}}">
                        </li>
                      </ul>
                    </div>
                    <div class="" ng-if="pictures.length == 0" translate="-"></div>
                  </section>'
        link: (scope, elem, attr) ->
          scope.changePicture = (inx) ->
            scope.index = inx

          scope.$watch 'pictures', (newVals, oldVals) ->
            if angular.isArray(newVals) and angular.isArray(oldVals)
              checkedPic = oldVals[scope.index] if oldVals.length > scope.index
              nowIdx = $.inArray(checkedPic, newVals)
              if nowIdx isnt -1
                scope.index = nowIdx
              else
                scope.index = 0
          , true
      )
  ]

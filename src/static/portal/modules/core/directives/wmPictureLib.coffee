define ['core/coreModule'], (mod) ->

  mod.directive 'wmPictureLib', [
    'notificationService'
    (notificationService) ->
      return (
        restrict: 'EA'
        scope:
          pictures: '='
          isShow: '='
          index: '='
        template: '<div>
                    <div class="modal-backdrop fade in" style="z-index:1040"></div>
                    <div class="show-pic-wrapper" ng-click="hideModal()">
                      <div class="page-wrapper stopProp" ng-click="prePage()">
                        <img src="/images/core/prepage_default.png">
                      </div>
                      <div class="big-pic-wrapper stopProp">
                        <img class="absolute-center" ng-src="{{pictures[index].url}}">
                      </div>
                      <div class="thumb-pic-wrapper stopProp">
                        <span class="pic-close-btn cp" ng-click="hideModal()"></span>
                        <div class="pic-name text-el">{{pictures[index].name}}</div>
                        <div class="pic-operation-wrapper clearfix">
                          <span class="pic-size">{{pictures[index].size}}MB</span>
                          <span class="pic-delete-icon cp" ng-click="deletePic(index, $event)"></span>
                        </div>
                        <ul class="col-md-12 col-xs-12 pd0">
                          <li class="col-md-6 col-xs-12 pl0 mb10" ng-repeat="picture in pictures track by $index">
                            <div class="thumb-box-border" ng-class="{\'pic-select-border\':index==$index}">
                               <div ng-style="{\'background-image\':\'url(\'+picture.url+\')\'}" class="thumb-box cp" ng-click="selectPic($index)">
                              </div>
                            </div>
                          </li>
                        </ul>
                      </div>
                      <div class="page-wrapper stopProp" ng-click="nextPage()">
                        <img src="/images/core/nextpage_default.png">
                      </div>
                    </div>
                  </div>'

        link: (scope, elem, attrs) ->

          picLen = scope.pictures.length
          scope.hideModal = ->
            scope.isShow = false

          scope.deletePic = (index, $event) ->
            notificationService.confirm $event,{
              title: 'product_pic_delete'
              submitCallback: _deletePicHandler
              params: [index]
            }

          _deletePicHandler = (index) ->
            scope.$apply ->
              scope.pictures.splice(index, 1)
              if scope.pictures.length > 0
                if index is 0
                  scope.index = index
                else
                  scope.index = index - 1
              else
                scope.isShow = false

          scope.prePage = ->
            if scope.index - 1 < 0
              scope.index = picLen - 1
            else
              scope.index--

          scope.nextPage = ->
            if scope.index + 1 is picLen
              scope.index = 0
            else
              scope.index++

          scope.selectPic = (index) ->
            scope.index = index

          $('.stopProp').on('click', (event)->
            event.stopPropagation()
          )

      )
  ]

define ['core/coreModule'], (mod) ->

  mod.directive('wmPagination', [
      '$translate'
      '$location'
      ($translate, $location) ->
        return (
          replace: true
          restrict: 'EA'
          scope:
            extraSize: '@'
            currentPage: '='
            pageSize: '='
            totalItems: '='
            hideNodata: '='
            hidePageSize: '@'
            onChangeSize: '&'
            onChangePage: '&'

          template: '<div>
                    <div class="wm-pagination" ng-show="totalItems > size">
                      <span translate="display" ng-hide="hidePageSize == \'true\'"></span>
                      <span class="pagesize" ng-hide="hidePageSize == \'true\'">
                        <div wm-select direction="up" on-change="changePageSize" ng-model="pageSize" items="numbers" text-field="text" value-field="value"></div>
                      </span>
                      <span translate="per-page" ng-hide="hidePageSize == \'true\'"></span>
                      <span class="pagination-wrap">
                        <div pagination ng-model="currentPage" total-items="totalItems" items-per-page="pageSize" boundary-links="true" num-pages="numPages"
                        first-text="{{\'first_page\'|translate}}" last-text="{{\'last_page\'|translate}}" previous-text="◂" next-text="▸" max-size="0"></div>
                      </span>
                    </div>
                    </div>'
          link: (scope) ->
            firstLoad = true

            scope.numbers = []

            #default page size config
            defaultNumbers = [10, 20, 50, 100]
            # add other page size config if you need
            # extraSize is a string like '5,40,70'
            if scope.extraSize
              extraSize = scope.extraSize.split ','
              extraSize = extraSize.map (item) ->
                return Number(item)
              defaultNumbers = defaultNumbers.concat extraSize
            # sort pagesize by asc
            defaultNumbers.sort (a, b) ->
              return if a > b then 1 else -1

            scope.size = defaultNumbers[0] or 10
            scope.numPages = Math.ceil(scope.totalItems / scope.size)
            for item in defaultNumbers
              scope.numbers.push {text: "#{item}", value: item}

            scope.changePageSize = (val) ->
              #_setQueryString('pageSize', val)
              scope.pageSize = val
              scope.numPages = Math.ceil(scope.totalItems / val)
              scope.onChangeSize()(val) if scope.onChangeSize()

            scope.$watch 'currentPage', (page) ->
              if page and scope.onChangePage()
                if not firstLoad
                  scope.onChangePage()(page)
                  #_setQueryString('currentPage', page)
                firstLoad = false

            _setQueryString = (flag, value) ->
              $location.search(flag, value)
              $location.search('currentPage', null) if flag is 'pageSize' or (flag is 'currentPage' and value is 1)
              return

      )
  ]).directive 'wmRestrictedPage', ->
    restrict: 'A'
    require: 'ngModel'
    link: (scope, elem, attr, ctrl) ->
      reg = /^[1-9]\d*$/

      lastPage = 1

      changePageHandler = scope.$parent.onChangePage() if scope.$parent.onChangePage()

      ctrl.$parsers.unshift (value) ->
        totalPages = parseInt(attr.totalPages) or 1

        if value and (not reg.test(value) or parseInt(value) > totalPages)
          _setPageValue(ctrl.$modelValue)
          return ctrl.$modelValue
        else
          return value

      elem.bind 'blur', (e) ->
        _handleJumpPage()


      elem.bind 'keyup', (e) ->
        if e.keyCode is 13
          _handleJumpPage()


      _setPageValue = (value) ->
        ctrl.$setViewValue(value)
        ctrl.$render()

      _handleJumpPage = ->
        lastPage = scope.$parent.currentPage
        currentPage = elem.val()

        if currentPage and parseInt(currentPage) isnt lastPage
          lastPage = parseInt(currentPage)
          changePageHandler.call null, lastPage if changePageHandler
        else
          _setPageValue lastPage

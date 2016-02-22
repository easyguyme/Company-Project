define ['core/coreModule'], (mod) ->
  mod.directive 'wmMicrositeLocation', [
    'restService'
    (restService) ->
      return (
        restrict: 'EA'
        scope:
          ngModel: '='
          changeHandler: '&'
          vertical: '@'
        template: '<div class="microsite-directive-location row">
                            <div ng-class="{false: \'col-md-12\', true: \'col-md-4\'}[vertical == \'true\']">
                              <div wm-select on-change="changeProvince" ng-model="province" text-field="text" value-field="value" items="provinces" default-text="province"></div>
                            </div>
                            <div ng-class="{false: \'col-md-12\', true: \'col-md-4\'}[vertical == \'true\']">
                              <div wm-select on-change="changeCity" ng-model="city" text-field="text" value-field="value" items="cities" default-text="city"></div>
                            </div>
                            <div ng-class="{false: \'col-md-12\', true: \'col-md-4\'}[vertical == \'true\']">
                              <div wm-select on-change="changeCounty" ng-model="county" text-field="text" value-field="value" items="counties" default-text="county"></div>
                            </div>
                          </div>'
        link: (scope, elem, attrs) ->
          info = undefined
          restService.get '/build/modules/core/meta/three-location.json', (data) ->
            info = angular.copy data
            _fetch(scope.ngModel)
            return
          # Generate the model for external access
          generateModel = (scope) ->
            province = if scope.province then scope.province else ''
            city = if scope.city then scope.city else ''
            county = if scope.county then scope.county else ''
            scope.ngModel =
              province: province
              city: city
              county: county

          # rewrite the fetchProvinces and fetchCities function, because the two data are different formats
          # fetch provinces with the local data
          fetchProvinces = (callback) ->
            provinces = []
            angular.forEach info, (province) ->
              provinces.push
                text: province.name
                value: province.name
              return
            scope.provinces = provinces
            callback() if callback

          # fetch cityes with the local data
          fetchCities = (provinceVal, callback) ->
            cities = []
            angular.forEach info, (province) ->
              if province.name is provinceVal
                children = province.c
                angular.forEach children, (city) ->
                  cities.push
                    text: city.name
                    value: city.name
                  return
              return
            scope.cities = cities
            callback() if callback

          # fetch counties with the local data
          fetchCounties = (provinceVal, cityVal, callback) ->
            counties = []
            angular.forEach info, (province) ->
              if province.name is provinceVal
                children = province.c
                angular.forEach children, (city) ->
                  if city.name is cityVal
                    children = city.c
                    angular.forEach children, (county) ->
                      counties.push
                        text: county.name
                        value: county.name
                      return
                  return
                return
              return
            scope.counties = counties
            callback() if callback

          scope.changeProvince = (val) ->
            fetchCities val, ->
              scope.county = ''
              scope.city = ''
              scope.province = val
              generateModel scope
            scope.changeHandler() if scope.changeHandler
            return

          scope.changeCity = (val) ->
            fetchCounties scope.province, val, ->
              scope.county = ''
              scope.city = val
              generateModel scope
            scope.changeHandler() if scope.changeHandler
            return

          scope.changeCounty = (val) ->
            scope.county = val
            generateModel scope
            scope.changeHandler() if scope.changeHandler
            return

          _fetch = (location) ->
            fetchProvinces()
            if location
              scope.province = location.province
              if scope.province
                fetchCities scope.province, ->
                  scope.city = location.city
                  if scope.city
                    fetchCounties scope.province, scope.city, ->
                      scope.county = location.county
                  else
                    scope.county = ''
                    scope.counties = []
              else
                scope.city = ''
                scope.county = ''
                scope.cities = []
                scope.counties = []

          scope.$watch 'ngModel', (location) ->
            _fetch(location) if info and info.length > 0
      )
  ]

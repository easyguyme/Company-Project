define ['core/coreModule'], (mod) ->
  mod.directive 'wmLocation', [
    'restService'
    '$q'
    (restService, $q) ->
      return (
        restrict: 'EA'
        scope:
          ngModel: '='
          channelId: '@'
        template: '<div class="row">
                    <div class="col-md-4">
                      <div wm-select on-change="changeCountry" ng-model="country" text-field="text"
                      value-field="value" items="countries" default-text="country"></div>
                    </div>
                    <div class="col-md-4">
                      <div wm-select on-change="changeProvince" ng-model="province" text-field="text" value-field="value" items="provinces" default-text="province"></div>
                    </div>
                    <div class="col-md-4">
                      <div wm-select on-change="changeCity" ng-model="city" text-field="text" value-field="value" items="cities" default-text="city"></div>
                    </div>
                  </div>'
        link: (scope, element, attrs) ->
          fetchList = (locationProperty, parentProvince, parentCountry) ->
            if attrs.hasOwnProperty('member')
              url = '/api/member/statisticss'
            else
              url = '/api/common/property/location'
            defered = $q.defer()
            data =
              locationProperty: locationProperty
              channelId: scope.channelId
            data.parentProvince = parentProvince if parentProvince
            data.parentCountry = parentCountry if parentCountry

            restService.get url, data, (data) ->
              defered.resolve data.items
            defered.promise

          fetchCountries = (callback) ->
            fetchList('country', '', '').then (countryMap) ->
              countries = []
              defCountries = [
                'channel_wechat_mass_unlimited'
              ]
              defCountries = [] if not defCountries?
              countryMap = [] if not countryMap?
              for country in defCountries
                countries.push
                  text: country
                  value: country
              for country in countryMap
                countries.push
                  text: country['value']
                  value: country['value']
              scope.countries = countries
              callback() if callback

          if attrs.hasOwnProperty('static')
            defCountries = [
              'UNKNOWN'
              '中国'
            ]
            countries = []
            for country in defCountries
              countries.push
                text: country
                value: country
            scope.countries = countries
          else
            scope.$watch 'channelId', (val) ->
              if val or attrs.hasOwnProperty('member')
                fetchCountries ->
                  if scope.ngModel?.country
                    scope.country = scope.ngModel.country

          # If element has static attribute, it means it will use local data
          if attrs.hasOwnProperty('static')
            info = []
            restService.get '/build/modules/core/meta/sub-location.json', (data) ->
              info = angular.copy data

            # rewrite the fetchProvinces and fetchCities function, because the two data are different formats
            # fetch provinces with the local data
            fetchProvinces = (val, callback) ->
              provinces = []
              angular.forEach info, (province) ->
                provinces.push
                  text: province.name
                  value: province.name
                return
              scope.provinces = provinces
              callback() if callback

            # fetch cityes with the local data
            fetchCities = (val, country, callback) ->
              cities = []
              angular.forEach info, (province) ->
                if province.name is val
                  children = province.c
                  angular.forEach children, (city) ->
                    cities.push
                      text: city.name
                      value: city.name
                    return
                return
              scope.cities = cities
              callback() if callback
          else
            #Mock data get from wechat API
            fetchProvinces = (country, callback) ->
              fetchList('province', '', country).then (provinceMap) ->
                provinces = []
                for province in provinceMap
                  provinces.push
                    text: province['value']
                    value: province['value']
                scope.provinces = provinces
                callback() if callback

            fetchCities = (province, country, callback) ->
              fetchList('city', province, country).then (cityMap) ->
                cities = []
                for city in cityMap
                  cities.push
                    text: city['value']
                    value: city['value']
                scope.cities = cities
                callback() if callback

          # Generate the model for external access
          generateModel = (scope) ->
            country = if scope.country then scope.country else ''
            province = if scope.province then scope.province else ''
            city = if scope.city then scope.city else ''
            scope.ngModel =
              country: country
              province: province
              city: city

          _clearLocation = ->
            scope.provinces = []
            scope.cities = []
            scope.province = ''
            scope.city = ''

          scope.changeCountry = (val) ->
            if $.inArray(val, ['channel_wechat_mass_unlimited', 'UNKNOWN']) >= 0
              _clearLocation()
              scope.ngModel =
                country: if val is 'channel_wechat_mass_unlimited' then '' else 'UNKNOWN'
                province: ''
                city: ''
            else
              scope.country = val
              fetchProvinces val, ->
                scope.province = scope.city = ''
                generateModel scope
            return

          scope.changeProvince = (val) ->
            fetchCities val, scope.country, ->
              scope.city = ''
              scope.province = val
              generateModel scope
            return

          scope.changeCity = (val) ->
            scope.city = val
            generateModel scope

          scope.$watch 'ngModel', (location) ->
            if location and not $.isEmptyObject(location)
              scope.country = location.country
              if not scope.country?
                _clearLocation()
              else if scope.country is '' or (scope.country isnt '中国' and attrs.hasOwnProperty('static'))
                scope.country = if attrs.hasOwnProperty('static') then 'UNKNOWN' else 'channel_wechat_mass_unlimited'
                _clearLocation()
              else
                if location.country
                  fetchProvinces location.country, ->
                    scope.province = location.province
                    if not location.country or $.inArray(location.country, ['channel_wechat_mass_unlimited', 'UNKNOWN']) >= 0
                      scope.provinces = []

                if location.province and location.country
                  fetchCities location.province, location.country, ->
                    scope.city = location.city
                    if not location.province
                      scope.cities = []
            else
              scope.country = ''
              scope.province = ''
              scope.city = ''
              scope.provinces = []
              scope.cities = []

          return
      )
  ]

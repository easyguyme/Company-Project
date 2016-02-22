define ['core/coreModule'], (mod) ->
  mod.directive 'wmStore', [
    '$rootScope'
    'restService'
    '$q'
    ($rootScope, restService, $q) ->
      return (
        restrict: "A"
        scope:
          ngModel: '='
          channelId: '@'
          onChange: '&'
        template: '<div class="row">
                    <div class="col-md-3" style="padding-right: 0px">
                      <div class="store-select" wm-select on-change="changeProvince" ng-model="province" wm-tooltip="{{province | translate}}" text-field="text"
                      value-field="value" items="provinces" default-text="province"></div>
                    </div>
                    <div class="col-md-3" style="padding-right: 0;padding-left: 2px">
                      <div class="store-select" wm-select on-change="changeCity" ng-model="city" text-field="text" value-field="value" items="cities" default-text="cities"></div>
                    </div>
                    <div class="col-md-3" style="padding-right: 0;padding-left: 2px">
                      <div class="store-select" wm-select on-change="changeRegion" ng-model="region" text-field="text" value-field="value" items="regions" default-text="region"></div>
                    </div>
                    <div class="col-md-3" style="padding-right: 0;padding-left: 2px">
                      <div class="store-select" wm-select on-change="changeStore" ng-model="store" text-field="text" value-field="value" items="stores" default-text="store">
                      </div>
                    </div>
                  </div>'
        link: (scope, element, attrs) ->

          fetchList = (name) ->
            defered = $q.defer()
            url = '/api/channel/offlinestore/store/location'
            param =
              name: name
            if not name
              restService.get url, (data) ->
                defered.resolve data
              defered.promise
            else
              restService.get url, param, (data) ->
                defered.resolve data
              defered.promise

          fetchProvinces = (province, callback) ->
            fetchList(province).then (provinceList) ->
              provinces = []

              for provinceItem,index in provinceList
                provinces.push
                  text: provinceItem.name
                  value: provinceItem.name
              scope.provinces = provinces
              #scope.province = if provinces[0] then provinces[0].value else ''
              callback() if callback

          fetchCities = (province, callback) ->
            fetchList(province).then (cityList) ->
              cities = []

              for cityItem in cityList
                cities.push
                  text: cityItem.name
                  value: cityItem.name
              scope.cities = cities
              #scope.city = if cities[0] then cities[0].value else ''
              callback() if callback

          fetchRegions = (city, callback) ->
            fetchList(city).then (regionList) ->
              regions = []

              for regionItem in regionList
                regions.push
                  text: regionItem.name
                  value: regionItem.name
              scope.regions = regions
              #scope.region = if regions[0] then regions[0].value else ''
              callback() if callback

          fetchStores = (region, callback) ->
            fetchList(region).then (storeList) ->
              stores = []

              for storeItem in storeList
                stores.push
                  text: storeItem.name
                  value: storeItem.id
              scope.stores = stores
              #scope.store = if stores[0] then stores[0].value else ''
              callback() if callback

          # clear store
          clearStore = ->
            scope.cities = []
            scope.city = ''
            scope.regions = []
            scope.region = ''
            scope.stores = []
            scpoe.store = ''

          # generate model for extral access
          generateModel = (scope) ->
            province = if scope.province then scope.province else ''
            city = if scope.city then scope.city else ''
            region = if scope.region then scope.region else ''
            store = if scope.store then scope.store else ''
            scope.ngModel =
              province: province
              city: city
              region: region
              store: store

          # Select province.
          scope.changeProvince = (val) ->
            if val is 'channel_wechat_mass_unlimited'
              clearStore()
              scope.ngModel =
                province: ''
                city: ''
                region: ''
                store: ''
            else
              scope.province = val
              fetchCities val, ->
                scope.city = scope.region = scope.store = ''
                scope.stores = []
                scope.regions = []
                generateModel scope
            return

          # Select city.
          scope.changeCity = (val) ->
            scope.city = val
            fetchRegions val, ->
              scope.region = scope.store = ''
              scope.stores = []
              generateModel scope
            return

          # Select region
          scope.changeRegion = (val) ->
            scope.region = val
            fetchStores val, ->
              scope.store = ''
              generateModel scope
            return

          # Select store.
          scope.changeStore = (val) ->
            scope.store = val
            if scope.onChange() and scope.province and scope.city and scope.region
              scope.onChange()(val)
            generateModel scope

          _init = ->
            scope.provinces = []
            scope.cities = []
            scope.regions = []
            scope.stores = []
            fetchProvinces '', ->
              scope.province = if scope.provinces[0] then scope.provinces[0].value else ''
              fetchCities scope.province, ->
                scope.city = if scope.cities[0] then scope.cities[0].value else ''
                fetchRegions scope.city, ->
                  scope.region = if scope.regions[0] then scope.regions[0].value else ''
                  fetchStores scope.region, ->
                    scope.store = if scope.stores[0] then scope.stores[0].value else ''
                    scope.changeStore scope.store
                    return
                  return
                return
              return

          _init()
      )
  ]

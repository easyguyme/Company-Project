define [
  'wm/app'
  'wm/config'
], (app, config) ->
  app.registerController 'wm.ctrl.management.view.store', [
    'restService'
    '$location'
    '$stateParams'
    '$filter'
    '$sce'
    (restService, $location, $stateParams, $filter, $sce) ->
      vm = this

      defaultStoreImage = '/images/content/default.png'

      _init = ->
        vm.storeId = $stateParams.id if $stateParams.id

        vm.store =
          name: '-'
          branchName: '-'
          type: '-'
          subtype: '-'
          telephone: '-'
          position:
            longitude: null
            latitude: null
          image: defaultStoreImage
          businessHours: '-'
          address: '-'
          location:
            province: ''
            city: ''
            county: ''
            detail: ''
          description: '-'
          positionIcon: ''

        vm.breadcrumb = [
          {
            text: 'store_management'
            href: '/management/store'
          }
          'management_view_store'
        ]

        _getStore()

        return

       _getStore = ->
        restService.get config.resources.store + '/view/' + $stateParams.id, (data) ->
          vm.store = angular.copy _formatStoreData data
        return

      _formatStoreData = (store)->
        store.location.province = store.location.province or ''
        if store.location.city and store.location.city isnt $filter('translate')('management_store_city')
          store.location.city = store.location.city
        else
          store.location.city = ''

        if store.location.district and store.location.district isnt $filter('translate')('management_store_county')
          store.location.county = store.location.district
        else
          store.location.county = ''

        store.location.detail = store.location.detail or ''

        store.address = (store.location.province + store.location.city + store.location.county + store.location.detail) or '-'
        store.branchName = store.branchName or '-'
        store.type = store.type or '-'
        store.subtype = store.subtype or '-'
        store.telephone = store.telephone or '-'
        store.businessHours = store.businessHours or '-'
        if store.description
          replacedDescription = store.description.replace /\n/g, '<br>'
          store.description = $sce.trustAsHtml(replacedDescription)
        else
          store.description = ''
        store.image = if store.image then store.image else defaultStoreImage

        if store.position
          store.positionIcon = "http://api.map.baidu.com/staticimage?center=#{store.position.longitude},#{store.position.latitude}&width=520&height=290&zoom=14&markers=#{store.position.longitude},#{store.position.latitude}&markerStyles=-1,http://api.map.baidu.com/images/marker_red.png,-1"

        return store

      _init()

      vm
  ]

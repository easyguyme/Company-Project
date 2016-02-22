define [
  'wm/app'
  'wm/config'
], (app, config) ->
  app.registerController 'wm.ctrl.uhkklp.newmsg', [
    'restService'
    '$location'
    '$stateParams'
    '$rootScope'
    'notificationService'
    '$http'
    '$filter'
    '$scope'
    'exportService'
    (restService, $location, $stateParams, $rootScope, notificationService, $http, $filter, $scope, exportService) ->
      vm = this
      $scope.breadcrumb = [
        'uhkklp_message'
      ]
      Date.prototype.format = (fmt) ->
        o =
          "M+": this.getMonth() + 1
          "d+": this.getDate()
          "h+": this.getHours()
          "m+": this.getMinutes()
          "s+": this.getSeconds()
          "q+": Math.floor (this.getMonth() + 3) / 3
          "S": this.getMilliseconds()
        if /(y+)/.test fmt
          fmt = fmt.replace RegExp.$1, (this.getFullYear() + "").substr 4 - RegExp.$1.length
        (
          fmt = fmt.replace RegExp.$1,  if RegExp.$1.length is 1 then v else ("00" + v).substr ("" + v).length
        )for k, v of o when (new RegExp "(" + k + ")").test fmt
        return fmt

      EDIT_URL = '/uhkklp/edit/newmsg'

      if $rootScope.uhkklp_newmsg_tip
        notificationService.success $rootScope.uhkklp_newmsg_tip, false
        delete $rootScope.uhkklp_newmsg_tip

      #tabs
      vm.tabs = [
        {
          name: 'mt_newmsgtb_push_list'
          value: ''
        }
      ]
      tabVal = $location.search().active

      #pagination
      vm.pageSize = 10
      vm.page = 1

      #table
      vm.list =
        columnDefs: [
          {
            field: '$id'
            label: 'mt_newmsgtb_ID'
            sortable: true
            desc: true
          }
          {
            field: 'content'
            label: 'mt_newmsgtb_content'
          }
          {
            field: 'linkType'
            label: 'mt_newmsgtb_linktype'
            sortable: true
            desc: true
          }
          {
            field: 'formattedPushTime'
            label: 'mt_newmsgtb_pushtime'
            sortable: true
            desc: true
          }
          {
            field: 'pushMethod'
            label: 'mt_newmsgtb_pushmethod'
            type: 'html'
            sortable: true
            desc: true
          }
          {
            field: 'operations'
            label: 'operations'
            type: 'operation'
          }
        ]
        data: []
        hasLoading: true

        editHandler: (item) ->
          $rootScope.uhkklp_newmsg_condition = {
            currentPage: vm.page
            pageSize: vm.pageSize
            sort: $scope.sort
          }
          $location.url EDIT_URL + '/' + vm.list.data[item]._id.$id
        deleteHandler: (item) ->
          url = '/api/uhkklp/message/delete'
          params = $.param
            $id: vm.list.data[item]._id.$id
          $http
            url: url
            method: 'POST'
            headers:
              'Content-Type': 'application/x-www-form-urlencoded'
            data: params
          .success (data) ->
            notificationService.success 'mt_fm_delete_succ', false
            _getList()
            return
          return
        sortHandler: (colDef) ->
          if colDef.field is '$id'
            $scope.sort = {'_id': colDef.desc}
          else if colDef.field is 'formattedPushTime'
            $scope.sort = {'pushTime': colDef.desc}
          else if colDef.field is 'linkType'
            $scope.sort = {'linkType': colDef.desc}
          else if colDef.field is 'pushMethod'
            $scope.sort = {'pushMethod': colDef.desc}
          _getList()
        exportHandler: (item) ->
          exportService.export 'mt_export_result', '/api/uhkklp/excel-conversion/export-push-result', {id: vm.list.data[item]._id.$id}, false
      # $scope.condition = []
      $scope.sort = {}

      _getList = ->

        if $rootScope.uhkklp_newmsg_condition
          vm.page = $rootScope.uhkklp_newmsg_condition.currentPage
          vm.pageSize = $rootScope.uhkklp_newmsg_condition.pageSize
          $scope.sort = $rootScope.uhkklp_newmsg_condition.sort

        url = '/api/uhkklp/message/get-list'
        params =
          currentPage: vm.page
          pageSize: vm.pageSize
          # condition: $scope.condition
          sort: $scope.sort
        $http
          url: url
          method: 'POST'
          headers:
            'Content-Type': 'application/json'
          data: JSON.stringify params
        .success (data) ->
          vm.totalCount = data.dataCount
          (
            data.messages[i].$id = message._id.$id

            date = new Date message.pushTime.sec * 1000
            data.messages[i].formattedPushTime = date.format 'yyyy-MM-dd hh:mm'

            if message.linkType is 'app'
              data.messages[i].linkType = $filter('translate')('mt_rd_link_app')
            if message.linkType is 'list'
              data.messages[i].linkType = $filter('translate')('mt_rd_link_list')
            if message.linkType is 'news'
              data.messages[i].linkType = $filter('translate')('mt_rd_link_news')

            if message.pushMethod is 'all'
              data.messages[i].pushMethod = $filter('translate')('mt_rd_pushall')
            if message.pushMethod is 'nameList'
              # data.messages[i].pushMethod = $filter('translate')('mt_rd_namelist')
              data.messages[i].pushMethod = '<a href="#" class="mt_msg_nl_lk">' + $filter('translate')('mt_rd_namelist') + '</a><br>' + '<div class="' + i + ' mt_msg_nl" style="display: none;"></div>'
            data.messages[i].operations = [
              {
                name: 'edit'
                title: "mt_edit"
              }
              {
                name: 'delete'
                title: 'mt_delete'
              }
              {
                name: 'export'
                title: 'mt_export'
                disable: true
              }
            ]
            $scope.listReady = true
            if message.pushTime.sec < (new Date().getTime()) / 1000
              data.messages[i].operations[0].disable = true
              data.messages[i].operations[1].disable = true
            if message.isPushed
              data.messages[i].operations[2].disable = false
          ) for message, i in data.messages

          vm.list.data = data.messages
          vm.list.hasLoading = false
          if $rootScope.uhkklp_newmsg_condition
            delete $rootScope.uhkklp_newmsg_condition
          return
        return

      _getList()

      $scope.$watch 'listReady', (value) ->
        $scope.listReady = false
        if not $('.mt_msg_nl_lk').length
          return
        # console.log  $('.mt_msg_nl')
        $('.mt_msg_nl_lk').click ->
          divObj = $ this.nextSibling.nextSibling
          divContent = ''
          (
            divContent += '<li>' + num + '</li>'
          ) for num, i in vm.list.data[divObj[0].classList[0]].pushDevices
          divContent = '<ul>' + divContent + '</ul>'
          divObj.html divContent
          if (divObj.css 'display') is 'none'
            divObj.css 'display', 'block'
          else
            divObj.css 'display', 'none'
          return
        return

      vm.changePage = (currentPage) ->
        vm.page = currentPage
        _getList()

      vm.changeSize = (pageSize) ->
        vm.pageSize = pageSize
        if not $rootScope.uhkklp_newmsg_condition
          vm.page = 1
        _getList()

      vm

  ]

  app.registerDirective "mttFormat", [
    'validateService'
    '$filter'
    (validateService, $filter) ->
      return {
        restrict: 'A'
        require: 'ngModel'
        scope:
          mttFormat: '@'
          formatType: '@'
        link: (scope, elem, attr, ngModel) ->
          reg = new RegExp scope.mttFormat, 'i'
          elem.on 'keyup blur focus', ->
            if ($.inArray 'ng-pristine', elem.context.classList) > 0
              return
            val = elem.context.value
            if reg.test val
              validateService.restore elem, ''
            else
              if not val
                tip = $filter('translate')('mt_ck_required')
              else
                if scope.formatType is 'url' and /^((http|https|ftp):\/\/)?(w{3}\.)?[\.\w-]+(?=\.[a-z])\.[a-z]+(\/[\S]*)*$/i.test val
                  tip = $filter('translate')('mt_ck_url_unsupported')
                else
                  tip = $filter('translate')('mt_ck_format')
              validateService.showError elem, tip
            return

          validator = (value) ->
            validity = (ngModel.$isEmpty value) or reg.test value
            ngModel.$setValidity 'urlFormat', validity
            if validity
              return value
            return false
          ngModel.$formatters.push validator
          ngModel.$parsers.push validator

          return
      }
  ]

  return

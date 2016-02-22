define [
  'wm/app'
  'wm/config'
], (app, config) ->
  app.registerController 'wm.ctrl.member.download.member', [
    'restService'
    'klpExportService'
    (restService, klpExportService) ->
      vm = this

      vm.download = ->
        params =
          begin: vm.beginDate
          end: vm.endDate
        klpExportService.export '会员下载', '/api/member/member/export-klp-member', params, false

      vm
]

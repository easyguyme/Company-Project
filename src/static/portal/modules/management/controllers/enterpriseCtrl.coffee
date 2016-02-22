define [
  'wm/app'
  'wm/config'
], (app, config) ->
  app.registerController 'wm.ctrl.management.enterprise', [
    'restService'
    '$interval'
    '$rootScope'
    'validateService'
    '$filter'
    (restService, $interval, $rootScope, validateService, $filter) ->
      vm = this

      _init = ->
        vm.isShowEditEnterprisePane = false
        vm.isShowEditContactNamePane = false
        vm.isShowEditContactTelPane = false
        vm.isShowEditHelpdeskPhonePane = false
        vm.enterpriseId = ''
        vm.checkedPhone = 'management_enterprise_phone_length'
        vm.requestCaptcha = 'management_enterprise_captcha'
        _initParams()
        vm.isSent = true
        _getAccount()

        vm.breadcrumb = [
          'enterprise_management'
        ]

        vm.verification = {}
        vm.getVerificationCode()

      _getAccount = ->
        restService.get config.resources.enterprise, (data) ->
          vm.enterpriseId = data.id
          vm.defaultEnterpriseRemarks = (if data.comapny is null then '' else data.comapny)
          vm.defaultContactNameRemarks = (if data.name is null then '' else data.name)
          vm.defaultContactTelRemarks = (if data.phone is null then '' else data.phone)
          vm.defaultHelpdeskPhone = data.helpdeskPhone or ''

      _initParams = ->
        vm.checkedTel = ''
        vm.times = ''
        vm.isSent = true

      _restoreSentCaptcha = (timer) ->
        $interval.cancel(timer) if timer
        vm.requestCaptcha = 'management_enterprise_captcha'
        _initParams()

      _sendCaptcha = ->
        time = 60
        if time > 0
          promise = $interval ( ->
            time--
            vm.pro = promise
            vm.requestCaptcha = 'management_enterprise_timing'
            vm.times = time
            if time is 0
              _restoreSentCaptcha(promise)
          ), 1000
        promise

      vm.checkNum = (val) ->
        validateService.checkTelNum val

      vm.checkedVerification = ->
        code = vm.verificationCode
        tip = ''
        if not code
          tip = 'required_field_tip'

        validateService.highlight($('.verification-code'), $filter('translate')(tip)) if tip
        tip

      vm.checkedCaptcha = ->
        code = vm.checkedTel
        tip = ''
        if not code
          tip = 'required_field_tip'

        if tip
          validateService.highlight($('#captcha'), $filter('translate')(tip))
        tip

      vm.restoreVerification = ->
        validateService.restore($(".verification-code"))
        return

      vm.showEditEnterprisePane = ->
        vm.isShowEditEnterprisePane = true
        vm.remarkCompany = vm.defaultEnterpriseRemarks

      vm.cancelEditCompany = ->
        vm.isShowEditEnterprisePane = false
        vm.defaultEnterpriseRemarks = vm.remarkCompany

      vm.showEditHelpdeskPhonePane = ->
        vm.isShowEditHelpdeskPhonePane = true
        vm.helpdeskPhone = vm.defaultHelpdeskPhone

      vm.cancelHelpdeskPhone = ->
        vm.isShowEditHelpdeskPhonePane = false
        vm.defaultHelpdeskPhone = vm.helpdeskPhone

      vm.showEditContactNamePane = ->
        vm.isShowEditContactNamePane = true
        vm.remarkName = vm.defaultContactNameRemarks

      vm.cancelEditName = ->
        vm.isShowEditContactNamePane = false
        vm.defaultContactNameRemarks = vm.remarkName

      vm.showEditContactTelPane = ->
        vm.isShowEditContactTelPane = true
        vm.remarkPhone = vm.defaultContactTelRemarks
        vm.verificationCode = ''
        vm.getVerificationCode()

      vm.cancelPhone = ->
        vm.isShowEditContactTelPane = false
        vm.defaultContactTelRemarks = vm.remarkPhone
        vm.requestCaptcha = 'management_enterprise_captcha'
        $interval.cancel vm.pro
        vm.isSent = true
        vm.times = ''

      vm.sendCode = ->
        if not vm.checkNum(vm.defaultContactTelRemarks) & not vm.checkedVerification()
          timer = _sendCaptcha()
          vm.isSent = false
          condition =
            mobile: vm.defaultContactTelRemarks
            type: 'updateCompanyInfo'
            code: vm.verificationCode
            codeId: vm.verification.code
          restService.post config.resources.contactMobile, condition, (data) ->
            if data and data.message is 'Error'
              _restoreSentCaptcha(timer)
              validateService.highlight($('#phone'), $filter('translate')('send_captcha_fail'))
              vm.getVerificationCode()
          , (res) ->
            vm.getVerificationCode()
            _restoreSentCaptcha(timer)

      vm.saveCompany = ->
        condition =
          company: vm.defaultEnterpriseRemarks
        restService.put config.resources.enterprise + '/' + vm.enterpriseId, condition, (data) ->
          vm.isShowEditEnterprisePane = false

      vm.saveHelpdeskPhone = ->
        condition =
          helpdeskPhone: vm.defaultHelpdeskPhone
        restService.put config.resources.enterprise + '/' + vm.enterpriseId, condition, (data) ->
          vm.isShowEditHelpdeskPhonePane = false

      vm.saveName = ->
        condition =
          name: vm.defaultContactNameRemarks
        restService.put config.resources.enterprise + '/' + vm.enterpriseId, condition, (data) ->
          vm.isShowEditContactNamePane = false

      vm.savePhone = ->
        phone = vm.defaultContactTelRemarks
        captcha = vm.checkedTel
        code = vm.verificationCode
        if vm.checkNum(phone) is '' & vm.checkedCaptcha(captcha) is '' & vm.checkedVerification(code) is ''
          condition =
            captcha: captcha
            phone: phone
          restService.put config.resources.enterprise + '/' + vm.enterpriseId, condition, (data) ->
            vm.isShowEditContactTelPane = false
            $interval.cancel(vm.pro)
            vm.requestCaptcha = 'management_enterprise_captcha'
            _initParams()

      vm.getVerificationCode = ->
        restService.get config.resources.captchas, (data) ->
          vm.verification.link = data.data
          vm.verification.code = data.codeId

      _init()

      vm
  ]

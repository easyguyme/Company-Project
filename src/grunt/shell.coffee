'use strict'

module.exports = (grunt) ->
  getIconNames:
    command: './yii management/module/get-config'
    options:
      callback: (err, stdout, stderr, cb) ->
        if err
          console.error err
        else
          nameMap = JSON.parse stdout
          content = '$nav-icons: ' + nameMap.menuNames.join(',') + ';\n\r'
          content += '$extension-modules: ' + nameMap.extNames.join(',') + ';\n\r'
          grunt.file.write 'static/portal/scss/_dynamic-variables.scss', content
          cb()

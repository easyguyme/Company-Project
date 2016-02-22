'use strict'

module.exports = (grunt) ->

  WEB_ROOT = 'frontend/web/'
  WEBAPP_ROOT = 'webapp/web/'
  packageData = grunt.file.readJSON('package.json')

  globalConfig =
    webRoot: WEB_ROOT
    webappRoot: WEBAPP_ROOT
    resourceRoot: 'static'
    webappModuleDir: 'webapp/modules'
    webappBuildDir: "#{WEBAPP_ROOT}/build"
    buildDir: "#{WEB_ROOT}/build"
    distDir: "#{WEB_ROOT}/build/"
    distCoreDir: "#{WEB_ROOT}/build/modules/core"
    requirejsPath: 'vendor/bower/requirejs/'
    moduleDir: 'static/portal/modules/'
    angularLoaderDir: 'static/portal/coffee/'
    styleDir: 'static/portal/scss'
    i18nDistDir: "#{WEB_ROOT}/i18n/"
    imageDistDir: "#{WEB_ROOT}/images/"
    locateChFile: 'locate-zh_cn.json'
    locateEnFile: 'locate-en_us.json'
    chatDir: 'static/chat'
    mobileDir: 'static/webapp'
    viewsDir: 'frontend/views'
    jsonIndent: 2
    extModules: packageData.extModules

  require('load-grunt-config') grunt,
    staticMappings: {}
    data: globalConfig

  require('time-grunt')(grunt)

  ###############################################################
  # Load external libs
  ###############################################################
  extend = require('extend')

  # on watch events configure jshint:all to only run on changed file
  grunt.event.on 'watch', (action, filepath, target) ->
    if filepath.indexOf('i18n') isnt -1
      globalConfig.jsonIndent = 2
      # Get mapping global i18n file path
      isEnglishChanged = filepath.lastIndexOf('en_us.json') isnt -1
      globalFilePath = globalConfig.i18nDistDir + (if isEnglishChanged then globalConfig.locateEnFile else globalConfig.locateChFile)
      anotherGlobalFilePath = globalConfig.i18nDistDir + (if isEnglishChanged then globalConfig.locateChFile else globalConfig.locateEnFile)
      # Read the related i18n JSON in the module
      changedJSON = grunt.file.readJSON filepath
      anotherFilePath = filepath.replace 'zh_cn.json', 'en_us.json'
      anotherFilePath = filepath.replace 'en_us.json', 'zh_cn.json' if isEnglishChanged
      if grunt.file.exists anotherFilePath
        anotherJSON = grunt.file.readJSON anotherFilePath
        copyChangedJSON = extend({}, changedJSON)
        copyAnotherJSON = extend({}, anotherJSON)
        for key of copyChangedJSON
          if anotherJSON[key]
            delete copyChangedJSON[key]
            delete copyAnotherJSON[key]
        # Collect unmatched keys in i18n files
        leftChangedKeys = Object.keys(copyChangedJSON)
        leftAnotherKeys = Object.keys(copyAnotherJSON)
        grunt.log.error 'Please add keys "' + leftAnotherKeys + '" in ' + filepath if leftAnotherKeys.length > 0
        grunt.log.error 'Please add keys "' + leftChangedKeys + '" in ' + anotherFilePath if leftChangedKeys.length > 0
        if leftAnotherKeys.length + leftAnotherKeys.length is 0
          # Create global i18n files there is none
          grunt.file.write globalFilePath, JSON.stringify({}) if not grunt.file.exists globalFilePath
          grunt.file.write anotherGlobalFilePath, JSON.stringify({}) if not grunt.file.exists anotherGlobalFilePath
          # Merge global i18n files
          globalJSON = grunt.file.readJSON globalFilePath
          extend globalJSON, changedJSON
          grunt.file.write globalFilePath, JSON.stringify(globalJSON, false, globalConfig.jsonIndent)
          anotherGlobalJSON = grunt.file.readJSON anotherGlobalFilePath
          extend anotherGlobalJSON, anotherJSON
          grunt.file.write anotherGlobalFilePath, JSON.stringify(anotherGlobalJSON, false, globalConfig.jsonIndent)
      else
        grunt.log.error 'Please add file ' + anotherFilePath
    else if target.indexOf('coffeelint') isnt -1
      targetName = target.replace 'coffeelint', 'build'
      fileSrc = filepath.replace grunt.config.get('coffeelint')[targetName].cwd + '/', ''
      grunt.config("coffeelint.#{targetName}.src", fileSrc)
    else if target.indexOf('scsslint') isnt -1
      targetName = target.replace 'scsslint', 'build'
      fileSrc = filepath
      grunt.config("scsslint.#{targetName}.src", fileSrc)
    else if target.indexOf('htmllint') isnt -1
      targetName = target.replace 'htmllint', 'build'
      fileSrc = filepath
      grunt.config("htmllint.#{targetName}.src", fileSrc)


  ##############################################################
  # Util functions
  ###############################################################
  # Replace text in file
  replaceText = (filePath, replacements) ->
    content = grunt.file.read filePath
    replacements = [replacements] if replacements.constructor isnt Array
    for replacement in replacements
      content = content.replace(replacement.regx, replacement.text)
    grunt.file.write filePath, content

  ##############################################################
  # Customized tasks
  ###############################################################

  grunt.registerMultiTask 'i18n', 'Merge i18n data in modules', ->
    config = this.data
    i18nFiles = grunt.file.expand config.src
    i18nMaps = {}
    i18nFiles.forEach (filepath) ->
      key = filepath.slice(filepath.lastIndexOf('-') + 1, filepath.lastIndexOf('.'))
      i18nMaps[key] = {} if not i18nMaps[key]
      tmpJSON = grunt.file.readJSON filepath
      extend i18nMaps[key], tmpJSON
    for key, value of i18nMaps
      grunt.file.write "#{globalConfig.i18nDistDir}locate-#{key}.json", JSON.stringify(value)

  # Usage: grunt diffi18n:moduleName
  grunt.registerTask 'diffi18n', 'Diff i18n data in modules', (moduleName) ->
    targetI18nFolder = globalConfig.moduleDir + moduleName + '/i18n/'
    if grunt.file.isDir targetI18nFolder
      globalI18nFiles = ['locate-en_us.json', 'locate-zh_cn.json']
      i18nENMap = {}
      i18nCHMap = {}
      # Read json from i18n file to cache map
      globalI18nFiles.forEach (filepath) ->
        tmpJSON = grunt.file.readJSON targetI18nFolder + filepath
        if filepath.indexOf('zh_cn.json') isnt -1
          extend i18nCHMap, tmpJSON
        else
          extend i18nENMap, tmpJSON
      enKeyLen = Object.keys(i18nENMap).length
      chKeyLen = Object.keys(i18nCHMap).length
      iterateMap = if enKeyLen > chKeyLen then i18nENMap else i18nCHMap
      compareMap = if enKeyLen > chKeyLen then i18nCHMap else i18nENMap
      for key, value of iterateMap
        if compareMap[key]
          delete iterateMap[key]
          delete compareMap[key]
      extend iterateMap, compareMap
      leftKeys = Object.keys iterateMap
      grunt.log.error 'Please add keys for module ' + moduleName, leftKeys if leftKeys.length

  # Usage: grunt stripi18n:moduleName
  grunt.registerTask 'stripi18n', 'Strip duplicated i18n data in modules', (moduleName) ->
    targetI18nFolder = globalConfig.moduleDir + moduleName + '/i18n/'
    if grunt.file.isDir targetI18nFolder
      globalI18nFiles = ['locate-en_us.json', 'locate-zh_cn.json']
      # Read json and write to file directly
      globalI18nFiles.forEach (filepath) ->
        tmpJSON = grunt.file.readJSON targetI18nFolder + filepath
        grunt.file.write targetI18nFolder + filepath, JSON.stringify(tmpJSON, false, globalConfig.jsonIndent)

  # Usage: grunt addi18n:moduleName:keyName:English:Chinese
  grunt.registerTask 'addi18n', 'Add new i18n data in modules', (moduleName, keyName, English, Chinese) ->
    targetI18nFolder = globalConfig.moduleDir + moduleName + '/i18n/'
    if grunt.file.isDir targetI18nFolder
      globalI18nFiles = ['locate-en_us.json', 'locate-zh_cn.json']
      # Add new key and value for i18n files
      globalI18nFiles.forEach (filepath) ->
        tmpJSON = grunt.file.readJSON targetI18nFolder + filepath
        if tmpJSON[keyName]
          grunt.log.error 'Key ' + keyName + ' is already in the ' + filepath + ' file, choose another one'
        else
          tmpJSON[keyName] = if filepath.indexOf('zh_cn.json') isnt -1 then Chinese else English
          grunt.file.write targetI18nFolder + filepath, JSON.stringify(tmpJSON, false, globalConfig.jsonIndent)

  grunt.registerMultiTask 'mergeconf', 'Merge the frontend configuration', ->
    configFiles = grunt.file.expand this.data.src
    modules = {}
    introduction = {}
    configFiles.forEach (filepath) ->
      config = grunt.file.readJSON filepath
      if filepath.indexOf('config.json') > 0
        modules[config.name] = config
      else
        introduction[config.name] = config
    #Write to the config.coffee file
    replaceText this.data.dest, [
      {
        regx: /modules:.+/
        text: 'modules:' + JSON.stringify(modules) + ','
      }
      {
        regx: /introduction:.+/
        text: 'introduction:' + JSON.stringify(introduction) + ','
      }
    ]

  grunt.registerMultiTask 'loadsasses', 'Generate the loader file for module index.scss files', ->
    config = this.data
    indexFiles = grunt.file.expand config.src
    fileContent = "@import '../modules/core/index.scss';\n"
    indexFiles.forEach (file) ->
      file = file.slice(globalConfig.moduleDir.length)
      fileContent += "@import '../modules/#{file}';\n" if file.indexOf('core') < 0
    grunt.file.write config.dest, fileContent

  grunt.registerTask 'genversion', 'Generate the deployment timestamp for generated js files', ->
    timestamp = new Date().getTime()
    # Generate timestamp for loader files
    mainFiles = grunt.file.expand "#{globalConfig.buildDir}/**/main.js"
    mainFiles.forEach (filePath) ->
      replaceText(filePath,
        regx: /v=.+/
        text: "v=#{timestamp}'"
      )
    # Generate timestamp in php file for static assesets
    replaceText('frontend/config/params.php',
      regx: /'buildVersion'.+/
      text: "'buildVersion' => #{timestamp},"
    )
    # HTML version for angular based main site and chat site
    appFiles = ["#{globalConfig.buildDir}/app.js", "#{globalConfig.buildDir}/chat/app.js"]
    for appFile in appFiles
      replaceText appFile, [
        {
          regx: /\.html.*'/g
          text: ".html?v=#{timestamp}'"
        }
        {
          regx: /\.json.*'/g
          text: ".json?v=#{timestamp}'"
        }
      ]

  grunt.registerMultiTask 'mergeamd', 'Merge multiple anonymous AMD modules as one ', ->
    config = this.data
    moduleFiles = grunt.file.expand config.src
    depends = []
    content = ''
    modMap = {}

    moduleFiles.forEach (file) ->
      if config.exclude.indexOf(file.slice(file.lastIndexOf('/') + 1)) is -1
        fileContent = grunt.file.read file
        if not /app\.register/.test(fileContent)
          # Get the AMD module content
          innerContent = fileContent.match(/define\(\[.+\)\s\{[\S\s]+?([\S\s]+)\}\);/)[1]
          # Replace comment and white space
          innerContent = innerContent.replace(/\/\*\*[\S\s]+\*\//, '').match(/\w[\S\s]+;/)[0]
          # Replace first line return statement
          innerContent = innerContent.slice(6) if innerContent.indexOf('return') is 0
          content += innerContent
          # Get the AMD module dependencies
          modDepends = fileContent.match(/define\(\[(.+)\]/)[1].replace(/['"\s]+/g, '').split(',')
          # Get the AMD module dependencies mapping aliases
          modAliases = fileContent.match(/function\((.+)\)/)[1].split(', ')
          modDepends.forEach (modDep, idx) ->
            modMap[modDep] = if modAliases[idx] then modAliases[idx] else ''
        else
          console.log("#{file} is lazy loaded with register method")
    namedDeps = []
    unNamedDeps = []
    aliases = []
    for modDep, alias of modMap
      if alias
        aliases.push(alias)
        namedDeps.push(modDep)
      else
        unNamedDeps.push(modDep)
    depStr = JSON.stringify(namedDeps.concat(unNamedDeps))
    aliasStr = aliases.join(',')
    mergedAMDModule = "define(#{depStr}, function(#{aliasStr}) {\n#{content}\n});"
    grunt.file.write config.dest, mergedAMDModule

  grunt.registerTask 'linkmodule', 'Symbol link exteranl links to proper folders', (modName, stripped) ->
    fs = require('fs')
    stripAll = modName is 'true'
    stripped = true if stripAll
    modules = if modName and not stripAll then [modName] else globalConfig.extModules
    innerMods = ['backend', 'static', 'webapp', 'console']
    modules.forEach (module) ->
      basePath = "#{__dirname}/modules/#{module}/"
      innerMods.forEach (mod) ->
        destPath = if mod is 'static' then "static/portal/modules/#{module}" else "#{mod}/modules/#{module}"
        destPath = "#{__dirname}/#{destPath}"
        if fs.existsSync(basePath + mod)
          if stripped
            fs.unlink(destPath)
          else
            fs.symlink(basePath + mod, destPath)
        return

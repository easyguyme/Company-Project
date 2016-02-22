var glob = require('glob')
var fs = require('fs')
var cnchars = require('cn-chars');

//Add your own translators for auto translation, key: translate function
var i18nTranslator = {
  'zh_tr': function(str) {
    var transStr = [];
    var len = str.length;
    for (var i = 0; i < len; i++) {
      transStr.push(cnchars.toTraditionalChar(str.charAt(i)))
    }
    return transStr.join('')
  }
}

// if traditional Chinese is different from simplified Chinese, use this variable to translate
var traditionalChineseMap = {
  '邮政编码': '郵遞區號',
  '请填写收货地址的邮政编码': '請填寫收貨地址的郵遞區號'
}

//frontend part

function translateFrontendMessages(files) {
  files.forEach(function(file){
    var jsonFile = fs.readFileSync(file)
    var json = JSON.parse(jsonFile)
    for (name in i18nTranslator) {
      for (key in json) {
        // if translating key consist in traditionalChineseMap, then use traditionalChineseMap  to translate
        json[key] = traditionalChineseMap[json[key]] ? traditionalChineseMap[json[key]] : i18nTranslator[name](json[key])
      }
      translatedFile = file.slice(0, file.lastIndexOf('/')) + '/locate-' + name + '.json'
      if (fs.existsSync(translatedFile)) {
        fs.unlinkSync(translatedFile)
      }
      fs.writeFileSync(translatedFile, JSON.stringify(json, false, 4) ,{encoding:'utf-8'})
    }
  })
}

// It only takes effect for real files, soft symbol link has no effect
// (On condition that grunt linkmodule executed before run node translate.js)
glob('static/portal/modules/**/locate-zh_cn.json', function(er, files) {
  translateFrontendMessages(files);
});

glob('modules/**/locate-zh_cn.json', function(er, files) {
  translateFrontendMessages(files);
});

//backend part

function translateBackendMessages(files) {
  files.forEach(function(file){
    if (!fs.existsSync(file)) {
      return;
    }
    var phpFile = fs.readFileSync(file)
    var content = phpFile.toString()
    if (content) {
      //get array part of php file
      var arrayStr = content.replace(/\n/g,'').match(/\[.*\]/)[0]
      //format php array str to json
      var jsonStr = arrayStr.replace(/=>/g, ':').replace(/\[(.*?),?\]/, '{$1}').replace(/'/g, '"')
      var json = JSON.parse(jsonStr)
      for (name in i18nTranslator) {
        for (key in json) {
          json[key] = i18nTranslator[name](json[key])
        }
        //write to php file
        translatedFile = file.replace('zh_cn', name)
        if (fs.existsSync(translatedFile)) {
          fs.unlinkSync(translatedFile)
        }
        arrayStr = JSON.stringify(json, false, 4).replace(/\{/, '[').replace(/\}/, ']').replace(/":\s"/g, '"=>"')
        phpFormat = '<?php\nreturn ' + arrayStr + ';'
        var dirName = translatedFile.slice(0, translatedFile.indexOf(name)) + name
        if (!fs.existsSync(dirName)) {
          fs.mkdirSync(dirName)
        }
        fs.writeFileSync(translatedFile, phpFormat ,{encoding:'utf-8'})
      }
    }
  })
}

glob('backend/messages/zh_cn/*.php', function(er, files){
  translateBackendMessages(files);
});

glob('backend/modules/**/messages/zh_cn/*.php', function(er, files){
  translateBackendMessages(files);
});

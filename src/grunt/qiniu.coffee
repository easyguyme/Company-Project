'use script'

module.exports =
  options:
    accessKey: 'Ft6PlywyvrQDZWc0kdc7nFuJYB4Hcm38Yy68oiFf'
    secretKey: 'o2SriW2EoujXHSadCPHbEJsvpVo2vPzEYAHd8A3N'
    bucket: 'quncrm'
    domain: 'dn-quncrm.qbox.me'
  landing:
    options:
      resources: [
        cwd: '<%= webRoot %>'
        pattern: 'build/landing/{**,}/*.*'
      ]
  webapp:
    options:
      resources: [
        cwd: '<%= webappRoot %>'
        pattern: 'build/{**,}/*.*'
      ]

<raw>
  <span></span>

  let self = this,
      _renderHtml;

  _renderHtml = () => {
    self.root.innerHTML = opts.content || self.content || ''
  }

  self.on('updated', function() {
    _renderHtml()
  })

</raw>

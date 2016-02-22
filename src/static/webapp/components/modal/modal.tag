<modal>
  <section class="c-modal" show={ isShowModal }>
    <section class="c-modal-content" riot-style="width: { modalWidth }; top: { modalTop }; left: { modalLeft }" >
      <div class="c-modal-header">
        <button type="button" class="c-modal-close" onclick={ hideModal }></button>
        <h4 class="c-modal-title">{ title }</h4>
      </div>
      <div class="c-modal-body" riot-style="max-height: { modalmaxHeight }">
        <yield/>
      </div>
    </section>
    <div class="mask-wrap" onclick={ hideModal }>
    </div>
  </section>

  let self = this

  self.title = opts.conf.title || ''
  self.modalWidth = opts.conf.width || '80%'
  self.modalmaxHeight = opts.conf.maxheight || '10rem'
  self.modalTop = opts.conf.top || '3rem'
  self.modalLeft = opts.conf.left || '10%'
  self.isShowModal = opts.isShowModal || false

  self.hideModal = (e) => {
    self.isShowModal = false
  }

</modal>

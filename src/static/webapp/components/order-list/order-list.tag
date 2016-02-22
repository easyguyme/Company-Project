<order-list>
  <section class="c-order-list" each={ list }>
    <a href="{ link }" >
      <panel padding="true">
        <div class="c-order-list__head">
          <kv-list items={ headItems }></kv-list>
          <span class="c-order-list__head__status">{ statusText }</span>
        </div>
        <div class="c-order-list__body clearfix">
          <section class="c-order-list__body__img" style="background-image:url({ img })"></section>
          <section class="c-order-list__body__content">
            <div class="c-order-list__body__content__title">{ name }</div>
            <div class="c-order-list__body__content__price">{ price }</div>
            <div class="c-order-list__body__content__illustration"> { illustration } </div>
            <kv-list items={ items }></kv-list>
          </section>
        </div>
        <div show={ showBtn } class="c-order-list__footer">
          <status-btn product-id={ productId } reservation-id={ reservationId } order-id={ orderId } order-number={ orderNumber } status={ status } evaluate={ evaluate } click-handler={ parent.clickHandler }></status-btn>
        </div>
      </panel>
    </a>
  </section>
  <div show={ isLoading } class="loading"></div>

  let self = this
  self.list = opts.list || []
  self.isLoading = opts.isLoading || false
  self.reservationId = opts.reservationId
  self.orderNumber = opts.orderNumber
  self.orderId = opts.orderId
  self.productId = opts.productId
  self.status = opts.status
  self.evaluate = opts.evaluate || false
  self.clickHandler = opts.clickHandler

  var _formatData

  self.on('updated', function() {
    _formatData()
  })

  _formatData = () => {
    var panels = self.tags.panel

    if ($.isArray(panels)) {
      for (let i = 0, len = panels.length; i < len; i++) {
        self.list[i].showBtn = !!panels[i].tags['status-btn'].btns.length
      }
    } else if (panels) {
      self.list[0].showBtn = !!panels.tags['status-btn'].btns.length
    }

    self.update()
  }

</order-list>

<reminder-panel>
  <panel padding="true">
    <section class="c-reminder-panel">
      <p class="c-reminder-panel__tip">{ parent.tip }</p>
      <kv-list items={ parent.items }></kv-list>
      <div class="c-reminder-panel__footer clearfix">
        <div class="c-reminder-panel__footer__btn">
          <status-btn product-id={ parent.productId } reservation-id={ parent.reservationId } order-id={ parent.orderId } order-number={ parent.orderNumber } status={ parent.status } evaluate={ parent.evaluate } click-handler={ parent.clickHandler }></status-btn>
        </div>
      </div>
    </section>
  </panel>

  self = this
  self.tip = opts.tip
  self.items = opts.items || []
  self.status = opts.status
  self.evaluate = opts.evaluate || false
  self.orderId = opts.orderId
  self.orderNumber = opts.orderNumber
  self.reservationId = opts.reservationId
  self.productId = opts.productId
  self.clickHandler = opts.clickHandler

</reminder-panel>

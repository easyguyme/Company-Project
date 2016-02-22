<section class="content-member">
  <section class="member-header">
    <avatar></avatar>
    <div class="member-header-item">
      <div class="member-name"></div>
      <div class="member-card">
        <span class="member-card-name"></span>
        <span class="member-card-number"></span>
      </div>
    </div>
  </section>
  <nav-tab></nav-tab>
  <section class="member-info">
    <div class="member-property">
      <kv-list class="member-default-property"></kv-list>
    </div>
    <div class="member-content">
      <account></account>
      <m-tags></m-tags>
    </div>
    <div class="postion-bottom">
      <div class="center member-extend-properties hide" id="member-extend">查看扩展属性</div>
    </div>
  </section>
  <section class="member-purchase hide">
    <div class="member-purchase-no-record center">该会员没有购买记录</div>
    <div class="member-purchase-wrap hide">
      <div class="member-purchase-head center">
        <div>已有<i class="purchase-days"></i>天未发生购买行为</div>
        <div>最后购买时间:&nbsp;&nbsp;&nbsp;<i class="purchase-time"></i></div>
      </div>
      <m-purchase></m-purchase>
    </div>
  </section>
  <modal>
    <kv-list class="member-extend-property"></kv-list>
  </modal>
</section>
<script src="/build/webapp/components/nav-tab/nav-tab.js"></script>
<script src="/build/webapp/components/kv-list/kv-list.js"></script>
<script src="/build/webapp/components/m-tags/m-tags.js"></script>
<script src="/build/webapp/components/avatar/avatar.js"></script>
<script src="/build/webapp/components/m-purchase/m-purchase.js"></script>
<script src="/build/webapp/components/modal/modal.js"></script>
<script src="/build/webapp/components/account/account.js"></script>

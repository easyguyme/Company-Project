<div class="faq" ng-controller="FaqCtrl">
  <input type="hidden" id="category" value="<?= $category ?>"/>
  <input type="hidden" id="accountId" value="<?= $accountId ?>"/>
  <div class="faq-header">
    常见问题解答
  </div>
  <div class="faq-content">
    <?php if (!$category) : ?>
      <ul class="menus">
        <li bindonce ng-repeat="menu in data track by $index" ng-click="selectMenu(menu.name)">
          <a href="javascript:void(0);" ng-class="{'active':menu.active}">{{menu.name}}</a>
          <img ng-show="menu.active" src="/images/helpdesk/tab_selected.png" />
        </li>
      </ul>
    <?php endif; ?>
    <div class="list">
      <div bindonce ng-repeat="category in data track by $index" id="{{category.name}}">
        <div class="title">
          {{category.name}}
        </div>
        <ul>
          <li ng-repeat="question in data[$index].questions">
            <div class="question-wrapper" ng-click="openAnswer(question, $event)">
              <span class="question">
                <div class="question-icon" ng-class="{'fewer-icon':question.status=='hide'||!question.status, 'unfold-icon':question.status=='open'}"></div>
                {{question.question}}
              </span>
            </div>
            <div class="answer" id="answer_{{question.id}}" bo-html="question.answer | textareaBr">
            </div>
          </li>
        </ul>
      </div>
      <div class="bottom"></div>
    </div>
  </div>
</div>

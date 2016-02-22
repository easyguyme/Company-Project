define ["core/coreModule"], (mod) ->
  mod.directive 'wmWechatGraphic', [
    ->
      return (
        restrict: "A"
        scope:
          graphic: "="
          displayOptions: "@"
          linkable: "@"
          default: "=defaultData"
          selectedIndex: "="
          isEdit: "="
          articleSelect: "&"
          articleDelete: "&"
          graphicDelete: "&"
          graphicEdit: "&"
        transclude: true
        template: '<div class="news-view" ng-show="graphic.articles.length == 1">
                    <div class="waterfall-news-detail">
                      <div class="clearfix waterfall-news-header waterfall-news-header-single">
                        <span class="pull-left">{{graphic.createdAt}}</span>
                        <span class="pull-right"></span>
                      </div>
                      <h4 class="waterfall-news-detail-title">{{graphic.articles[0].title || default.title}}</h4>
                      <div class="waterfall-news-detail-row">
                        <div class="waterfall-news-detail-img-container image-container">
                          <a href="/content/graphic/{{graphic.id}}?index=0" target="_blank" ng-show="displayOptions||linkable" class="a-images-style cp">
                            <img wm-center-img ng-src="{{graphic.articles[0].picUrl}}">
                          </a>
                          <img wm-center-img ng-src="{{graphic.articles[0].picUrl || default.picUrl}}" ng-show="!displayOptions&&!linkable">
                        </div>
                      </div>
                      <div class="waterfall-news-detail-content">{{graphic.articles[0].description || default.description}}</div>
                      <div ng-show="displayOptions" class="graphics-footer-position">
                        <div class="edit-icon-style cp" ng-click="graphicEdit()(graphic.id)" title="{{\'content_graphics_edit\' | translate}}"></div>
                        <div class="delete-icon-style cp" ng-click="graphicDelete()(graphic.id, $event)" title="{{\'content_graphics_delete\' | translate}}"></div>
                      </div>
                    </div>
                  </div>
                  <div class="news-view" ng-show="graphic.articles.length > 1" ng-class="{\'multiple-graphic-edit\': isEdit}">
                    <div class="waterfall-news-list">
                      <div class="clearfix waterfall-news-header waterfall-news-header-multiple">
                        <span class="pull-left">{{graphic.createdAt}}</span>
                        <span class="pull-right"></span>
                      </div>
                      <div class="waterfall-news-normal waterfall-top" ng-class="{\'waterfall-active\': selectedIndex===0}">
                        <div class="waterfall-news-list-inner">
                          <div class="waterfall-news-list-inner-img-container image-container">
                            <a href="/content/graphic/{{graphic.id}}?index=0" target="_blank" ng-show="displayOptions||linkable" class="a-images-style cp">
                              <img wm-center-img ng-src="{{graphic.articles[0].picUrl}}">
                            </a>
                            <img wm-center-img ng-src="{{graphic.articles[0].picUrl || default.picUrl}}" ng-show="!displayOptions&&!linkable">
                          </div>
                          <div class="waterfall-news-list-cover">{{graphic.articles[0].title || default.title}}</div>
                          <div class="waterfall-news-list-inner-cover">
                            <i class="icon-edit cp" ng-click="articleSelect()(0)"></i>
                          </div>
                        </div>
                      </div>
                      <div class="waterfall-news-normal" ng-repeat="article in graphic.articles" ng-show="$index != 0" ng-class="{\'waterfall-active\': selectedIndex===$index}">
                        <div class="waterfall-news-list-item clearfix">
                          <div class="waterfall-news-list-item-text">
                            {{article.title || default.title}}
                          </div>
                          <div class="waterfall-news-list-item-image">
                            <div class="waterfall-news-list-img-container image-container">
                              <a href="/content/graphic/{{graphic.id}}?index={{$index}}" target="_blank" ng-show="displayOptions||linkable" class="a-images-style cp">
                                <img wm-center-img ng-src="{{article.picUrl}}">
                              </a>
                              <img wm-center-img ng-src="{{article.picUrl || default.smallPicUrl}}" ng-show="!displayOptions&&!linkable">
                            </div>
                          </div>
                          <div class="waterfall-news-list-item-cover">
                            <div class="waterfall-news-list-item-icons">
                              <i class="icon-edit cp pull-left" ng-click="articleSelect()($index)"></i>
                              <i class="icon-delete cp pull-right" ng-click="articleDelete()($index)"></i>
                            </div>
                          </div>
                        </div>
                      </div>
                      <div class="waterfall-news-list-item cp" ng-show="isEdit && graphic.articles.length < 8" ng-click="articleSelect()(graphic.articles.length)">
                        <div class="waterfall-news-add-item">
                          {{\'content_graphics_add_graphic\'|translate}}
                        </div>
                      </div>
                      <div ng-show="displayOptions" class="graphics-footer-position">
                        <div class="edit-icon-style cp" ng-click="graphicEdit()(graphic.id)" title="{{\'content_graphics_edit\' | translate}}"></div>
                        <div class="delete-icon-style cp" ng-click="graphicDelete()(graphic.id, $event)" title="{{\'content_graphics_delete\' | translate}}"></div>
                      </div>
                    </div>
                  </div>'
      )
  ]

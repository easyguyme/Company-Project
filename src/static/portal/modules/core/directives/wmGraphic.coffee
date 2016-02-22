define ["core/coreModule"], (mod) ->
  mod.directive 'wmGraphic', [
    ->
      return (
        restrict: "A"
        scope:
          graphic: "="

        transclude: true
        template: '<div class="news-view" ng-show="graphic.articles.length == 1">
                    <div class="waterfall-news-detail">
                      <div class="clearfix waterfall-news-header waterfall-news-header-single">
                        <span class="pull-left">{{graphic.createTime | date:\'yyyy-MM-dd\'}}</span>
                      </div>
                      <h4 class="waterfall-news-detail-title">{{graphic.articles[0].title}}</h4>
                      <div class="waterfall-news-detail-row">
                        <div class="waterfall-news-detail-img-container image-container">
                          <a ng-href="{{graphic.articles[0].contentUrl}}" target="_blank" class="a-images-style cp">
                            <img wm-center-img ng-src="{{graphic.articles[0].url}}">
                          </a>
                        </div>
                      </div>
                      <div class="waterfall-news-detail-content">{{graphic.articles[0].description}}</div>
                    </div>
                  </div>
                  <div class="news-view" ng-show="graphic.articles.length > 1">
                    <div class="waterfall-news-list">
                      <div class="clearfix waterfall-news-header waterfall-news-header-multiple">
                        <span class="pull-left">{{graphic.createTime | date:\'yyyy-MM-dd\'}}</span>
                      </div>
                      <div class="waterfall-news-normal waterfall-top" ng-class="{\'waterfall-active\': selectedIndex===0}">
                        <div class="waterfall-news-list-inner">
                          <div class="waterfall-news-list-inner-img-container image-container">
                            <a ng-href="{{graphic.articles[0].contentUrl}}" target="_blank" class="a-images-style cp">
                              <img wm-center-img ng-src="{{graphic.articles[0].url}}">
                            </a>
                          </div>
                          <div class="waterfall-news-list-cover">{{graphic.articles[0].title}}</div>
                        </div>
                      </div>
                      <div class="waterfall-news-normal" ng-repeat="article in graphic.articles" ng-show="$index != 0">
                        <div class="waterfall-news-list-item clearfix">
                          <div class="waterfall-news-list-item-text">
                            {{article.title}}
                          </div>
                          <div class="waterfall-news-list-item-image">
                            <div class="waterfall-news-list-img-container image-container">
                              <a ng-href="{{article.contentUrl}}" target="_blank" class="a-images-style cp">
                                <img wm-center-img ng-src="{{article.url}}">
                              </a>
                            </div>
                          </div>
                        </div>
                      </div>
                    </div>
                  </div>'
      )
  ]

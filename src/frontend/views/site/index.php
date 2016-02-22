<nav class="navbar container-fluid container-fluid-fix-nav" role="navigation" ng-class="{'visible':isLogined}">
  <div class="portal-message"></div>
  <div class="navbar-header">
    <a class="navbar-brand" href="#">
      <img src="/images/site/logo.png" alt="">
    </a>
  </div>
  <wm-top-nav ng-hide="isHideTopNav" current-state="currentState" channels="channels" channel-link="{{currentChannel.link}}" mods="conf.mods"></wm-top-nav>
  <ul class="nav navbar-nav navbar-right">
    <li><wm-message></wm-message></li>
    <li ng-show="isAdmin">
      <a ng-class="{active:highlightManagement}" href="/management/user" wm-tooltip="{{'administration'|translate}}">
        <i class="glyphicon-nav glyphicon glyphicon-cog"></i>
        <!-- <span class="nav-title" translate="administration"></span> -->
      </a>
    </li>
    <li>
      <a href="http://help.quncrm.com/" target="_blank" wm-tooltip="{{'helper'|translate}}">
        <i class="glyphicon-nav glyphicon glyphicon-question-sign"></i>
        <!-- <span class="nav-title" translate="helper"></span> -->
      </a>
    </li>
    <li class="dropdown" dropdown on-toggle="toggled(open)">
      <a href class="dropdown-toggle" dropdown-toggle>
        <img class="avatar" ng-src="{{user.avatar|qiniu}}"/>
        <span class="nav-title">
            <span class="user-name" wm-tooltip="{{user.name}}" tooltip-max-width="150">{{user.name}}</span>
            <b class="unfold"></b>
        </span>
      </a>
      <ul class="dropdown-menu user-setting">
        <li ng-repeat="action in user.actions">
          <a ng-href="{{action.link}}" ng-click="user[action.handler]()">{{action.title|translate}}</a>
        </li>
      </ul>
    </li>
  </ul>
</nav>
<div class="notification" ng-class="{'with-nav':isLogined}"></div>
<div class="container-fluid container-fluid-fix main-content-view" ng-class="{'main-content-wrap':isLogined}">
  <div class="row ov main-content" ng-class="{'hide-vertical-nav':!isLogined||isFullScreen}">
    <wm-vertical-nav ng-hide="isHideVerticalNav" current-state="currentState" channels="channelSuccess" current-channel="currentChannel" menus="conf.menus"></wm-vertical-nav>
    <div class="col-xs-10 viewport-wrap" ng-class="{'col-xs-12':isHideVerticalNav}" style="padding:0">
      <div class="viewport" ng-class="{'embeded':isLogined}">
          <div ui-view></div>
          <div wm-footer ng-class="{'visible':isLogined}"></div>
      </div>
    </div>
  </div>
</div>
<div id="mask-loading" class="mask-loading" style="background-color: rgba(0, 0, 0, 0.17);">
    <div class="loading-icon"></div>
</div>
<div class="export-wrapper" ng-show="isLogined"></div>
<?php include 'person.php';?>

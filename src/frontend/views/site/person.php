<!-- Psersonal Data -->
<script type="text/ng-template" id="personalData.html">
<div ng-show="isShowView">
  <div class="modal-header">
    <h4 class="title create-user-title">
      {{'management_personal_data' | translate}}
      <div class="display" ng-click="showUpdateDialog()" >
        <div class="edit-display cp"></div>
      </div>
       <button type="button" class="close popup-close" ng-click="closeDialog()" style="outline: none;margin-top: 3px;"></button>
    </h4>
  </div>
  <div class="modal-body-user modal-bgcolor create-user-body clearfix p-style">
    <div class="pull-left width-60p">
      <div class="form-group mb10">
        <label class="dark-gray">{{'management_account' | translate}}</label>
        <p>{{userData.email}}</p>
      </div>
      <div class="form-group mb10" ng-if="user.role">
        <label class="dark-gray">{{'management_role' | translate}}</label>
        <p>{{userData.role | translate}}</p>
      </div>
      <div class="form-group mb10" ng-if="user.badge">
        <label class="dark-gray">{{'management_badge' | translate}}</label>
        <p>{{userData.badge | translate}}</p>
      </div>
      <div class="form-group mb10">
        <label class="dark-gray">{{'nickname' | translate}}</label>
        <p>{{userData.name}}</p>
      </div>
      <div class="form-group mb10">
        <label class="dark-gray">{{'management_language' | translate}}</label>
        <p>{{userData.language | translate}}</p>
      </div>
    </div>
    <div class="pull-left width-26p">
      <div class="modal-avatar-wrapper-user">
        <div class="thumbnail img-wrapper">
          <img class="menber-avatar-icon " ng-src="{{userData.avatar|qiniu:80}}"/>
        </div>
      </div>
    </div>
  </div>
</div>

<div ng-show="isShowUpdate">
  <div class="modal-header">
    <button type="button" class="close popup-close" ng-click="closeDialog()" style="outline: none;margin-top: 12px;"></button>
    <h4 class="title create-user-title">{{'management_update_personal_data' | translate}}</h4>
  </div>
  <form ng-submit="submit()">
    <div class="modal-body-user modal-bgcolor create-user-body clearfix p-style">
      <div class="pull-left width-60p">
        <div class="form-group mb10">
          <label class="dark-gray" for="email">{{'management_account' | translate}}</label>
          <p>{{userData.email}}</p>
        </div>
        <div class="form-group mb10" ng-if="user.role">
          <label class="dark-gray" for="role">{{'management_role' | translate}}</label>
          <p>{{userData.role | translate}} </p>
        </div>
        <div class="form-group mb10" ng-if="user.badge">
          <label class="dark-gray">{{'management_badge' | translate}}</label>
          <p>{{userData.badge | translate}}</p>
        </div>
        <div class="form-group mb10">
          <label class="dark-gray" for="name">{{'nickname' | translate}}</label>
          <div class="rel">
            <input id="name" ng-model="userData.name" class="contrl-style form-control md" maxlength="15" wm-validate="checkNickname" form-tip="{{'management_input_string' | translate}}" required/>
          </div>
        </div>
        <div class="form-group">
          <label class="dark-gray" for="role">{{'management_language' | translate}}</label>
          <div class="translations-radio">
            <label class="radio-inline">
              <span wm-radio value="zh_cn" ng-model="userData.language"></span><span>{{'zh_cn'|translate}}</span>
            </label>
            <label class="radio-inline">
              <span wm-radio value="en_us" ng-model="userData.language"></span><span>{{'en_us'|translate}}</span>
            </label>
            <label class="radio-inline">
              <span wm-radio value="zh_tr" ng-model="userData.language"></span><span>{{'zh_tr'|translate}}</span>
            </label>
          </div>
          <span class="form-tip form-style">{{'management_tip' | translate}}</span>
        </div>
      </div>

      <div class="pull-right width-26p">
        <div class="modal-avatar-wrapper-user">
          <div class="thumbnail img-wrapper">
            <div class="menber-avatar-icon"><img ng-src="{{userData.avatar|qiniu:80}}"/></div>
              <div id="user-profile-avatar" wm-file-upload process-bar='true' class="file-upload-wrap file-style" ng-model="userData.avatar">
                <div class="img-btn-style file-upload-wrap" translate="upload_picture"></div>
              </div>
            </div>
          </div>
          <div class="pic-style">
              <span class="form-tip-img form-style">{{'management_icon_tip' | translate}}</span>
          </div>
        </div>
      </div>

    <div class="modal-footer modal-bgcolor center-text create-user-footer">
    <button class="btn btn-success user-btn" type="submit" >{{'management_user_submit' |translate}}</button>
    </div>
  </form>
</div>
</script>

<!-- update password -->
<script type="text/ng-template" id="updatePwd.html">
<div class="modal-header">
  <h4 class="title create-user-title">
    {{'management_update_password' | translate}}
     <button type="button" class="close popup-close" ng-click="closeDialog()" style="outline: none;margin-top: 2px;"></button>
  </h4>
</div>
<form ng-submit="submit()">
  <div class="modal-body-user modal-bgcolor create-user-body clearfix">
    <div class="form-group mb10">
      <label class="dark-gray label-width display" for="old-password">{{'management_current_password' | translate}}</label>
      <div class="rel">
        <input type="password" class="hide-password"/>
        <input type="password" id="old-password" autocomplete="off" class="form-control form-control-style" ng-model="userData.currentPwd" below-msg wm-validate="checkCurrentPwd" form-tip="{{'management_input_currentpwd' | translate}}" required/>
      </div>
    </div>
    <div class="form-group mb10">
      <label class="dark-gray" for="new-password">{{'management_new_password' | translate}}</label>
      <div class="rel">
        <input type="password" id="new-password" class="form-control form-control-style" ng-model="userData.newPwd" below-msg wm-validate="checkNewPwd" form-tip="{{'management_password_tip' | translate}}" required/>
      </div>
    </div>
    <div class="form-group mb10">
      <label class="dark-gray" for="confirm-password">{{'management_confirm_password' | translate}}</label>
      <div class="rel">
        <input type="password" id="confirm-password" class="form-control form-control-style" ng-model="userData.password" below-msg wm-validate="checkPassword" form-tip="{{'management_password_tip' | translate}}" required/>
      </div>
    </div>
  </div>
  </div>
  <div class="modal-footer modal-bgcolor center-text create-user-footer">
    <button class="btn btn-success user-btn" >{{'management_user_submit' |translate}}</button>
  </div>
</form>
</script>

<div class="wm-block-border wm-message-pd">
    <div class="wm-rem-mb15 wm-rem-f16 wm-message-color"><label><?php echo ((!empty($title) || $title == '0') ? htmlspecialchars($title, ENT_QUOTES) : '未命名的留言版'); ?></label></div>
    <form>
        <?php foreach ($info as $message) { ?>

        <div>
            <div> <?php if($message['required'] == 'true') { ?>
                <label class="wm-required-field wm-rem-f14 wm-message-input">
                <?php } else { ?>
                     <label class="wm-rem-f14 wm-message-input">
                <?php } ?>
            <?php echo ((empty($message['question']) && $message['question'] != '0') ? '未命名的字段' : htmlspecialchars($message['question'], ENT_QUOTES)); ?></label></div>
            <?php if($message['textType'] == 'single') { ?>
                <input message-key="<?php echo $message['question'];?>" class="wm-form-control wm-rem-f15 wm-message-input wm-rem-mt10 wm-rem-mb15 wm-rem-h42" type="text" <?php if('true' === $message['required']) {echo 'required';} ?>/>
            <?php } else { ?>
                    <textarea message-key="<?php echo $message['question'];?>" cols='25' rows='7' class="wm-form-control wm-f14 wm-message-input wm-rem-mb15 wm-rem-h107" style="padding:5px;" <?php if('true' === $message['required']) {echo 'required';} ?>></textarea>
            <?php } ?>
        </div>
         <?php } ?>
        <div>
            <button message-board-id="<?php echo $message_board_id;?>" class="w100 wm-btn wm-white wm-bgcolor brad2 message-board-submit wm-rem-h44 wm-rem-f16" type="reset" disabled>提交留言</button>
        </div>
    </form>
</div>

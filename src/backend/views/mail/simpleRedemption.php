<div>
<table bgcolor="#ffffff" border="0" cellpadding="0" cellspacing="0" style="margin:0 auto" width="90%">
    <tbody>
        <tr height="30px">
        </tr>
        <tr height="24px">
            <td width="100%"><font style="font-family:微软雅黑,arial; font-size:18px;font-weight:bold">您好 <?php echo $username;?>, </font></td>
        </tr>
        <tr height="10px">
        </tr>
        <tr height="20px">
            <td width="100%"><font style="font-family:微软雅黑,arial; font-size:14px">谢谢您的兑换，以下为您输入的资料：</font></td>
        </tr>
        <tr height="10px">
        </tr>
        <tr height="20px">
            <td width="100%">
            <table width="100%">
                <tbody style="font-size:16px;text-align:left;font-family:微软雅黑,arial;">
                    <tr>
                        <td>姓名：<?php echo $username;?></td>
                        <td>性别：<?php echo $gender;?></td>
                        <td>邮箱：<?php echo $email;?></td>
                    </tr>
                    <tr>
                        <td>手机：<?php echo $phone;?></td>
                        <td>生日：<?php echo $birthday;?></td>
                        <td> </td>
                    </tr>
                </tbody>
            </table>
            </td>
        </tr>
        <tr height="30px">
        </tr>
        <tr height="20px">
            <td width="100%"><font style="font-family:微软雅黑; font-size:14px">兑换礼品：</font></td>
        </tr>
        <tr height="10px">
        </tr>
        <tr>
            <td width="100%">
            <table width="100%">
                <thead style="font-size:16px;text-align:left;font-family:微软雅黑,arial;">
                    <tr>
                        <th style="border-bottom:1px solid #eee" width="30%">数量</th>
                        <th style="border-bottom:1px solid #eee" width="30%">礼品</th>
                        <th style="border-bottom:1px solid #eee" width="20%">价格</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($datas as $data) { ?>
                    <tr style="border-bottom:1px solid #eee">
                        <td style="border-bottom:1px solid #eee"><font style="font-family:微软雅黑,arial; font-size:14px"><?php echo $data['quantity']; ?></font></td>
                        <td style="border-bottom:1px solid #eee"><font style="font-family:微软雅黑,arial; font-size:14px"><?php echo $data['productName']; ?></font></td>
                        <td style="border-bottom:1px solid #eee"><font style="font-family:微软雅黑,arial; font-size:14px"><?php echo $data['price'];?></font></td>
                    </tr>
                    <?php
}
                    ?>
                </tbody>
            </table>
            </td>
        </tr>
        <tr height="30px">
        </tr>
        <tr height="30px">
            <td><font style="font-family:微软雅黑,arial; font-size:16px; font-weight:bold">敬祝　安好</font></td>
        </tr>
    </tbody>
</table>
</div>

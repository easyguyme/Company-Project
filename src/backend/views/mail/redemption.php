<div>
<table bgcolor="#ffffff" border="0" cellpadding="0" cellspacing="0" width="90%" style="margin:0 auto">
    <tbody>
        <tr height="30px">
        </tr>
        <tr height="24px">
            <td width="100%"><font style="font-family:微软雅黑; font-size:18px;font-weight:bold">商品兑换成功!</font></td>
        </tr>
        <tr height="10px">
        </tr>
        <tr height="20px">
            <td width="100%"><font style="font-family:微软雅黑; font-size:14px"><?php echo $username;?>, 您好</font></td>
        </tr>
        <tr height="20px">
            <td width="100%"><font style="font-family:微软雅黑; font-size:14px">感谢您的购买，您兑换的商品已兑换成功，兑换商品信息如下。</font></td>
        </tr>
        <tr height="30px">
        </tr>
        <tr>
            <td width="100%">
                <table width="100%">
                    <thead style="font-size:16px;text-align:left;font-family:微软雅黑;">
                        <th width="30%" style="border-bottom:1px solid #eee">商品</th>
                        <th width="30%" style="border-bottom:1px solid #eee">商品编码</th>
                        <th width="20%" style="border-bottom:1px solid #eee">数量</th>
                        <th width="20%" style="border-bottom:1px solid #eee">使用积分</th>
                    </thead>
                    <tbody>
                        <?php
                         foreach($datas as $data) {
                            echo  '<tr style="border-bottom:1px solid #eee">
                            <td style="border-bottom:1px solid #eee"><font style="font-family:微软雅黑; font-size:14px">' .$data['productName']. '</font></td>
                            <td style="border-bottom:1px solid #eee"><font style="font-family:微软雅黑; font-size:14px">' .$data['sku']. '</font></td>
                            <td style="border-bottom:1px solid #eee"><font style="font-family:微软雅黑; font-size:14px">' .$data['num']. '</font></td>
                            <td style="border-bottom:1px solid #eee"><font style="font-family:微软雅黑; font-size:14px">' .$data['score']. '</font></td>
                             </tr>';
                        }
                        ?>
                    </tbody>
                </table>
            </td>
        </tr>
         <tr height="30px">
        </tr>
        <tr height="30px">
            <td style="text-align: right"><font style="font-family:微软雅黑; font-size:16px; font-weight:bold">兑换商品使用积分: <?php echo $exchangeScore;?></font></td>
        </tr>
        <tr height="30px">
            <td style="text-align:right"><font style="font-family:微软雅黑; font-size:16px; font-weight:bold">剩余积分: <?php echo $remainScore;?></font></td>
        </tr>
    </tbody>
</table>
</div>

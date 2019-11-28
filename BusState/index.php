<?php
session_start();
include('function.php');
$zbh = $_GET['zbh'];
if (!empty($zbh)){
    $all = findbus($zbh);
    $x = 0;
    foreach ($all as $k1 => $v1)
    {

        $lineId[$x] = findbusline($v1);
        $res[$x] = main($lineId[$x],$v1);
        $x++;
    }
}
?>

<html xmlns="http://www.w3.org/1999/xhtml">
<head id="Head1"><meta http-equiv="Content-Type" content="text/html; charset=UTF-8" /><meta name="viewport" content="width=device-width, initial-scale=1.0, minimum-scale=0.5, maximum-scale=2.0, user-scalable=yes" /><title>
	车辆状态查询
</title><link rel="stylesheet" href="css/themes/default/jquery.mobile-1.1.1.css" /><link rel="stylesheet" href="css/formobile.css" />
    <script type="text/javascript" src="Scripts/jquery-1.8.2.js"></script>

    <script src="Scripts/jquery.mobile-1.2.0.min.js" type="text/javascript"></script>

    <script type="text/javascript">
        function SearchStation() {
            if ($("#registrationmark").val().length > 3)
            { $("#BtnSearch").click(); }
            else
            {
                alert("输入过短！");
            }


        }
    </script>
</head>
<body>

    <form name="form1" method="get" action="" id="form1">

        <div class="ui-header ui-bar-d" role="banner" data-role="header" data-theme="d">
            <h1 class="ui-title" role="heading" aria-level="1">车辆状态查询</h1>
        </div>
        <div class="ui-body">
<form action="/bus/busstate/">
<input name="zbh" id="zbh" type="text" placeholder="请输入自编" />
<i class="search"></i>
</form>

        <p id="needShow">

        

<span id="lbCount" style="color:gray;">Max 10 results showed Only</span><br/><br/><br/><div id="result" style="min-width: 200px">
    
<?php 
    $x = 0;
    //print_r($all);
    foreach ($res as $k1 => $v1)
    {
        echo '<div data-role="collapsible" data-theme="d" data-content-theme="d">'."\n";
        echo  '<h3>'.$all[$x]."</h3>\n";
        echo '<li class="ui-li ui-li-static ui-body-c" style="position: relative"><span>线路名</span><span style="left: 49%; position: absolute">'.get_line_name($lineId[$x]).'</span></li>'."\n";
        echo '<li class="ui-li ui-li-static ui-body-c" style="position: relative"><span>车牌号</span><span style="left: 49%; position: absolute">'.find_plate($all[$x]).'</span></li>'."\n";
        
        foreach ($v1 as $k => $v)
        {
            echo '<li class="ui-li ui-li-static ui-body-c" style="position: relative"><span>'.$k.'</span><span style="left: 49%; position: absolute">'.$v.'</span></li>'."\n";
        }
    $x++;
    echo "                            </ul>\n
                        </p>\n
                    </div>\n
";
    }
?>

    </form>
</body>
</html>

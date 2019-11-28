<?php

/* The version is made by BobLiu0518 and I 've improved some UI issue for her. */

	include('function.php'); //export function package
	$zbh = $_GET['zbh'];
	if(!empty($zbh))
	{
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
<head>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
<meta content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=0;" name="viewport" />
<title>车辆状态查询</title>
<link rel="stylesheet" href="https://cdn.staticfile.org/twitter-bootstrap/4.3.1/css/bootstrap.min.css">
<script src="https://cdn.staticfile.org/twitter-bootstrap/4.3.1/js/bootstrap.min.js"></script>
	<style>
		body {
			padding: 20px
		}
	</style>
</head>

<body>
<div class="container">
<br />
	<h2><b>车辆状态查询</b></h2>
            <div class="search" class="row head">
                <form action="index.php" method="get" class="form-inline">
                    	<div class="col-lg-10">
                        	<div class="input-group">
                            		<input type="text" name="zbh" class="form-control" placeholder="在此自编号" value="<?= $zbh ?>" transparent autofocus x-webkit-speech>
                            		<button type="submit" class="btn btn-primary" style="margin:2px;margin-top:0px">查询</button>
                        	</div>
                    	</div>
                </form>
            </div>
</div>
            
<div class="container">
<?php
	$x = 0;
	if(count($res) == 10)
		echo '<div class="alert alert-info"><strong>注意：</strong>只显示前十条查询结果。</div>';
	else if(!empty($zbh) && count($res) == 0)
		echo '<div class="alert alert-danger"><strong>错误：</strong>没有找到结果，请检查输入是否正确。</div>';
	foreach ($res as $k1 => $v1)
	{
		echo '<h3>'.$all[$x].'</h3>';
		echo '<table class="table table-bordered"><tbody>';
		echo '<tr><th colspan="2">车辆状态</th></tr>'."\n";
		echo '<tr><td>线路名</td><td>'.get_line_name($lineId[$x]).'</td></tr>'."\n";
		echo '<tr><td>车牌号</td><td>'.find_plate($all[$x]).'</td></tr>'."\n";
		foreach ($v1 as $k => $v)
		{
			echo '<tr><td>'.$k.'</td><td>'.$v.'</td></tr>'."\n";
		}
		echo '</tbody></table>';
	$x++;
	}
?>
			</div>
		</div>
	</body>
</html>

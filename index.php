<?php

$user = 'root';
$pwd = '';
$host = 'localhost';
$sys_dbname = 'drc';

$dbh = mysqli_connect($host,$user,$pwd,$sys_dbname) or die ('You need to set your database connection in includes/db.php.</td></tr></table></body></html>');

$stock_history = array();
$modified_array = array();
$path = "unread/sample_data.csv";
 if (($open = fopen("unread/sample_data.csv", "r")) !== FALSE) 
 {  
    $file = basename($path);  
    $sql = "INSERT INTO `csv_read_files` ( `file_name`, `read_date_time`) VALUES ('$file', current_timestamp())";                 
    $result = mysqli_query($dbh , $sql);

   $array = csvToJson($path); 

    $grouped_array = array();
    foreach ($array as $element) {
        $grouped_array[$element["Month/Year"]][] = $element;
    }

    $remaining_stock = 0;
    foreach ($grouped_array as $month_transcation_arr) {
        $temp_arr = array();
        foreach($month_transcation_arr as $data_obj) {
            if ($data_obj["Type"] == "1") {
                $remaining_stock += $data_obj["Qty"];
                $temp_arr["buyQty"] = $data_obj["Qty"];
                $temp_arr["buyTotal"] = $data_obj["Total"];
                $temp_arr["buyRate"] = $data_obj["Rate"];
                array_push($stock_history ,array($data_obj["Qty"], $data_obj["Rate"]));
            }
            if ($data_obj["Type"] == "2") {
                $remaining_stock -= $data_obj["Qty"];
                $temp_arr["sellQty"] = $data_obj["Qty"];
                $temp_arr["sellTotal"] = $data_obj["Total"];
                $temp_arr["sellRate"] = $data_obj["Rate"];
            }
        }
        $temp_arr["Month/Year"] = $month_transcation_arr[0]["Month/Year"];
        $temp_arr["remaining_stock"] = $remaining_stock;
        $sellTotal = $temp_arr["sellTotal"];
        $sellQty = $temp_arr["sellQty"];
        $ptl = 0;
        for($i = 0; $i < count($stock_history); $i++) {
            if ($stock_history[$i][0] == 0) {
                continue;
            }
            if ($stock_history[$i][0] < $sellQty) {
                $ptl += ($stock_history[$i][0] * $stock_history[$i][1]);
                $sellQty -= $stock_history[$i][0];
                $stock_history[$i][0] = 0;
                continue;
            }
            if ($stock_history[$i][0] > $sellQty) {
                $stock_history[$i][0] -= $sellQty;
                $ptl += ($sellQty * $stock_history[$i][1]);
                break;
            }
        }
        $temp_arr["PTL"] = $sellTotal - $ptl; 
        array_push($modified_array, $temp_arr);
    }
   rename($path, "read/sample_data.csv");
   fclose($open);
 }

 function calculateProfitLoss($data) {
    $ptl = 0;
    $stock_history[0][0] -= $data["sellQty"];
    $ptl = $data["sellTotal"] - ($data["sellQty"] * $stock_history[0][1]);
    return $ptl;
 }

 echo "<html><table border='1'>
 <thead>
     <tr>
         <th>Month/Year</th>
         <th colspan='3'>Buy</th>
         <th colspan='3'>Sell</th>
         <th>Remain Stock</th>
         <th>Profit/Loss</th>
     </tr>
     <tr>
         <th></th>
         <th>Qty</th>
         <th>Rate</th>
         <th>Total</th>
         <th>Qty</th>
         <th>Rate</th>
         <th>Total</th>
     </tr>
 </thead>
 <tbody>";
 ?>
<?php
foreach($modified_array as $data) {
    echo "<tr>";
    echo "<td>" . $data['Month/Year'] . "</td>";
    if(isset($data['buyQty'])) {
        echo "<td>" . $data['buyQty'] . "</td>";
    } else {
        echo "<td></td>";
    }
    if(isset($data['buyRate'])) {
        echo "<td>" . $data['buyRate'] . "</td>";
    } else {
        echo "<td></td>";
    }
    if(isset($data['buyTotal'])) {
        echo "<td>" . $data['buyTotal'] . "</td>";
    } else {
        echo "<td></td>";
    }
    echo "<td>" . $data['sellQty'] . "</td>";
    echo "<td>" . $data['sellRate'] . "</td>";
    echo "<td>" . $data['sellTotal'] . "</td>";
    echo "<td>" . $data['remaining_stock'] . "</td>";
    echo "<td>" . $data['PTL'] . "</td>";
    echo "</tr>";
}
echo "</tbody>";
echo "</table>";

?>
</body>

</html>
<?php
 function csvToJson($fname) {
    if (!($fp = fopen($fname, 'r'))) {
        die("Can't open file...");
    }
  
    $key = fgetcsv($fp,"1024",",");
    
    $json = array();
        while ($row = fgetcsv($fp,"1024",",")) {
        $json[] = array_combine($key, $row);
    }

    fclose($fp);
    return $json;
}

?>
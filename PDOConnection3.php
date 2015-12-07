<style>
    table.ol-datagrid{
        border: 1px solid #cccccc;

    }

    table.ol-datagrid td{
        border: 1px solid #cccccc;
        padding: 10px;
    }

    table.ol-datagrid th{
        border: 1px solid #cccccc;
        padding: 10px;
        font-weight: bold;
    }

    form.search{
        border: solid 1px #cccccc;
        padding: 10px;
    }

    form.search div{
        margin: 5px;;
    }

    form.search div label{
        width: 100px;
        display: inline-block;
    }
    form.search div input{
        display: inline-block;
    }



</style>

<?php
$mysqli = new mysqli("localhost", "root", "", "rigsphere");
if ($mysqli->connect_errno) {
    echo "Failed to connect to MySQL: (" . $mysqli->connect_errno . ") " . $mysqli->connect_error;
}

$whereSql=null;
$currentQueryString=null;
/* Generate where condition */
foreach($_REQUEST as  $key=>$val){
   if(!empty($_REQUEST[$key]) && !($_REQUEST[$key]=="order-by" || $_REQUEST[$key]=="order-type")){
       $val2=str_replace("&", "%26", $val);
       $currentQueryString=$currentQueryString.'&'.$key.'='.$val2;
       $s=explode('+',$key);
       var_dump($s);
       if($s[2]=="STR") {
           $whereSql = $whereSql . ''.$s[0].'.'.$s[1].'=\''.$val.'\' or ';
       }else{
           $whereSql = $whereSql . ''.$s[0].'.'.$s[1].'='.$val.' or ';
       }
   }
}
if($whereSql){
    $whereSql='where '.$whereSql;
}

$whereSql=rtrim($whereSql, " or ");
var_dump($whereSql);
$currentQueryString=ltrim($currentQueryString, "&");
var_dump($currentQueryString);
// ORDER BY ///////////////////////////////////////////////////////////////////////////////////////////////////////////
$order_sql=null;
$orderBy=null;
if(isset($_REQUEST['order-by'])){
    $orderBy=$_REQUEST['order-by'];
    if(isset($_REQUEST['order-type'])){
        $orderType=$_REQUEST['order-type'];
        $order_sql="order by ".$orderBy.' '.$orderType;
    }
}
// limit /////////////////////////////////////////////////////////////////////////////////////////////////////////////



$stmt = $mysqli->prepare(" 	Select users.username as 'User Name', users.email as Email, job_seekers.contact_number as 'Contact No.', users.first_name, users.last_name, job_seekers.location, users.id From users Inner Join job_seekers On users.id = job_seekers.id Where users.id > 600");
$stmt->execute();
$res = $stmt->get_result();
$totalRows=$res->num_rows;//Retrieve number of rows
$rows = $res->fetch_all();//total records



$cols = ($res->fetch_fields());

$cols_name = array();
$cols_name_with_table_name=array();
$html_name_with_table_name=array();
$cols_alias = array();
$pk_col = "";
foreach ($cols as $c) {
    array_push($cols_name, $c->orgname);
    array_push($cols_alias, $c->name);
    array_push($cols_name_with_table_name,$c->orgtable.'.'.$c->orgname);
    array_push($html_name_with_table_name,$c->orgtable.'+'.$c->orgname);
    if ($c->flags == MYSQLI_PRI_KEY_FLAG) {
        $pk_col = $c->orgname;
    }
}

/* Started creating dynamic search form*////////////////////////////////////////////////////////////////////////////
var_dump($_REQUEST);
echo '<form class="search" action="PDOConnection3.php">';
$input_fields=array();
foreach ($cols as $c) {

    $type="STR";
    if( $c->type==MYSQLI_TYPE_DECIMAL ||
        $c->type==MYSQLI_TYPE_NEWDECIMAL ||
        $c->type==MYSQLI_TYPE_BIT ||
        $c->type==MYSQLI_TYPE_TINY ||
        $c->type==MYSQLI_TYPE_SHORT ||
        $c->type==MYSQLI_TYPE_LONG ||
        $c->type==MYSQLI_TYPE_LONGLONG ||
        $c->type==MYSQLI_TYPE_INT24 ||
        $c->type==MYSQLI_TYPE_FLOAT ||
        $c->type==MYSQLI_TYPE_DOUBLE){
        $type="NUM";
    }

    $input_field_name=$c->orgtable.'+'.$c->orgname.'+'.$type;
    $value = (!isset($_REQUEST[$input_field_name]) ? "" :$_REQUEST[$input_field_name]);
    $label='<label for="'.$input_field_name.'">'. $c->name.'</label>';
    $input_field='<input type="text" name="'.$input_field_name.'" value="'.$value.'"/>';
    //array_push($input_fields,$label.'.'.$input_field);
    echo '<div>'.$label.' : '.$input_field.'</div>';
}

echo '<div><input type="submit" value="Search"</div>';
echo "</form>";
/*Ends creating dynamic search form*/


/*Started Creating table*////////////////////////////////////////////////////////////////////////////////////////
// Prepare header row of table
$html_table_header_row="<tr>";
$count=0;
foreach($cols_name as $cn){

    if($orderBy==$html_name_with_table_name[$count]) {
        if($orderType="asc") {
            $html_table_header_row = $html_table_header_row . '<th class="asc"><a href="?'.$currentQueryString.'&order-by='.$html_name_with_table_name[$count].'&order-type=DESC">' . $cols_alias[$count] . '</a></th>';
        }else{
            $html_table_header_row = $html_table_header_row . '<th class="desc"><a href="?'.$currentQueryString.'&order-by='.$html_name_with_table_name[$count].'&order-type=ASC">' . $cols_alias[$count] . '</a></th>';
        }
    }else {
        $html_table_header_row = $html_table_header_row . '<th class=""><a href="?' . $currentQueryString . '&order-by=' . $html_name_with_table_name[$count] . '&order-type=ASC">' . $cols_alias[$count] . '</a></th>';
    }
        $count++;
}
$html_table_header_row=$html_table_header_row.'</tr>';




echo '<table class="ol-datagrid">';
echo $html_table_header_row;

// Prepare rows of table
foreach($rows as $r) {
    $html_table__row = "<tr>";
    $count = 0;
    foreach ($r as $d) {
        $html_table__row = $html_table__row . '<td>' . $d . '</td>';
        $count++;
    }
    $html_table__row = $html_table__row . '</tr>';
    echo $html_table__row;
}

echo "</table>";
/*Ended creating table*/
?>

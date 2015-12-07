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

    .pagination-list li{
        list-style: none;
        float: left;
        padding: 7px;
        margin: 5px;
    }

    .pagination-list li .active{
        color : #333;
    }

</style>

<?php

function createLinks( $links, $_total,$_limit,$_page ) {

    $list_class="pagination-list";
    $last       = ceil( $_total / $_limit );
    $start      = ( ( $_page - $links ) > 0 ) ? $_page - $links : 1;
    $end        = ( ( $_page + $links ) < $last ) ? $_page + $links : $last;

    var_dump($last);
    var_dump($start);
    var_dump($end);

    $html       = '<ul class="' . $list_class . '">';

    $class      = ( $_page == 1 ) ? "disabled" : "";
    $html       .= '<li class="' . $class . '"><a href="?limit=' . $_limit . '&page=' . ( $_page - 1 ) . '">&laquo;</a></li>';

    if ( $start > 1 ) {
        $html   .= '<li><a href="?limit=' . $_limit . '&page=1">1</a></li>';
        $html   .= '<li class="disabled"><span>...</span></li>';
    }

    for ( $i = $start ; $i <= $end; $i++ ) {
        $class  = ( $_page == $i ) ? "active" : "";
        $html   .= '<li class="' . $class . '"><a href="?limit=' . $_limit . '&page=' . $i . '">' . $i . '</a></li>';
    }

    if ( $end < $last ) {
        $html   .= '<li class="disabled"><span>...</span></li>';
        $html   .= '<li><a href="?limit=' . $_limit . '&page=' . $last . '">' . $last . '</a></li>';
    }

    $class      = ( $_page == $last ) ? "disabled" : "";
    $html       .= '<li class="' . $class . '"><a href="?limit=' . $_limit . '&page=' . ( $_page + 1 ) . '">&raquo;</a></li>';

    $html       .= '</ul>';


    return $html;
}


$mysqli = new mysqli("localhost", "root", "", "rigsphere");
if ($mysqli->connect_errno) {
    echo "Failed to connect to MySQL: (" . $mysqli->connect_errno . ") " . $mysqli->connect_error;
}

$whereSql=null;
$currentQueryString=null;
/* Generate where condition */
foreach($_REQUEST as  $key=>$val){
    var_dump($key);
   if(!empty($key) && !($key=="order-by" || $key=="order-type" || $key=="limit" || $key=="page") && !empty($val)){
       $val2=str_replace("&", "%26", $val);
       $currentQueryString=$currentQueryString.'&'.$key.'='.$val2;

       $s=explode('+',$key);
       //var_dump($s);
       if(isset($s[2])&& $s[2]=="STR") {
           $whereSql = $whereSql . ''.$s[0].'.'.$s[1].'=\''.$val.'\' or ';
       }else if(isset($s[1])){
           $whereSql = $whereSql . ''.$s[0].'.'.$s[1].'='.$val.' or ';
       }else{
           $whereSql = $whereSql . ''.$s[0].'='.$val.' or ';
       }
   }
}
if($whereSql){
    $whereSql='where '.$whereSql;
}

$whereSql=rtrim($whereSql, " or ");
var_dump($whereSql);
$currentQueryString=ltrim($currentQueryString, "&");

// ORDER BY ///////////////////////////////////////////////////////////////////////////////////////////////////////////
$order_sql=null;
$orderBy=null;
$orderType="ASC";
if(isset($_REQUEST['order-by'])){
    $orderBy=$_REQUEST['order-by'];
    if(isset($_REQUEST['order-type'])){
        $orderType=$_REQUEST['order-type'];
        $order_sql="order by ".$orderBy.' '.$orderType;
        //$currentQueryString=$currentQueryString.'&order-by='.$orderBy.'&order-type='.$orderType;
    }
}



// LIMIT /////////////////////////////////////////////////////////////////////////////////////////////////////////////

/*Pagination ------------------------------------- */
$limit=10;
$page=0;
$limit_sql="";
if(isset($_REQUEST['page']) && isset($_REQUEST['limit'])){
    $page=$_REQUEST['page'];
    $limit=$_REQUEST['limit'];
    if($limit<=0) $limit=10;
    if($page<0) $page=0;
    $limit_sql="LIMIT ".($limit*$page).' '.$limit;
    $currentQueryString=$currentQueryString.'&page='.$page.'&limit='.$limit;
}
/*Pagination --------------------------------------*/

var_dump($currentQueryString);

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

/* Started creating dynamic search form *///////////////////////////////////////
var_dump($_REQUEST);
echo '<form class="search" action="">';
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
    var_dump($html_name_with_table_name[$count]);
    var_dump($currentQueryString);
    $queryString=explode("&page=",$currentQueryString)[0];

    if($orderBy==$html_name_with_table_name[$count]) {
        if($orderType=="ASC") {
            $html_table_header_row = $html_table_header_row . '<th class="asc"><a href="?'.$queryString.'&order-by='.$html_name_with_table_name[$count].'&order-type=DESC">' . $cols_alias[$count] . '</a></th>';
        }else{
            $html_table_header_row = $html_table_header_row . '<th class="desc"><a href="?'.$queryString.'&order-by='.$html_name_with_table_name[$count].'&order-type=ASC">' . $cols_alias[$count] . '</a></th>';
        }
    }else {
        $html_table_header_row = $html_table_header_row . '<th class=""><a href="?' .$queryString. '&order-by=' . $html_name_with_table_name[$count] . '&order-type=ASC">' . $cols_alias[$count] . '</a></th>';
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
/*start pagination*/

//echo createLinks($links, $list_class,$_total,$_limit,$_page );

echo createLinks(5,400,10,20 );
/*End pagination*/

?>

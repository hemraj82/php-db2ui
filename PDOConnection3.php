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

function createLinks( $links, $_total,$_limit,$_page,$currentQueryString ) {

    if($_total<=$_limit)
    {
        return "";
    }

    $list_class="pagination-list";
    $last       = ceil( $_total / $_limit );
    $start      = ( ( $_page - $links ) > 0 ) ? $_page - $links : 1;
    $end        = ( ( $_page + $links ) < $last ) ? $_page + $links : $last;


    $html       = '<ul class="' . $list_class . '">';

    $class      = ( $_page == 1 ) ? "disabled" : "";
    $html       .= '<li class="' . $class . '"><a href="?'.$currentQueryString.'&limit=' . $_limit . '&page=' . ( $_page - 1 ) . '">&laquo;</a></li>';

    if ( $start > 1 ) {
        $html   .= '<li><a href="?limit=' . $_limit . '&page=1">1</a></li>';
        $html   .= '<li class="disabled"><span>...</span></li>';
    }

    for ( $i = $start ; $i <= $end; $i++ ) {
        $class  = ( $_page == $i ) ? "active" : "";
        $html   .= '<li class="' . $class . '"><a href="?'.$currentQueryString.'&limit=' . $_limit . '&page=' . $i . '">' . $i . '</a></li>';
    }

    if ( $end < $last ) {
        $html   .= '<li class="disabled"><span>...</span></li>';
        $html   .= '<li><a href="?limit=' . $_limit . '&page=' . $last . '">' . $last . '</a></li>';
    }

    $class      = ( $_page == $last ) ? "disabled" : "";
    $html       .= '<li class="' . $class . '"><a href="?'.$currentQueryString.'&limit=' . $_limit . '&page=' . ( $_page + 1 ) . '">&raquo;</a></li>';

    $html       .= '</ul>';


    return $html;
}


$mysqli = new mysqli("localhost", "root", "", "rigsphere");
if ($mysqli->connect_errno) {
    echo "Failed to connect to MySQL: (" . $mysqli->connect_errno . ") " . $mysqli->connect_error;
}

$whereSql=null;
$and_or='and';
$currentQueryString=null;
/* Generate where condition */
foreach($_REQUEST as  $key=>$val){

   if(!empty($key) && !($key=="order-by" || $key=="order-type" || $key=="limit" || $key=="page") && !empty($val)){

       $s=explode('+',$key);

       if ($s[sizeof($s)-1]=="STR"){
           $whereSql = $whereSql . ''.$s[0].'.'.$s[1].'=\''.$val.'\' '.$and_or.' ';
       }else{
           $whereSql = $whereSql . ''.$s[0].'.'.$s[1].'='.$val.' '.$and_or.' ';
       }
       $val2=urlencode($val);
       $key=urlencode($key);
       $currentQueryString=$currentQueryString.'&'.$key.'='.$val2;
   }
}
if($whereSql){
    $whereSql='where '.$whereSql;
}

$whereSql=rtrim($whereSql, " ".$and_or." ");
$currentQueryString=ltrim($currentQueryString, "&");

// ORDER BY ///////////////////////////////////////////////////////////////////////////////////////////////////////////
$order_sql="";
$orderBy=null;
$orderType="ASC";
if(isset($_REQUEST['order-by'])){
    $orderBy=$_REQUEST['order-by'];
    $orderBy = str_replace(" ", "+", $orderBy);
    //echo "<hr/>Order by values = ".$orderBy;
    if(isset($_REQUEST['order-type'])){
        $orderType=$_REQUEST['order-type'];
        $order_sql="order by ".str_replace("+", ".", $orderBy).' '.$orderType;
        $currentQueryString=$currentQueryString.'&order-by='.$orderBy.'&order-type='.$orderType;
    }
}



// LIMIT /////////////////////////////////////////////////////////////////////////////////////////////////////////////

/*Pagination ------------------------------------- */
$limit=10;
$page=0;
$limit_sql="";
$page=isset($_REQUEST['page'])?$_REQUEST['page']:0;
$limit=isset($_REQUEST['limit'])?$_REQUEST['limit']:10;
$page=$page-1;
if($limit<=0) $limit=10;
if($page<0) $page=0;
$limit_sql="LIMIT ".($limit*$page).','.$limit;
//$currentQueryString=$currentQueryString.'&page='.$page.'&limit='.$limit;
/*Pagination --------------------------------------*/

/*This is the original sql command to be passed as parameters*/
$sqlQuery="Select users.username as 'User Name', users.email as Email, job_seekers.contact_number as 'Contact No.', users.first_name, users.last_name, job_seekers.location, users.id From users Inner Join job_seekers On users.id = job_seekers.id";
/* Now modify the actual sql with search parameters*/
if (strpos(strtolower($sqlQuery),'where') == true && !empty($whereSql)){
    $sqlQuery=$sqlQuery.' '.$and_or.''.ltrim($whereSql,"where").' '.$order_sql;;
}else{
    $sqlQuery=$sqlQuery.' '.$whereSql.' '.$order_sql;
}


// Retrieve total records against this query
$sqlTotalRecords='select count(*) from '.explode("from",strtolower($sqlQuery))[1];

$stmt = $mysqli->prepare($sqlTotalRecords);
$stmt->execute();
$res1 = $stmt->get_result();
$totalRecordsFound=$res1->fetch_all()[0][0];

// Add limit for pagnation
$sqlQuery=$sqlQuery.' '.$limit_sql;
echo $sqlQuery;
// Execute actual query for fetching the records.
$stmt = $mysqli->prepare($sqlQuery);
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

echo createLinks(5,$totalRecordsFound,$limit,$page,$currentQueryString);
/*End pagination*/

?>

<?php
/**
 * Created by PhpStorm.
 * User: kemal kanok
 * Date: 18/10/14
 * Time: 23:20
 */

$data = include 'app/config/database.php';
$index = "mysql";
$user = $data["connections"][$index]["username"];
$pass = $data["connections"][$index]["password"]; 
$host = $data["connections"][$index]["host"];
$db = $data["connections"][$index]["database"];

$dbh = new PDO("mysql:dbname=".$db.";host=".$host,$user,$pass);

$sth = $dbh->prepare("select table_name , column_name from information_schema.columns
where table_schema = ?
order by table_name,ordinal_position");

$sth->execute([$db]);

$result = $sth->fetchAll();

$data = [];
foreach ($result as $key => $value) {
	if($value["table_name"] != "migrations" && !in_array($value["column_name"], ["updated_at","created_at" , "id"]) )
	{
		$data[$value["table_name"]][]=$value;
	}
}

foreach ($data as $key => $value) {
	# code...
	$string = '<?php
class '.$key.' extends \Eloquent {
    public $table = "'.$key.'";
    ';


  $string .='
    public static function remove($id)
    {
  	    $obj = '.$key.' :: find($id);
  	    if(count($obj) > 0)
  	    {
		    return $obj -> delete();
  	    }
    }

    public static function getOne($id)
    {
  	    return '.$key.'::find($id);
    }
  
    public static function getList()
    {
  	    return '.$key.'::get();
    }
';
    foreach ($value as $k => $v) {
 $string .=  '
    public static function update'.$v[1].'($id , $'.$v[1].')
    {
  	     $obj = '.$key.'::getOne($id);
  	     if(count($obj) > 0)
  	     {
   		     $obj -> '.$v[1].' = $'.$v[1].';
  		     $obj -> save();
  	     }
    }
   ';
    	
    }
 $string .=  '  
    public static function insert(';
			$args = "";
			foreach ($value as $k => $v) {
	        	$args.= '$'.$v[1].', ';
	        }
	        $args = substr($args, 0,-2);
	        $string .= $args;
			$string .=')
    {
        $obj = new '.$key.'();
      ';
  	        foreach ($value as $k => $v) {
  	        	$string .= '  $obj -> '.$v[1].' = $'.$v[1].';
      ';
  	        }
  	          
  	        $string.= '  $obj -> save();
        return $obj -> id;
    }

}';
$fh = fopen("app/models/$key.php", "w+");
fwrite($fh, $string);
}
echo "done";
//print_r($result);






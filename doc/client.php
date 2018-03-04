<?php
include 'class_przelewy24.php';
?><!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en">
<head>
<title>Przelewy24 - panel testowy</title>
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-2" />
<meta name="language" content="PL" />
<meta name="language" content="PL" />
<meta name="Copyright" content="Wszystkie prawa zastrzezone 2014 DialCom24 Sp. z o.o." />
<meta NAME="Robots" content="ALL" />
<meta NAME="Language" content="pl" />
<meta NAME="Classification" content="Internet Services" />
<meta http-equiv="Reply-to" content="info@przelewy24.pl" />
<style>
.serwer td{border:1px #999 solid; text-align:center; padding:5px;}
</style>
</head>
<body>
<h2>Panel testowy do usługi Przelewy24</h2>
<h3>Wersja <?php echo P24_VERSION; ?></h3>
<?php

session_start();

//Fragment kodu odpowiedzialny za weryfikacje transakcji.
if($_GET["ok"]==2)
{
    if(file_exists ("parametry.txt")){
        $result = file_get_contents("parametry.txt");
        
        $X = explode("&", $result);
              	
     	foreach($X as $val) {
                $Y = explode("=", $val);
                $FIL[trim($Y[0])] = urldecode(trim($Y[1]));
                 	}
        
        $P24 = new Przelewy24($_POST["p24_merchant_id"],$_POST["p24_pos_id"],$FIL['p24_crc'],$FIL['env']);
        
        foreach($_POST as $k=>$v) $P24->addValue($k,$v);  
        
        $P24->addValue('p24_currency',$FIL['p24_currency']);
        $P24->addValue('p24_amount',$FIL['p24_amount']);
        $res = $P24->trnVerify();
        if(isset($res["error"]) and $res["error"] === '0')
            {
                $msg = 'Transakcja została zweryfikowana poprawnie';
            }
        else{
                $msg = 'Błędna weryfikacja transakcji';
        }
    }
    else{
           $msg = 'Brak pliku parametry.txt';
    }
    
    file_put_contents("weryfikacja.txt",date("H:i:s").": ".$msg." \n\n",FILE_APPEND);
    exit;
}


if(isset($_POST["submit_test"])) {
    echo '<h2>Wynik:</h2>';
    $test = ($_POST["env"]==1?true:false);
    $salt = $_POST["salt"];
    $P24 = new Przelewy24($_POST["p24_merchant_id"],
                            $_POST["p24_pos_id"],
                            $salt,
                            $test
                            );
                            
    $RET = $P24->testConnection();
    echo '<pre>RESPONSE:'.print_r($RET,true).'</pre>';                            

}elseif(isset($_POST["submit_send"])) {
    echo '<h2>Wynik:</h2>';
    $test = ($_POST["env"]==1?"1":"0");
    $salt = $_POST["salt"];
    
    $P24 = new Przelewy24($_POST["p24_merchant_id"],
                            $_POST["p24_pos_id"],
                            $salt,
                            $test);
   
    foreach($_POST as $k=>$v) $P24->addValue($k,$v);                            
    
    file_put_contents("parametry.txt","p24_crc=".$_POST['salt']."&p24_amount=".$_POST['p24_amount']."&p24_currency=".$_POST['p24_currency']."&env=".$test);

    
    $bool = ($_POST["redirect"]=="on")? true:false;
    $res = $P24->trnRegister($bool);
    
    echo '<pre>RESPONSE:'.print_r($res,true).'</pre>';
    
    if(isset($res["error"]) and $res["error"]==='0') {

        echo '<br/><a href="'.$P24->getHost()."trnRequest/".$res["token"].'">'.$P24->getHost()."trnRequest/".$res["token"].'</a>';
        
        
    }
    
}


$protocol = ( isset($_SERVER['HTTPS'] )  && $_SERVER['HTTPS'] != 'off' )? "https://":"http://";  
session_regenerate_id();
?>
<h2>Formularz żądania transakcji:</h2>
<form action="client.php" method="post" class="form" id="fformn">
<table class="serwer">
<tr><td>Serwer</td><td>TrnRegister</td><td>TrnDirect</td></tr>
<tr>
  <td>Sandbox</td>
  <td><input type="radio" onclick="document.getElementById('fformn').action='client.php'" name="env" value="1" checked /></td>
  <td><input type="radio" onclick="document.getElementById('fformn').action='https://sandbox.przelewy24.pl/trnDirect'" name="env" value="2"  /></td>
</tr>
<tr>
  <td>Live</td>
  <td><input type="radio" onclick="document.getElementById('fformn').action='client.php'" name="env" value="2"  /></td>
  <td><input type="radio" onclick="document.getElementById('fformn').action='https://secure.przelewy24.pl/trnDirect'" name="env" value="4"  /></td>
</tr>
</table>
<table>
<tr><td>CRC_key</td><td><input type="text" style="width:250px" name="salt" value="" /></td></tr>
<tr><td>Redirect</td><td><input type="checkbox" name="redirect" /><span>Zaznaczenie checkboxa powoduje automatyczne przekierowanie na stronę trnRequest.</span></td></tr>
<tr><td>p24_merchant_id</td><td><input type="text" style="width:250px" name="p24_merchant_id" value="" /></td></tr>
<tr><td>p24_pos_id</td><td><input type="text" style="width:250px" name="p24_pos_id" value="" /></td></tr>
<tr><td>p24_session_id</td><td><input type="text" style="width:250px" name="p24_session_id" value="<?php echo md5(session_id().date("YmdHis")); ?>" /></td></tr>
<tr><td>p24_amount</td><td><input type="text" style="width:250px" name="p24_amount" value="512" /></td></tr>
<tr><td>p24_currency</td><td><input type="text" style="width:250px" name="p24_currency" value="PLN" /></td></tr>
<tr><td>p24_description</td><td><input type="text" style="width:250px" name="p24_description" value="Zamówienie testowe" /></td></tr>
<tr><td>p24_email</td><td><input type="text" style="width:250px" name="p24_email" value="no-reply@przelewy24.pl" /></td></tr>
<tr><td>p24_client</td><td><input type="text" style="width:250px" name="p24_client" value="Jan Kowalski" /></td></tr>
<tr><td>p24_address</td><td><input type="text" style="width:250px" name="p24_address" value="ul. Kwiatowa 13" /></td></tr>
<tr><td>p24_zip</td><td><input type="text" style="width:250px" name="p24_zip" value="60-111" /></td></tr>
<tr><td>p24_city</td><td><input type="text" style="width:250px" name="p24_city" value="Poznań" /></td></tr>
<tr><td>p24_country</td><td><input type="text" style="width:250px" name="p24_country" value="PL" /></td></tr>
<tr><td>p24_phone*</td><td><input type="text" style="width:250px" name="p24_phone" value="611111111" /></td></tr>
<tr><td>p24_language*</td><td><input type="text" style="width:250px" name="p24_language" value="PL" /></td></tr>
<tr><td>p24_method*</td><td><input type="text" style="width:250px" name="p24_method" value="" /></td></tr>
<tr><td>p24_url_return</td><td><input type="text" style="width:250px" name="p24_url_return" value="<?echo $protocol.$_SERVER['HTTP_HOST'].$_SERVER['SCRIPT_NAME']."?ok=1"?>" />Podaj adres w przypadku powrotu po zakończeniu transakcji. (http://nazwa_domeny/sample/client.php?ok=1)</td></tr>
<tr><td>p24_url_status</td><td><input type="text" style="width:250px" name="p24_url_status" value="<?echo $protocol.$_SERVER['HTTP_HOST'].$_SERVER['SCRIPT_NAME']."?ok=2"?>" />Podaj adres skryptu weryfikującego transakcję.(http://nazwa_domeny/sample/client.php?ok=2)</td></tr>
<tr><td>p24_time_limit*</td><td><input type="text" style="width:250px" name="p24_time_limit" value="29" /></td></tr>
<tr><td>p24_wait_for_result*</td><td><input type="text" style="width:250px" name="p24_wait_for_result" value="1" /></td></tr>
<tr><td>p24_ecod*</td><td><input type="text" style="width:250px" name="p24_ecod" value="" /></td></tr>
<tr><td>p24_shipping*</td><td><input type="text" style="width:250px" name="p24_shipping" value="2500" /></td></tr>
<tr><td>p24_name_1*</td><td><input type="text" style="width:250px" name="p24_name_1" value="Pizza" /></td></tr>
<tr><td>p24_description_1*</td><td><input type="text" style="width:250px" name="p24_description_1" value="Smaczna, zdrowa..." /></td></tr>
<tr><td>p24_quantity_1</td><td><input type="text" style="width:250px" name="p24_quantity_1" value="2" /></td></tr>
<tr><td>p24_price_1*</td><td><input type="text" style="width:250px" name="p24_price_1" value="1250" /></td></tr>
<tr><td>p24_number_1*</td><td><input type="text" style="width:250px" name="p24_number_1" value="1367" /></td></tr>
<tr><td>p24_transfer_label*</td><td><input type="text" style="width:250px" name="p24_transfer_label" value="MyStore" /></td></tr>
<tr><td>p24_api_version</td><td><input type="text" style="width:250px" name="p24_api_version" value="<?php echo P24_VERSION; ?>" /></td></tr>
</table>
<input name="submit_test" value="test connection" type="submit" />
<input name="submit_send" value="send" type="submit" />
</form>
* - opcjonalne

</body>
</html>
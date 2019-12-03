#!/usr/bin/php -q
&lt;?php
 
// Cambiar esta constante por el codigo de area local desde donde se originan las llamadas
define('LOCAL','55');
 
// Parametros de acceso a la base de datos.
$db['user'] = 'asteriskuser';
$db['pass'] = 'asteriskpass';
$db['name'] = 'asterisk';
$db['host'] = 'sql.enlaza.mx';
 
 
// ***************** No cambiar nada a partir de este punto
 
require_once &quot;phpagi.php&quot;;
$agi = new AGI();
 
 
 
if ( (!is_numeric($argv[1])) || (strlen($argv[1]) &lt; 10) ) {
    $agi-&gt;verbose('No se proporciono un numero de 10 digitos');
    $agi-&gt;set_variable('MOVIL',0);
    $agi-&gt;set_variable('PREFIJO','');
    $agi-&gt;set_variable('COMPLETO','');
    exit;
}
 
// Nos quedamos solo con los ultimos 10 digitos para asegurar que quitamos cualquier prefijo
$numero = substr($argv[1],-10);
 
if (!$data = mysql_connect($db['host'],$db['user'],$db['pass'])) {
    $agi-&gt;verbose('Error de conexion a la BD');
    $agi-&gt;set_variable('MOVIL',0);
    $agi-&gt;set_variable('PREFIJO','');
    $agi-&gt;set_variable('COMPLETO','');
    exit;
}
 
// Definimos codigos de area de 2 digitos para conocer cual es el codigo de area y cual es el numero local
$areas = array('55','81','33');
if (in_array(substr($numero,0,2),$areas)) {
    $area = substr($numero,0,2); 
    $local= substr($numero,2); 
    $serie= substr($local,0,4);
}
else {
    $area = substr($numero,0,3);
    $local= substr($numero,3); 
    $serie= substr($local,0,3);
}
 
$query = &quot;SELECT movil FROM &quot;.$db['name'].&quot;.ift WHERE SUBSTRING('$numero',7) BETWEEN inicial AND final AND `area` = $area AND serie = $serie LIMIT 1&quot;;
$result = mysql_query($query,$data);
$row = mysql_fetch_array($result);
$agi-&gt;set_variable('MOVIL',$row['movil']);
 
if (substr($numero,0,strlen(LOCAL)) == LOCAL) {
    if ($row['movil'] == 1) {
        $agi-&gt;set_variable('PREFIJO','044');
        $agi-&gt;set_variable('COMPLETO', '044'.$numero);
    }
    else {
        $agi-&gt;set_variable('PREFIJO','');
        $agi-&gt;set_variable('COMPLETO', $local);
    }
}
else {
    if ($row['movil'] == 1) {
        $agi-&gt;set_variable('PREFIJO','045');
        $agi-&gt;set_variable('COMPLETO', '045'.$numero);
    }
    else {
        $agi-&gt;set_variable('PREFIJO','01');
        $agi-&gt;set_variable('COMPLETO', '01'.$numero);
    }
}
exit;
?&gt;

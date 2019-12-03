# agiexample

En México tenemos diferentes mecanismos de marcación de un número, dependiendo si este es fijo (01) o celular (044 o 045). Esto complica un poco las cosas, ya que el identificador de llamadas (callerid) siempre es de 10 dígitos, por lo que si recibimos una llamada de un número X, no podemos simplemente devolver la llamada a este número ya que tenemos que prefijarlo según el tipo de teléfono destino que es

Afortunadamente, el Instituto Federal de Telecomunicaciones (IFT) publica una base de datos que determina si una serie telefónica corresponde a un número móvil o fijo. Dado que aquí los teléfonos los marcamos diferentes dependiendo del tipo de línea, es muy importante tener presente siempre que tipo de teléfono es el que estamos llamando para ajustar nuestro plan de marcación acorde.

Esto resulta muy conveniente de hacer en sistemas de marcación predictiva donde las listas pueden estar capturadas a 10 dígitos y nosotros convertir cada número a fijo o móvil según la base nos lo diga. En Asterisk, esta funcionalidad es muy fácil de implementar con una base de datos, una función personalizada de ODBC (func_odbc) o un AGI. Aquí te dejo la guía de como lograrlo utilizando un AGI.

**Antes de comenzar, asumimos lo siguiente:**

Que ya tienes instalado PHP en tu sistema.
Que ya tienes instalado y cuentas con experiencia con MySQL y que sabes como importar tablas a partir de un archivo SQL.
Que la base de datos de nombre asterisk ya está creada.
Que entiendes el plan de marcación básico.
Dando esto por sentado, comencemos:


**Paso 1. Consigue el listado de numeración del IFT**

Limpiar la lista y dejar solamente estos 5 campos:

Código de área (int)
Serie (int)
Numeración inicial (int)
Numeración final (int)
Móvil (bool)

Puedes usar el archivo ya en formato SQL listo para importar dentro del repositorio 

Una vez descargado, descomprímelo e impórtalo en MySQL. En este tutorial asumiremos que la tabla se creará en la base de datos asterisk. Si lo quieres poner en una base de datos diferente, edita el archivo SQL y apúntalo a donde necesites.

Para proceder, necesitas la contraseña de root de MySQL:

mysql -p asterisk &lt; bdift_safe.sql

Esto creará la tabla ift en la base de datos asterisk. Ahora crearemos el AGI para poder consultarla


**Paso 2. Crea el AGI ift.php**



Para el siguiente paso, crearemos un AGI que se encargue de consultar la tabla creada en el paso anterior. El AGI lo crearemos con el nombre /var/lib/asterisk/agi-bin/ift.php
function test() {
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
}

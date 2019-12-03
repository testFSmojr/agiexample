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

ift.php  se encuentra en el repositorio. 

**Para que funcione es necesario cambiar:

-La constante LOCAL. Reemplázala por el código de área de tu población.
-Los parámetros de conexión a tu base de datos.

**El AGI es bastante sencillo y lo que hace es:

1.Recibe como argumento un número de 10 dígitos. No debe tener más ni menos.
2.Descompone ese número y extrae el código de área, ya sea de 2 dígitos (México, Guadalajara o Monterrey) o de 3 dígitos (resto del país) y la serie del número local.
3.Compara estos números contra la base de datos creada.
 -Si encuentra el registro, lo cataloga de acuerdo al campo movil de la tabla.
 -Si no lo encuentra, lo cataloga como fijo.
4.Para la entrega de resultados, el AGI crea las siguientes variables:
 -${MOVIL}. Es 0 o 1, dependiendo si el número se considera fijo o móvil, respectivamente.
 -${PREFIJO} es el prefijo de marcación: 01, 044 o 045.
 -${COMPLETO} contiene el número tal cual se tiene que marcar de acuerdo al plan de numeración de México. Es la combinación de ${PREFIJO} y de ${EXTEN}. Si el número es fijo y está en el mismo código de área que el AGI, entonces te lo entrega en 7 dígitos.

**Ejemplos de corrida. Asumimos que la variable LOCAL es configurada con 33 (Guadalajara):

1.AGI(ift.php,5546144400) te da:
 -${MOVIL} es 0, ya que el número es fijo.
 -${PREFIJO} es 01, ya que es considerado larga distancia nacional.
 -${COMPLETO} es 013346144400
 -AGI(ift.php,5513208860) te da:
 -${MOVIL} es 1, ya que el número es celular.
 -${PREFIJO} es 045, ya que es considerado celular con LDN.
 -${COMPLETO} es 0455513208860.
 
 **Paso 3. Crea el plan de marcación
 
El último paso es utilizar las variables que el AGI crea para hacer la marcación. El plan de marcación se colocará en el extensions.conf de /etc/asterisk, y será algo más o menos así:

***
exten =&gt; _NXXXXXXXXX,1,AGI(ift.php,${EXTEN})
same =&gt; n,Dial(DAHDI/g0/${COMPLETO},,Tt)
***



<?php
/*****
	*	ejemplo.php
	*	ejemplos de motorPlantilla
	*	Copyright (c) 2009 Leonardo Montilla G. <leonardomontilla@itc.com.ve
	**/

/***
	*	directorio de clase
	*	cambie el valor de esta variable para ajustarlo a su instalación
	**/
	$classdir = '../';
	
/***
	*	clase según versión PHP
	**/
	require_once($classdir.'class.motorPlantilla.php');

/***
	*	instancia
	*	usa la plantilla ejemplo.tpl
	**/
	$mt = new motorPlantilla('ejemplo.tpl');
	
/***
	*	¿se definieron las constantes?
	**/
	echo 'Variables globales: '.MT_GLOBAL.'<br />';
	echo 'Bloque raíz: '.MT_RAIZ.'<br />';
	echo 'Plantilla: '.MT_TPL.'<br />';
	echo 'Página HTML: '.MT_HTML.'<br />';
	echo 'Sección BODY: '.MT_BODY.'<br />';
	
?>
<?php
/******
	* motorPlantilla.php
	*	clase de motor de plantillas HTML
	*	(a) 2004-2011 Leonardo Montilla G. <leonardomontilla@itc.com.ve>
	*
	*	Este programa es software libre: usted puede redistribuirlo y/o modificarlo bajo términos de
	*	la Licencia Pública General GNU publicada por la Fundación para el Software Libre, ya sea la
	*	versión 3 de la Licencia, o (a su elección) cualquier versión posterior.
	*
	*	Este programa se distribuye con la esperanza de que sea útil, pero SIN GARANTIA ALGUNA; ni
	*	siquiera la garantía implícita MERCANTIL o de APTITUD PARA UN PROPOSITO DETERMINADO.
	*	Consulte los detalles de la Licencia Pública General GNU para información detallada.
	*
	*	Debería haber recibido una copia de la Licencia Pública General GNU junto con este programa,
	*	en caso contrario, consulte <http://www.gnu.org/licenses/>
	**/
  
/*****
	*	clase motorPlantilla
	*	--------------------
	*
	*	Propiedades:
	*	Esta clase no declara propiedades públicas
	*
	*	Constantes:
	*	MT_GLOBAL		      : identificador de variables globales
	*	MT_RAIZ			      : apuntador a bloque raíz
	*	MT_TPL			      : tipo de documento "plantilla"
	*	MT_HTML			      : tipo de documento "html"
	*	MT_BODY			      : tipo de documento "html" parcial (solo BODY)
	*
	*	Métodos:
	*	motorPlantilla()	: constructor, crea un objeto a partir de una plantilla
	*	usar()			      : usa un bloque
	*	asignar()		      : asigna valores a variables
	*	cargar()		      : carga un documento o plantilla a un bloque
	*	ira()			        : ubica el puntero interno en un bloque activo
	*	mostrar()		      : muestra el documento final
	*	obtener()		      : devuelve una cadena con el documento final
	*	tiempo()		      : devuelve el tiempo de ejecución del script
	*	traducir()	      : carga y ejecuta traductor de cadenas
	**/
  
/***
	*	constantes
  * v2.0.2
	**/
	if(!defined("MT_GLOBAL"))
		define("MT_GLOBAL","_MTGLOB_");		// variables globales
	if(!defined("MT_RAIZ"))
		define("MT_RAIZ","_MTRAIZ_");			// bloque raíz
	if(!defined("MT_TPL"))
		define("MT_TPL",0);						    // documento plantilla
	if(!defined("MT_HTML"))
		define("MT_HTML",1);						  // documento html
	if(!defined("MT_BODY"))
		define("MT_BODY",2);						  // documento html (solo body)


/***
	*	clase motorPlantilla
	**/

class motorPlantilla {

	/* propiedades privadas **/
	private $_mt_dirt;										// directorio de plantillas
	private $_mt_tpls;										// array de plantillas (archivos) cargados
	private $_mt_blqs;										// array de documentos cargados (bloques)
	private $_mt_blqp;										// array de parientes de bloques
	private $_mt_blqt;										// array de bloques temporales
	private $_mt_varg;										// array de variables globales
	private $_mt_acti;										// id de bloque activo
	private $_mt_tini;										// tiempo de inicio
	private $_mt_lang;										// idioma de plantilla
	
	/* Etiquetas de bloques **/
	private $_etq_ini = "BINI";							// inicio de bloque
	private $_etq_fin = "BFIN";							// fin de bloque
	private $_etq_asg = "BASG";							// bloque para asignación
	private $_etq_inc = "BINC";							// bloque de inclusión automática
	private $_etq_dci = "<!-- DOCI -->";				// inicio de documentación
	private $_etq_dcf = "<!-- DOCF -->";				// fin de documentación
	
	/***
		* para modificaciones futuras
		* trabajar los delimitadores de variable e idioma
		*
		* private $_etq_vini = "{";
		* private $_etq_vfin = "}";
		* private $_etq_lini = "[";
		* private $_etq_lfin = "]";
		*
		**/
		
	
	/***
		*	__construct( str_documento> [, <str_opcion> ] ) -> void
		*	constructor de clase
		*	@public
		**/
		public function __construct($tpldoc="",$opcdoc=0) {
			if(!$tpldoc) {
				$this->PRIV_mtError("motorPlantilla()","Se requiere <i>plantilla o documento</i> a utilizar");
			}
			if($opcdoc != 0 || $opcdoc != 1 || $opcdoc != 2) {
				$opcdoc = 0;
			}
			$this->_mt_tini = microtime(true);
			$this->_mt_dirt = "";
			$this->_mt_tpls = array();
			$this->_mt_blqs = array();
			$this->_mt_blqp = array();
			$this->_mt_blqt = array();
			$this->_mt_acti = '_MTRAIZ_';
			$this->_mt_varg = array();
			$tmp = $this->PRIV_mtIsfile($tpldoc);
			if( !$tmp ) {
				$srcdoc = "V";
				$tpldoc .= "\n";
			} else {
				$srcdoc = "F";
				$tpldoc = $tmp;
			}
			if($opcdoc == 0) {
				$this->PRIV_mtParsetpl($tpldoc,'_MTRAIZ_',$srcdoc);
			} else {
				$this->_mt_blqs['_MTRAIZ_'] = $this->PRIV_mtParsehtm($tpldoc,$srcdoc,$opcdoc);
			}
			return;
		} //	eof:__construct

	/***
		*	usar( <etiqueta_bloque> [, <hash_variables> ] ) -> void
		*	prepara un bloque para su utilización y adicionalmente puede asignar valores a sus variables
		*	@public
		**/
		public function usar($blqn="",$vars=array()) {
			if(!$blqn) {
				$this->PRIV_mtError("usar()","No se especificó <i>nombre de bloque</i> a usar");
			}
			if($blqn == '_MTRAIZ_') {
				$this->PRIV_mtError("usar()","No puede usarse el <i>bloque raíz</i>");
			}
			if(!array_key_exists($blqn,$this->_mt_blqs)) {
				$this->PRIV_mtError("usar()","El bloque <i>$blqn</i> no existe");
			}
			$parent = $this->_mt_blqp[$blqn];
			if( $parent !== '_MTRAIZ_' && !array_key_exists($parent,$this->_mt_blqt) ) {
				$this->PRIV_mtError("usarbloque()","El bloque <i>$blqn</i> pertenece a otro (<i>$parent</i>) que no está en uso");
			}
			if(array_key_exists($blqn,$this->_mt_blqt)) {
				$this->PRIV_mtParseblq($blqn,$parent);
			}
			$this->_mt_blqt[$blqn] = $this->_mt_blqs[$blqn];
			$this->_mt_acti = $blqn;
			if( count($vars) > 0 ) {
				$this->asignar($vars,$blqn);
			}
			return;
		}	//	eof:usar

	/***
		*	asignar( hash_variables [,<etiqueta_bloque>] ) -> void
		*	asigna valores a variables
		*	@public
		**/
		public function asignar($var=array(),$blq="") {
			if(!is_array($var) || count($var)<=0) {
				$this->PRIV_mtError("asignar()","Faltan <i>variables</i> a asignar o la lista no es un arreglo");
			}
			if(!$blq) {
				$blq = $this->_mt_acti;
			}
			if($blq != '_MTRAIZ_' && $blq != '_MTGLOB_' && !array_key_exists($blq,$this->_mt_blqt)) {
				$this->PRIV_mtError("asignar()","El bloque <i>$blq</i> no está en uso");
			}
			if( array_key_exists($blq,$this->_mt_blqt) && $this->_mt_blqt[$blq] == "_MTASIGN_" ) {
				$this->PRIV_mtError("asignar()","El bloque <i>$blq</i> es de asignación, utilice el método <b>cargar()</b>");
			}
			foreach($var as $k=>$v) {
				switch($blq) {
					case '_MTGLOB_' :
						$this->_mt_varg[$k] = $v;
						break;
					case '_MTRAIZ_' :
						$this->_mt_blqs[$blq] = preg_replace("/{".$k."}/",$v,$this->_mt_blqs[$blq]);
						break;
					default:
						$this->_mt_blqt[$blq] = preg_replace("/{".$k."}/",$v,$this->_mt_blqt[$blq]);
				}
			}
			return;
		}	//	eof:asignar
		
	/***
		*	cargar(<nombre_bloque>,<dato_documento>[,<seccion_documento>]) -> void
		*	carga un documento a un bloque dinámico
		*	@public
		**/
		public function cargar($blq="",$doc="",$sec=2) {
			if(!$blq || !$doc) {
				$this->PRIV_mtError("cargar()","Falta(n) argumentos de función");
			}
			if(!array_key_exists($blq,$this->_mt_blqs)) {
				$this->PRIV_mtError("cargar()","No existe el bloque <i>$blq</i>");
			}
			if($this->_mt_blqs[$blq] != "_MTASIGN_") {
				$this->PRIV_mtError("cargar()","El bloque <i>$blq</i> no es un bloque dinámico");
			}
			$tmp = $this->PRIV_mtIsfile($doc);
			$src = "V";
			if($tmp) {
				$doc = $tmp;
				$src = "F";
			}
			unset($tmp);
			$this->usar($blq);
			if($sec == 0) {
				$this->_mt_blqs[$blq]="";
				$this->PRIV_mtparseTpl($doc,$blq,$src);
				$this->_mt_blqt[$blq]=$this->_mt_blqs[$blq];
				$this->_mt_blqs[$blq]="_MTASIGN_";
			} else {
				$this->_mt_blqt[$blq] = $this->PRIV_mtparseHtm($doc,$src,$sec);
			}
			return;
		}	//	eof:cargar
		
	/***
		*	ira(<bloque>) -> void
		*	activa un bloque en uso
		*	@public
		**/
		public function ira($blq="_MTRAIZ_") {
			if($blq == '_MTRAIZ_' || array_key_exists($blq,$this->_mt_blqt)) {
				$this->_mt_acti = $blq;
			}
			return;
		}	// eof:ira
		
	/***
		*	traducir(<idioma>) -> void
		*	asigna una plantilla de idioma para traducción
		*	@public
		**/
		public function traducir($idioma="") {
			$this->_mt_lang = '';
			if(!trim($idioma)) {
				return;
			}
			$a = $this->_mt_dirt.$idioma.'.php';
			if(!file_exists($a)) {
				$a = $this->_mt_dirt.'i18n/'.$idioma.'.php';
				if(!file_exists($a)) {
					return;
				}
			}
			if( !($arreglo = @include($a)) ) {
				return;
			}
			$this->_mt_lang = $a;
			return;
		}	// eof:traducir
		
	/***
		*	mostrar() - > void
		*	muestra el resultado
		*	@public
		**/
		public function mostrar() {
			reset($this->_mt_blqp);
			foreach($this->_mt_blqp as $k=>$v) {
				if($v == '_MTRAIZ_') {
					$this->PRIV_mtParseblq($k,'_MTRAIZ_');
				}
			}
			// asignación de variables globales
			foreach($this->_mt_varg as $k=>$v) {
				$this->_mt_blqs['_MTRAIZ_'] = preg_replace("/{".$k."}/",$v,$this->_mt_blqs['_MTRAIZ_']);
			}
			// ejecuta traductor
			$this->PRIV_mtTraductor();
			// salida (limpiando etiquetas perdidas)
			echo preg_replace("/{([a-zA-Z0-9_:]+)}/","",$this->_mt_blqs['_MTRAIZ_']);
			// la clase termina
			$this->_mt_dirt = "";
			$this->_mt_tpls = array();
			$this->_mt_blqs = array();
			$this->_mt_blqp = array();
			$this->_mt_blqt = array();
			$this->_mt_acti = '_MTRAIZ_';
			$this->_mt_varg = array();
			return;
		}	//	eof:mostrar
	
	/***
		*	obtener() -> cadena
		*	devuelve una cadena con el resultados
		*	@public
		**/
		public function obtener() {
			ob_start();
			$this->mostrar();
			return ob_get_flush();
		}	//	eof:obtener
		
	/***
		*	tiempo() -> segundos
		*	devuelve los segundos de ejecución
		*	@public
		**/
		public function tiempo() {
			$tfin = microtime(true);
			return $tfin - $this->_mt_tini;
		}	// eof:tiempo
	

	/***
		*	Métodos Privados
		*	@private
		*	PRIV_mtParsetpl	:	intérprete de plantilla
		*	PRIV_mtParsehtm	:	intérprete de documento HTML
		*	PRIV_mtParseblq	:	intérprete de bloques
    * PRIV_mtTraductor: traductor de plantilla
		*	PRIV_mtIsfile		:	conprueba nombre archivo y extrae directorio
		*	PRIV_mtError		:	mensaje de error
		**/
	
	/***
		*	PRIV_mtParsetpl( <dato_plantilla> [, <nombre_bloque> [, <tipo_dato> ] ] ) -> void
		*	intérprete de plantillas
		*	@private
		**/
		private function PRIV_mtParsetpl($tplDat,$tplblk,$tplTyp="F") {
			if($tplTyp == "F") {
				if(in_array($tplDat,$this->_mt_tpls)) {
					return;
				}
				$this->_mt_tpls[] = $tplDat;
				$aTemp = @file($tplDat);
			} else {
				$aTemp = explode("\n",$tplDat);
			}
			$blck_name[] = $tplblk;
			$blck_actv = $tplblk;
			$blck_omit = false;
			if(!array_key_exists($tplblk,$this->_mt_blqs)) {
				$this->_mt_blqs[$tplblk] = "";
			}
			$blck_npos = 0;
			foreach($aTemp as $k=>$v) {
				$v = trim($v);
				if(!$v) :
					$v = "";
				elseif(preg_match("/($this->_etq_dci|$this->_etq_dcf)/",$v,$r)) :
					if($r[0] == $this->_etq_dci) {
						$blck_omit = true;
					} else {
						$blck_omit = false;
					}
				elseif($blck_omit) :
					$v = "";
				elseif(preg_match("/(<!--\s)+($this->_etq_ini|$this->_etq_fin|$this->_etq_asg)+(\s+:\s)+([a-zA-Z0-9_]*)/",$v,$r)) :
					$blck_tipo = $r[2];
					$blck_etiq = $r[4];
					switch($blck_tipo) :
						case $this->_etq_ini :
							if(array_key_exists($blck_etiq,$this->_mt_blqp)) {
								$k++;
								$this->PRIV_mtError("Intérprete (Parser)","Etiqueta Duplicada -iniciaBloque : <i>$blck_etiq</i>-<br />Plantilla <i>$this->_mt_dirt$tplDat</i> / Línea $k");
							}
							$this->_mt_blqs[$blck_actv] .= "{BLCK:$blck_etiq}";
							$this->_mt_blqp[$blck_etiq] = $blck_actv;
							$blck_name[] = $blck_etiq;
							$blck_npos++;
							$blck_actv = $blck_name[$blck_npos];
							$this->_mt_blqs[$blck_actv] = "";
							break;
						case $this->_etq_fin :
							if($blck_etiq != $blck_actv) {
								$k++;
								$this->PRIV_mtError("Intérprete (Parser)","Error estructura de bloque -inicia/terminaBloque : <i>$blck_etiq</i>-<br />Plantilla <i>$this->_mt_dirt$tplDat</i> / Línea $k");
							}
							array_pop($blck_name);
							$blck_npos = count($blck_name)-1;
							$blck_actv = $blck_name[$blck_npos];
							break;
						case $this->_etq_asg :
							if(array_key_exists($blck_etiq,$this->_mt_blqp)) {
								$k++;
								$this->PRIV_mtError("Intérprete (Parser)","Etiqueta Duplicada -asignaBloque : <i>$blck_etiq</i>-<br />Plantilla <i>$this->_mt_dirt$tplDat</i> / Línea $k");
							}
							$this->_mt_blqp[$blck_etiq] = $blck_actv;
							$this->_mt_blqs[$blck_actv] .= "{BLCK:$blck_etiq}";
							$this->_mt_blqs[$blck_etiq] = "_MTASIGN_";
							break;
					endswitch;
				elseif(preg_match("/(<!--\s)+($this->_etq_inc)+(\s+:\s)+([a-zA-Z0-9_\.\/]*)/",$v,$r)) :
					$tmp = $this->PRIV_mtIsfile($r[4]);
					if(!$tmp) {
						$k++;
						$this->PRIV_mtError("Intérprete (Parser)","Documento no encontrado -insertaBloque : <i>$r[4]</i>-<br />Plantilla <i>$this->_mt_dirt$tplDat</i> / Línea $k");
					}
					$this->PRIV_mtParsetpl($tmp,$blck_actv,"F");
				else :
					$this->_mt_blqs[$blck_actv] .= $v;
				endif;
			}
			return;
		}	// eof:PRIV_mtParsetpl
		
	/***
		*	PRIV_mtParsehtm( <dato_documento> [,  <fuente_dato> [,<seccion_documento ] ]  ) -> void
		*	intérprete de documento HTML
		*	@private
		**/
		private function PRIV_mtParsehtm($docDat,$srcDat="F",$secDoc=2) {
			if($srcDat == "F") {
				if(!file_exists($docDat)) {
					$this->PRIV_Error("Intérprete (Parser)","Documento no encontrado <i>$docDat</i>");
				}
				$fp=@fopen($docDat,'r');
				$sTemp=@fread($fp,filesize($docDat));
				@fclose($fp);
				$docDat=$sTemp;
				unset($sTemp);
			}
			$docDat=preg_replace("/[\n|\n\r|\r|\t]/","",$docDat);
			$docDat=preg_replace("/\s{2,}/"," ",$docDat);
			if($secDoc == 2) {
				if(preg_match("/(<[\s\/]*(?i)body)(\w*)([^>]*>)/",$docDat)) {
					$fa=preg_split("/(<[\s\/]*(?i)body)(\w*)([^>]*>)/",$docDat);
					$docDat=$fa[1];
					unset($fa);
				}
			}
			return $docDat;
		}	// eof:PRIV_mtParsehtm()
		
	/***
		*	PRIV_mtParseblq( <bloque_actual>, <bloque_padre> ) -> void
		*	intérprete de bloques
		*	@private
		**/
		private function PRIV_mtParseblq($blqa,$blqp) {
			if(!array_key_exists($blqa,$this->_mt_blqt)) {
				return;
			}
			while( preg_match_all("/{BLCK:([a-zA-Z0-9_]*)}/",$this->_mt_blqt[$blqa],$rb) ) {
				foreach($rb[1] as $k=>$v) {
					if(array_key_exists($v,$this->_mt_blqt)) {
						$this->_mt_blqt[$blqa] = preg_replace("/{BLCK:".$v."}/",$this->_mt_blqt[$v],$this->_mt_blqt[$blqa]);
						unset($this->_mt_blqt[$v]);
					} else {
						$this->_mt_blqt[$blqa] = preg_replace("/{BLCK:".$v."}/","",$this->_mt_blqt[$blqa]);
					}
				}
			}
			if($blqp == '_MTRAIZ_') {
				$this->_mt_blqs['_MTRAIZ_'] = preg_replace("/{BLCK:".$blqa."}/",$this->_mt_blqt[$blqa]."{BLCK:$blqa}",$this->_mt_blqs['_MTRAIZ_']);
			} else {
				$this->_mt_blqt[$blqp] = preg_replace("/{BLCK:".$blqa."}/",$this->_mt_blqt[$blqa]."{BLCK:$blqa}",$this->_mt_blqt[$blqp]);
			}
			unset($this->_mt_blqt[$blqa]);
			return;
		}	//	eof:PRIV_mtParseblq
		
	/***
		*	PRIV_mtTraductor()
		*	traductor de plantilla
		*	carga el documento de idioma definido en $_mt_lang
		*	y lo aplica a las etiquetas de idioma en la plantilla
		*	@private
		**/
		private function PRIV_mtTraductor() {
			if($this->_mt_lang) {
				$idioma = include($this->_mt_lang);
				foreach($idioma as $k=>$v) {
					$clave = '['.$k.']';
					$this->_mt_blqs['_MTRAIZ_'] = str_replace($clave,$v,$this->_mt_blqs['_MTRAIZ_']);
				}
			}
			//$this->_mt_blqs['_MTRAIZ_'] = str_replace('[',"",$this->_mt_blqs['_MTRAIZ_']);
			//$this->_mt_blqs['_MTRAIZ_'] = str_replace(']',"",$this->_mt_blqs['_MTRAIZ_']);
			return;
		}
		
	/***
		*	PRIV_mtIsfile( <cadena> ) -> nombre_archivo
		*	comprueba si la cadena es un nombre de archivo
		*	extrae directorios y devuelve nombre de archivo
		*	@private
		**/
		private function PRIV_mtIsfile($cadena) {
			if(!preg_match("/^([a-zA-Z0-9\._\-\/]*)+([\.]+[a-zA-Z0-9]{1,4})$/",$cadena)) {
				return "";
			}
			$d = dirname($cadena);
			$f = basename($cadena);
			if(!$this->_mt_dirt) {
				$this->_mt_dirt = $d."/";
			}
			if(file_exists($cadena)) {
				$retorno = $cadena;
			} elseif(file_exists($this->_mt_dirt.$f)) {
				$retorno = $this->_mt_dirt.$f;
			} elseif(file_exists($d.$f)) {
				$retorno = $d.$f;
			} else {
				$retorno = "";
			}
			return $retorno;
		}	//	eof:PRIV_mtIsfile
		
	/***
		* 	PRIV_mtError( <función_error>, <descripción_error> ) -> void
		* 	muestra error y cancela ejecución
		* 	@private
		**/
		private function PRIV_mtError($srcErr="clase",$dcrErr="Desconocido") {
			if(ob_get_level() > 0) {
				ob_end_clean();
			}
			echo '<div style="text-align:center;margin-top:20px">';
			echo '<b>ERROR</b> al ejecutar <span style="color:#FF0000;font-weight:bold">'.$srcErr.'</span><br />';
			echo '<span style="color:#808080">'.$dcrErr.'</span><p />';
			echo '<small>ktpl</small>';
			echo '</div>';
			echo '<p />';
			exit();
		} //	eof:PRIV_mtError

}	//	eoc:motorPlantilla
?>
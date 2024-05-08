<?php

class Impuestos
{

	public $db;

	public function __construct($db)
	{
		$this->db = $db;
	}

    public function addImpuestosFacture($fk_chargesociales,$fk_facture,$porcentaje,$fk_user_author,$table_element)
	{  

        $sql='SELECT bi.campo_facture FROM  llx_c_chargesociales AS im INNER JOIN llx_base_importe AS bi ON im.base_importe=bi.rowid WHERE im.id='.$fk_chargesociales.' AND im.base_importe IS NOT NULL';      
        $result =$this->db->query($sql);
     
		if ($result) {
            $num = $this->db->num_rows($result);
            if($num){

				$object = new Facture($this->db);
				$aplicacion_impuesto='VEN';

				if($table_element=='facture_fourn'){
                    $object = new FactureFournisseur($this->db);
					$aplicacion_impuesto='COM';
				}

                $objp = $this->db->fetch_object($result);


                $object->fetch($fk_facture);
				$campo=$objp->campo_facture;
                $value=$object->$campo;
				$importe=($value)*($porcentaje/100);

				$sql='INSERT INTO llx_facture_impuesto(fk_chargesociales,fk_facture,porcentaje,fk_user_author,importe) VALUES('.$fk_chargesociales.','.$fk_facture.','.$porcentaje.','.$fk_user_author.','.$importe.')';

				$result2 =$this->db->query($sql);
                if ($result2) {

					$sql ="SELECT SUM(importe) as total";
					$sql .=" FROM llx_facture_impuesto as fi";
					$sql .=" INNER JOIN llx_c_chargesociales AS im ON fi.fk_chargesociales=im.id";
					$sql .=" WHERE fi.fk_facture=".$fk_facture." AND im.fk_aplicacion_impuestos='".$aplicacion_impuesto."'";

					$result3 =$this->db->query($sql);

					if ($result3) {
						$obj = $this->db->fetch_object($result3);

						$sql="UPDATE  ".MAIN_DB_PREFIX.$table_element." SET multicurrency_total_impuestos=".$obj->total." WHERE rowid=".$fk_facture;
						$result4 =$this->db->query($sql);

						if($result4){


						}
						
					
					}else{


					}
					
				}else{



				}
		
            }		
		}else{

		}

    }



	public function deleteImpuestosFacture($id,$fk_facture,$table_element)
	{  

		$sql='DELETE FROM llx_facture_impuesto WHERE rowid='.$id;
		$result = $this->db->query($sql);
		if ($result) {

			$aplicacion_impuesto='VEN';

			if($table_element=='facture_fourn'){
				$aplicacion_impuesto='COM';
			}


			$sql ="SELECT IFNULL(SUM(importe),0) as total";
			$sql .=" FROM llx_facture_impuesto as fi";
			$sql .=" INNER JOIN llx_c_chargesociales AS im ON fi.fk_chargesociales=im.id";
			$sql .=" WHERE fi.fk_facture=".$fk_facture." AND im.fk_aplicacion_impuestos='".$aplicacion_impuesto."'";

			$result3 =$this->db->query($sql);

			if ($result3) {

				$obj = $this->db->fetch_object($result3);
				$sql="UPDATE  ".MAIN_DB_PREFIX.$table_element." SET multicurrency_total_impuestos=".$obj->total." WHERE rowid=".$fk_facture;
				$result4 =$this->db->query($sql);

				if(!$result4){


				}
			}else{

			}	

		}else{

		}

    }


	public function updateImpuestosFacture($id,$porcentaje,$fk_facture,$fk_chargesociales,$table_element)
	{  


        $sql='SELECT bi.campo_facture FROM  llx_c_chargesociales AS im INNER JOIN llx_base_importe AS bi ON im.base_importe=bi.rowid WHERE im.id='.$fk_chargesociales.' AND im.base_importe IS NOT NULL';      
        $result =$this->db->query($sql);
     
		if ($result) {
            $num = $this->db->num_rows($result);
            if($num){

                $objp = $this->db->fetch_object($result);

				$aplicacion_impuesto='VEN';
                $object = new Facture($this->db);
				if($table_element=='facture_fourn'){
                    $object = new FactureFournisseur($this->db);
					$aplicacion_impuesto='COM';
				}

                $object->fetch($fk_facture);
				$campo=$objp->campo_facture;
                $value=$object->$campo;
				$importe=($value)*($porcentaje/100);

				$sql='UPDATE llx_facture_impuesto SET porcentaje='.$porcentaje.', importe='.$importe.' WHERE fk_chargesociales='.$fk_chargesociales.' AND rowid='.$id.' AND  fk_facture='.$fk_facture;

				$result2 =$this->db->query($sql);
                if ($result2) {
		
					$sql ="SELECT IFNULL(SUM(importe),0) as total";
					$sql .=" FROM llx_facture_impuesto as fi";
					$sql .=" INNER JOIN llx_c_chargesociales AS im ON fi.fk_chargesociales=im.id";
					$sql .=" WHERE fi.fk_facture=".$fk_facture." AND im.fk_aplicacion_impuestos='".$aplicacion_impuesto."'";
					$result3 =$this->db->query($sql);
	
					if ($result3) {
						$obj = $this->db->fetch_object($result3);	
						$sql="UPDATE  ".MAIN_DB_PREFIX.$table_element." SET multicurrency_total_impuestos=".$obj->total." WHERE rowid=".$fk_facture;
						$result4 =$this->db->query($sql);

						if($result4){


						}
						
					
					}else{


					}
					
				}else{



				}
		
            }		
		}else{

		}

    }



	
	public function getSelectBaseImporte($htmlname = 'subtypeid', $addempty = 0, $morecss = '',$selected)
	{
		
		$sql="select rowid,campo_facture,nombre from llx_base_importe";

		$resql = $this->db->query($sql);
		if ($resql) {
			$num = $this->db->num_rows($resql);
			$i = 0;
			$out .= '<select id="' . $htmlname . '" class="flat selectsubtype' . ($morecss ? ' ' . $morecss : '') . '" name="' . $htmlname . '">';
			if ($addempty) {
				$out .= '<option value="0">&nbsp;</option>';
			}
			while ($i < $num) {

				$obj = $this->db->fetch_object($resql);
			$out .= '<option value="' . $obj->rowid.'"';
			if ($selected == $obj->rowid) {
				$out .= ' selected="selected"';
			}
			$out .= '>';


			$out .= $obj->nombre;
			$out .= '</option>';
				$i++;
			}

			$out .= '</select>';

			//$this->cache_invoice_subtype = dol_sort_array($this->cache_invoice_subtype, 'code', 'asc', 0, 0, 1);

			return $out;
		} else {
			dol_print_error($this->db);
			return -1;
		}
			
	}


	public function getSelectAplicacionImpuesto($htmlname = 'subtypeid', $addempty = 0, $morecss = '',$selected)
	{
		
		$sql="select codigo,nombre from llx_aplicacion_impuestos where active=1";

		$resql = $this->db->query($sql);
		if ($resql) {
			$num = $this->db->num_rows($resql);
			$i = 0;
			$out .= '<select id="' . $htmlname . '" class="flat selectsubtype' . ($morecss ? ' ' . $morecss : '') . '" name="' . $htmlname . '">';
			if ($addempty) {
				$out .= '<option value="0">&nbsp;</option>';
			}
			while ($i < $num) {
				$obj = $this->db->fetch_object($resql);
			$out .= '<option value="' . $obj->codigo.'"';
			if ($selected == $obj->codigo) {
				$out .= ' selected="selected"';
			}
			$out .= '>';


			$out .= $obj->nombre;
			$out .= '</option>';
				$i++;
			}

			$out .= '</select>';

			//$this->cache_invoice_subtype = dol_sort_array($this->cache_invoice_subtype, 'code', 'asc', 0, 0, 1);

			return $out;
		} else {
			dol_print_error($this->db);
			return -1;
		}
			
	}
}

<?php
/**
 * Created by PhpStorm.
 * User: Master
 * Date: 29.12.2010
 * Time: 18:16:09
 */
 
class Z_Viewer extends Z_Object implements iViewer {
	protected $error_reporting;


  public function fetch($template, array $data = array()){
    $this->error_reporting = error_reporting();
    error_reporting($this->error_reporting ^ (E_NOTICE | E_WARNING));
    extract ($data);
    ob_start();
    include $template;
    $buffer = ob_get_clean();
    error_reporting($this->error_reporting);
    return $buffer;
  }	

}

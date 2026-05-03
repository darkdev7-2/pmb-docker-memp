<?php
// +-------------------------------------------------+
// � 2002-2004 PMB Services / www.sigb.net pmb@sigb.net et contributeurs (voir www.sigb.net)
// +-------------------------------------------------+
// $Id: verif.class.php,v 1.7 2021/04/30 12:06:05 rtigero Exp $

if (stristr($_SERVER['REQUEST_URI'], ".class.php")) die("no access");
    
class verif
{
    private $extensions;
    private $phpSuggestedSetup;
    private $mysqlSetup;
    private $install_msg;
    private $units = array("k"=>"000", "M"=>"000000", "G"=>"000000000");
    private $toogle = array(
        "<img src='images/error.gif' style='height:16px;' />",
        "<img src='images/tick.gif' style='height:16px;'/>",
        "<img src='images/warning.gif' style='height:16px;' />"
    );
    private $sqlModes = array("", "NO_AUTO_CREATE_USER");
    private $phpVersion = [
        "min" => "7.3",
        "max" => "7.4"
    ];
    private $mysqlVersion = [
        "min" => "5.5",
        "max" => "5.7"
    ];
    private $mariaDbVersion = [
        "min" => "5.5",
        "max" => "10.4.99"
    ];

    public function __construct($extensions, $phpSuggestedSetup, $mysqlSetup, $messages)
    {
        $this->extensions = $extensions;
        $this->phpSuggestedSetup = $phpSuggestedSetup;
        $this->mysqlSetup = $mysqlSetup;
        $this->install_msg = $messages;
    }
    
    public function checkPhpVersion()
    {
        $check = false;
        $version = PHP_MAJOR_VERSION.".".PHP_MINOR_VERSION;
        
        if(version_compare($version, $this->phpVersion['min'], '>=') && version_compare($version, $this->phpVersion['max'], '<=')){
            $check = true;
        }
        return [
            "check" => $check,
            "required_version" => $this->phpVersion
        ];
    }
    
    public function checkMySQLVersion($connexion = null)
    {
        $checked = false;
        is_null($connexion) ?
        $result = pmb_mysql_query('select VERSION()')
        : $result = pmb_mysql_query('select VERSION()', $connexion);
        
        $row = pmb_mysql_fetch_all($result);

        $explodedVersion = explode("-", $row[0][0]);
        $version = $explodedVersion[0];
        //Gestion du type de serveur de bdd
        switch($explodedVersion[1]){
            case "MariaDB" :
                if(version_compare($version, $this->mariaDbVersion['min'], ">=") && version_compare($version, $this->mariaDbVersion['max'], "<=")){
                    $checked = true;
                }
                $engineType = $explodedVersion[1];
                $requiredVersion = $this->mariaDbVersion;
                break;
            default : 
                if(version_compare($version, $this->mysqlVersion['min'], ">=") && version_compare($version, $this->mysqlVersion['max'], "<=")){
                    $checked = true;
                }
                $engineType = "MySQL";
                $requiredVersion = $this->mysqlVersion;
                break;
        }
        return [
            "user_version" => $version,
            "engine_type" => $engineType,
            "checked" => $checked,
            "required_version" => $requiredVersion
        ];
    }

    public function checkExtensions()
    {
        $required = $this->extensions;        
        $extensions = get_loaded_extensions();
        foreach($required as $extName => $ext){
            $state = (in_array($extName, $extensions) ? 1 : 0);
            if($state == 0 && $ext['optional']){
                $state = 2;
            }
            //V�rification de la version
            if($state != 0 && !empty($ext['version'])){
                $neededVersion = explode(" ", $ext['version']);
                $userVersion = phpversion($extName);
                //Gestion particuli�re de libxml car phpversion ne fonctionne pas dessus
                if($extName == "libxml"){
                    $userVersion = LIBXML_DOTTED_VERSION;
                }
                //Si la version utilisateur n'est pas >= � la version recommand�e
                if(!version_compare($userVersion, $neededVersion[1], $neededVersion[0])){
                    $state = 0;
                }
            }
            $requirements[] = [
                "name" => $extName,
                "state" => $state,
                "version" => $ext['version'],
                "optional" => $ext['optional']
            ];
        }
        return $requirements;
    }

    public function checkPHP()
    {
        $phpSetup = $this->phpSuggestedSetup;
        $checks = array();
        
        foreach($phpSetup as $paramName => $suggestedValue){
            $value = "";
            $required = 1;
            $currentParamValue = ini_get($paramName);
            
            switch($paramName){
                case "date.timezone":
                    if(empty($currentParamValue)){;
                        $value = $this->install_msg['req_no_setting_defined'];
                        $suggestedValue['value'] = $suggestedValue['value'].$this->install_msg['req_timezone_indication'];
                        $required = 0;
                    }
                    break;
                case "display_errors":
                case "expose_php";
                    if($currentParamValue){
                        $value = "On";
                        //si activ� alors on lance un avertissement
                        $required = 2;
                    } else {
                        $value = "Off";
                    };
                    break;
                case "suhosin.request.max_vars":
                case "suhosin.post.max_vars":
                    if(!extension_loaded("suhosin")){
                        $value = $this->install_msg['req_ext_not_installed'];
                        $required = 2;
                    }
                    break;
            }
            //Si value est toujours vide, on lui affecte la valeur de currentParamValue
            if(empty($value)){
                $value = $currentParamValue;
            }
            
            //Gestion des comparaisons
            switch(true){
                case !empty($suggestedValue['numeric_value']):
                    //R�cup�ration de la valeur num�rique
                    $userValue = str_replace(array_keys($this->units) , array_values($this->units), $currentParamValue);
                    $storedValue = str_replace(array_keys($this->units) , array_values($this->units), $suggestedValue['numeric_value']);
                    if((int)$userValue < (int)$storedValue){
                        $required = 2;
                    }
                    break;
                case !empty($currentParamValue):
                    if((int)$suggestedValue['value'] != (int)$currentParamValue){
                        $required = 2;
                    }
                    break;
            }

            $checks[] = [
                "name" => $paramName,
                "current_value" => $value,
                "suggested_value" => $suggestedValue['value'],
                "required" => $required
            ];
        }
        return $checks;
    }

    public function checkMySQL($connexion = null)
    {
        $checks = array();
        $mysqlSetup = $this->mysqlSetup;
        foreach($mysqlSetup as $setupName => $setupValue){
            $state = null;
            is_null($connexion) ? 
                $result = pmb_mysql_query('show variables like "' . $setupName . '"')
                : $result = pmb_mysql_query('show variables like "' . $setupName . '"', $connexion);
            
            $row = pmb_mysql_fetch_all($result);
            $name = $row[0][0];
            $suggestedValue =  $setupValue['value'];
            $userValue = $row[0][1];
            
            switch(true){
                //Si le param�tre est vide
                case empty($userValue):
                    $userValue = $this->install_msg['req_no_sql_variable_value'];
                    $state = 2;
                    break;
                //Si le param�tre est sous taille num�rique
                case $setupValue['numeric_size'] && $setupValue['numeric_size'] == 'yes':
                    $suggestedValue = str_replace(array_keys($this->units) , array_values($this->units), $suggestedValue);
                    (int)$suggestedValue <= (int)$userValue ? $state = 1 : $state = 2;
                    break;
                //Si le param�tre est sous format d'entier
                case intval($userValue) != 0:
                    intval($suggestedValue) <= intval($userValue) ? $state = 1 : $state = 2;
                    break;
                //Sinon 
                default:
                    strpos(strtolower($suggestedValue), strtolower($userValue)) !== false ? $state = 1 : $state = 2;
                    break;
            }
            
            //Gestion des param�tres bloquants
            switch($setupName){
                case "default_storage_engine":
                case "character_set_server":
                case "collation_server":
                    if($state == 2){
                        $state = 0;
                    }
                    break;
                case "sql_mode":
                    //Si le param�tre n'est pas vide et ne fait pas partie des param�tres autoris�s
                    if($userValue != $this->install_msg['req_no_sql_variable_value'] && !in_array($userValue, $this->sqlModes)){
                       $state = 0; 
                    } else {
                        $state = 1;
                    }
                    break;
            }
                        
            $checks[] = [
                'name' => $name,
                'value' => $userValue,
                'suggestedValue' => $suggestedValue,
                'state' => $state
            ];
        }
        return $checks;
    }

    private function get_numeric_size($size, $unit = 'M')
    {
        $units = [
            'o',
            'k',
            'M',
            'G',
            'T'
        ];
        $i = 0;
        while ($size >= 1024) {
            $size = $size / 1024;
            $i ++;
        }
        return $size . $units[$i];
    }
}
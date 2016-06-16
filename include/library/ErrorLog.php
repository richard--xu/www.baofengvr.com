<?php
class ErrorLog {
    
    function dolog($var, $name = 'log', $line_delimiter = "\n")
    {
        $time = date("Y-m-d",time());
        $fp = fopen('/tmp/bbs_yaf-' . $name . $time . '.log', 'a');
        if (!is_string($var)){
            $var = var_export($var, true);
        }
        fwrite($fp, $var . $line_delimiter);
        fclose($fp);
    }

    public function doLogForSql($var, $name = 'log', $line_delimiter = "\n")
    {
        $time = date("Y-m-d",time());
        if (!is_dir(ROOT.'/logs/loadsqlfortest/')) {
            mkdir(ROOT.'/logs/loadsqlfortest/', 0777, true);
        }

        $fp = fopen(ROOT.'/logs/loadsqlfortest/bbs_yaf-' . $name . $time . '.log', 'a');
        if (!is_string($var)){
            $var = var_export($var, true);
        }
        fwrite($fp, $var . $line_delimiter);
        fclose($fp);
    }
    
}
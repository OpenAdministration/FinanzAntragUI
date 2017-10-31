<?php

include "../lib/inc.all.php";

//Angezeigter NAme in Tool bei Auto Status Change
global $attributes;
$attributes["eduPersonPrincipalName"][0] = "Gremienwiki";


//Angefordererte Pdfs von FUI2PDF
if(isset($_GET['fname'])){
    $fname = $_GET['fname'];
    $id = $_GET['id'];
    $file = SYSBASE.'/storage/'.$id.'/'.$fname;

    #echo $file;
    header('Content-Disposition: attachment; filename="'. basename($file) . '"');
    header("Content-type: application/pdf");
    header('Content-Length: ' . filesize($file));
    readfile($file);
    exit;
}

$json = file_get_contents('php://input');
$data = json_decode($json, true);
if(!count($data)){
    echo "invalid JSON!";
    exit;
}

foreach($data as $token => $beschluss){
    $antrag = dbGet("antrag", ["token" => $token]);
    //test if antrag ist projekt-intern in right state
    $inhalt = [];
    $inhalt['antrag_id'] = $antrag['id'];
    $inhalt['contenttype'] = "text";
    if($antrag['type'] != "projekt-intern")
        continue;
    if($antrag['state'] == "ok-by-hv"){
        $inhalt["fieldname"] = "genehmigung.recht.int.sturabeschluss";
    }else if($antrag['state'] == "need-stura"){
        $inhalt["fieldname"] = "genehmigung.recht.stura.beschluss";
    }else{
        continue;
    }
    $inhalt["value"] = $beschluss;
    $ret = storeInhalt($inhalt,true);
    if(!$ret)
        exit;
    if($antrag['state'] == "need-stura"){
        $msgs = [];
        $filesCreated = [];
        $filesRemoved = [];
        $target = [];
        $form = getForm($antrag['id'],$antrag['revision']);
        writeState("ok-by-stura",$antrag,$form,$msgs,$filesCreated,$filesRemoved,$target,false);
    }

}

?>

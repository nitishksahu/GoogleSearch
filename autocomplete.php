<?php
header('Acess-Control-Allow-Origin:*');

  if(isset($_GET["myAutoCompleteTerm"])){
    $autoCompleteTerm = $_GET['myAutoCompleteTerm'];
    $autoCompleteTerm = strtolower($autoCompleteTerm);
    $final_url="http://localhost:8983/solr/irhw4/suggest?indent=on&wt=json&q=".$autoCompleteTerm;
    $jsonData=file_get_contents($final_url);
    echo $jsonData;
  }

?>
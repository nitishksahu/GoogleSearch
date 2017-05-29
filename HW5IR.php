<?php
ini_set('memory_limit', '-1');
set_time_limit(50);
//include Norvik Spellcorrector.php
include 'SpellCorrector.php';

//including thrd party html parsing php for geeting the snippet
include 'simple_html_dom.php';
error_reporting(0);
chmod("/Users/NitishSahu/Sites/serialized_dictionary.txt", 755);
//snippet code
function submitForSnippeting($name,$q1) {
  $k=file_get_html($name);
  if(strlen($k)===0)
    return;
  $t=$k->plaintext;
  $content=strtolower(strip_tags($t,"<br>"));

  $matches;
  $position = strstr($t,$q1);
  $q=strtolower(str_replace(" ", ".*", $q1));
  // echo $q;
  $word="/\b$q\b/mi";
//  echo "<hr>";
  $rest="";

  preg_match($word, $content,$matches,PREG_OFFSET_CAPTURE);

  foreach ($matches as $item) {
    $l= $item[1];
    $rest.=trim(substr($content, $l-10,400));
  }
  $arr=explode(" ", $q1);
  foreach($arr as $item)
  {
  $rest=str_replace($item, "<b>".$item."</b>", $rest);
  $rest=str_replace(strtolower($item), "<b>".$item."</b>", $rest);
  $rest=str_replace(strtoupper($item), "<b>".$item."</b>", $rest);
  }
  echo "<b>SNIPPET</b> :";
  echo $rest;
  if(strlen($rest)==0)
  {
    $arr1=explode(" ", $q1);
    foreach($arr1 as $it)
    {
      $mat;
      preg_match("/\b".$it."\b/mi", $content,$mat,PREG_OFFSET_CAPTURE);
      foreach ($mat as $item)
      {

        $l= $item[1];
        $rest.=" ".trim(substr($content, $l-10,40));
        foreach($arr as $item)
          {
            $rest=str_replace($item, "<b>".$item."</b>", $rest);
            $rest=str_replace(strtolower($item), "<b>".$item."</b>", $rest);
            $rest=str_replace(strtoupper($item), "<b>".$item."</b>", $rest);
          }
      }
      echo $rest;
    }



  }
  else{
  $rest="NO match found";
}
//  echo "<hr>";
}

$myString="";
$query = isset($_REQUEST['q']) ? $_REQUEST['q']: false;
$results = false;
$csv = array();
$lines = file('mapNYTimesDataFile.csv', FILE_IGNORE_NEW_LINES);

foreach ($lines as $key => $value)
{
    $temp = explode(",", $value);
    $csv[$temp[0]] = str_getcsv($temp[1]);
}


if ($query)
{
  
  require_once('Apache/Solr/Service.php');
  $solr = new Apache_Solr_Service('localhost', 8983, '/solr/irhw4');

  // if magic quotes is enabled then stripslashes will be needed
  if (get_magic_quotes_gpc() == 1)
  {
    $query = stripslashes($query);
  }

 $spl=explode(" ", $query);

  $flag=false;
  $correctQuery="";
  foreach ($spl as $w) {
    $corr=SpellCorrector::correct($w); 
//    echo $corr;
//    if(!/^[0-9a-zA-Z]+$/.test($corr)){
//        echo "hi";
//    }
    $correctQuery.=" ".$corr;
      
    
   
    if(strcmp($w, $corr))
    {
      $flag=true;
    }
  }

  if(strcmp(strtolower(trim($correctQuery)),strtolower(trim($query))))
  {
    $myString= "Showing results for: <b><i style=color:blue;>".trim($query)."</b></i> "."<br>";
    $myString=$myString. "<h5 style=color:red;display:inline;>Did you mean:</h5> <b><a style=display:inline; href='http://localhost/~NitishSahu/HW5IR.php?count=0&sort=solr&q=".trim($correctQuery)."'>".trim($correctQuery)."</a></b> "." ?<br>";

    if(!isset($_REQUEST['count']))
    {
      //$query=trim($correctQuery);
    }

  }

  try
  {
        $results = $solr->search($correctQuery, 0, $limit);

  }
  catch (Exception $e)
  { 
    die("<html><head><title>Exception!</title><body><pre>{$e->__toString()}</pre></body></html>");
  }
}

?>
    <html lang="en">

    <head>
        <title>Solr Client Example</title>
        <script src="//code.jquery.com/jquery-1.10.2.js"></script>
        <script src="//code.jquery.com/ui/1.11.4/jquery-ui.js"></script>
        <link rel="stylesheet" href="https://ajax.googleapis.com/ajax/libs/jqueryui/1.11.3/themes/smoothness/jquery-ui.css" />
        <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css" integrity="sha384-BVYiiSIFeK1dGmJRAkycuHAHRg32OmUcww7on3RYdg4Va+PmSTsz/K68vbdEjh4u" crossorigin="anonymous">
    </head>
        
        <script>
          //Resetting the string to blank
          prevStr = "";
          function getSuggestions(){
              if(document.getElementById('q').value==""){
                prevStr = "";  
              }
              
            $("#q").autocomplete({
              source: function( request, response )
              {
                xhr = $.ajax({
                crossDomain: true,
                url: "autocomplete.php",
                data: {
                  myAutoCompleteTerm: request.term
                },
                success: function( data ) {  
                  var lowerCaseInput = request.term.toLowerCase();
                  var words = lowerCaseInput.split(" ");
                    
                 for( var i=0; i < words.length;i++){
                     console.log("words["+i+"]"+words[i]);
                 }
                  if(words[words.length-1] == ""){
                    console.log("the string is blank");
                    prevStr="";  
                    for(var j=0;j<words.length-1;j++){
                        console.log(j+"   "+words[j]);
                        prevStr+=words[j]+" ";
                    }
                    //prevStr += words[words.length-2];
                    //prevStr += " ";
                      console.log("Previous string: "+ prevStr);
                  }
                 
                  else{
                    //console.log(data);
                    data=JSON.parse(data);
                    //console.log(data);
                    if(data == ""){
                      console.log("data after parsing is blank");
                    }
                    else{
                      var currData = words[words.length-1];
                      if(currData.length === 1){
                        response( $.map( data.suggest.suggest[currData].suggestions, function( item, len ) {
                          //cases when the length is less than 10  
                           
                          if(len < 10){
                              if(/^[0-9a-zA-Z]+$/.test(item.term)){
                                  return {
                              label:prevStr+item.term
                                } 
                              }
                               
                           
                          }
                        }));
                        //prevStr="";
                      }
                      //cases when the length is less than 2      
                      else if(currData.length === 2){
                        response( $.map( data.suggest.suggest[currData].suggestions, function( item, len ) {
                          if(len < 7){
                              if(/^[0-9a-zA-Z]+$/.test(item.term)){
                                  return {
                                  label:prevStr+item.term
                                }
                              }
                              
                            
                          }
                        }));
                           //prevStr="";
                      }
                    //cases when the length is less than 4  
                      else{
                        response( $.map( data.suggest.suggest[currData].suggestions, function( item, len ) {
                          if(len < 4){
                            if(/^[0-9a-zA-Z]+$/.test(item.term)){
                                 return {
                              label:prevStr+item.term
                            }
                            }
                              
                           
                          }
                        }));
                           //prevStr="";
                      }
                    }
                  }
                },
                error: function(){
                  console.log("Error in getting suggestions");
                }
              });
              },
             
            });
          }

        </script>

    <body>
        <center>
        <form accept-charset="utf-8" method="get">
            <nav style="background:#fafafa;height:15%;">
                <br>
                <a href="http://localhost/~NitishSahu/HW5IR.php"><img src="mygoogle.png" height="80%" alt="Google" style="margin-left:-10px;"></img></a>
<!--            <label for="q">Search : </label>-->
            <input class="input-lg" style="width:65%;" id="q" name="q" type="text" onkeypress="getSuggestions()" value="<?php echo htmlspecialchars($query, ENT_QUOTES, 'utf-8'); ?>" />
            <button type="submit" class="btn btn-primary" style="margin-top: -4px;padding:0px;">
                <i class="input-lg glyphicon glyphicon-search"></i>
                </button>
<!--            <input style="background:#fafafa;" class="input-lg glyphicon glyphicon-search" type="submit"> </input>-->
            
            </nav>
        </form>
      
<?php
if ($results)
{
  $total = (int) $results->response->numFound;
?>
<div style="text-align: left;     font-size: larger;    color: gray;margin-left: 21.1%;">
    About <?php echo $total; ?> results (1.95434 seconds)
</div>
<div id="spellCheckData" style="text-align: left;     font-size: larger;    color: gray;margin-left: 21.1%;">
     <?php echo $myString ?>
</div>
</center>
        <ol style="margin-left:18%;list-style-type: none;">
                <?php $count=1;
  foreach ($results->response->docs as $doc)
  {
        echo "<li>";
        //print_r($doc->description);
        $url=substr($doc->resourcename,32);
        $temp = explode('/', $url);
        $tUrl = array_pop($temp);
        $final_url = $csv[$tUrl][0];
                
        echo '<span style=font-size:medium;><a target=_blank href='.$final_url.' >'.$doc->title.'</span></a>';
        echo "<br>";
        echo "<span style=color:green;>".htmlspecialchars($final_url, ENT_NOQUOTES, 'utf-8')."</span><br>";
        
       
        echo $doc->id."<br>";
        if(is_scalar($doc->description)){
        echo  $doc->description."<br>";
        }
        
        submitForSnippeting($final_url,$query);

        echo  "</li>";
        echo "<hr>";
    }

?>
            </ol>
            <?php
}
?>
    </body>
    </html>
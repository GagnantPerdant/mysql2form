<?php
$defaultSql = <<< SQL
CREATE TABLE `formulaireContact` (
 `idFormulaireContact` int(8) NOT NULL AUTO_INCREMENT,
 `idContact` int(8) NOT NULL,
 `interets` set('Déménagement','Internet mobile et TV','Energie et Eau','Sécurité et Domotique','Ménage') COLLATE utf8_unicode_ci DEFAULT NULL,
 `autresInterets` enum('Surf','Skateboard','Patins à roulette','Autres') COLLATE utf8_unicode_ci DEFAULT NULL,
 `sujet` varchar(150) COLLATE utf8_unicode_ci NOT NULL,
 `message` varchar(1000) COLLATE utf8_unicode_ci NOT NULL,
 PRIMARY KEY (`idFormulaireContact`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci
SQL;

$sql = "";
$form = "";
if(!empty($_POST['sql']))
{
	//error_log(print_r($_POST));
	$sql = $_POST['sql'];
}
else
{
	$sql = $defaultSql;
}
$form = toForm($sql);

function toForm($sql)
{
		global $nomtable;
		ob_start;
        $lines = explode("\n", $sql);
        //var_dump($lines);
        $nomsColonnes = '';
        $nomtable = '';
        foreach($lines AS $line)
        {
                if(stripos($line, 'CREATE TABLE') !== FALSE)
                {
                        $content = str_ireplace(array("CREATE TABLE",'('), "", $line);
                        $nomtable = trim(str_replace('`','',$content));
                        echo '<form method="post">'."\r\n";
                        continue;
                }
                if( (stripos($line, 'int(') !== FALSE) || (stripos($line, 'varchar(') !== FALSE) || (stripos($line, 'enum(') !== FALSE) || (stripos($line, 'set(') !== FALSE))
                {
                        $line = explode(")",$line)[0].')';
                        $contents = explode(' ',trim($line), 2);
                        //echo "nom:".$contents[0]."\r\n";
                        $nomColonne = trim(str_replace('`','',$contents[0]));
                        $nomsColonnes[] = $nomColonne;
                        //echo "type:".$contents[1]."\r\n";
                        $types = explode('(',str_replace(')','',$contents[1]));
                        $type = $types[0];
                        $size = $types[1];
                        if(stripos($nomColonne, 'id') === 0)
                        {
                                continue;
                        }
                        if($nomColonne === 'email')
                        {
                                $iType = 'email';
                                $maxLen = ' maxlength="'.$size.'"';
                        }
                        elseif($type === 'int')
                        {
                                $iType = 'number';
                                $maxLen = ' min="0" max="'.str_repeat('9',$size).'"';
                        }
                        elseif(($type === 'varchar') && ($size > 200))
                        {
                                $iType = 'textarea';
                                $maxLen = ' maxlength="'.$size.'"';
                        }
                        elseif($type === 'varchar')
                        {
                                $iType = 'text';
                                $maxLen = ' maxlength="'.$size.'"';
                        }
                        elseif($type === 'timestamp')
                        {
                                $iType = 'date';
                        }
                        elseif($type === 'enum')
                        {
                                $iType = $size;
                        }
                        elseif($type === 'set')
                        {
                                $iType = $size;
                        }
                        
                        if($iType === 'textarea')
                        {
                                echo '<label for="'.str_replace(' ','_',$set).'">'.ucfirst($nomColonne).'</label>'."\r\n";
                                echo '<textarea id="'.str_replace(' ','_',$set).'" name="'.$nomColonne.'"'.$maxLen.'>'."\r\n";
                                echo "</textarea>\r\n";
                        }
                        elseif($type === 'set')
                        {
                                echo '<fieldset>'."\r\n".'<legend>'.ucfirst($nomColonne)."</legend>\r\n";
                                $sets = explode(',',$iType);
                                foreach($sets AS $set)
                                {
                                        $set = str_replace(array("'",'"'),'',$set);
                                        echo '<input type="checkbox" value="'.$set.'" id="'.str_replace(' ','_',$set).'" name="'.$nomColonne.'[]">'."\r\n";
                                        echo '<label for="'.str_replace(' ','_',$set).'">'.ucfirst($set).'</label>'."\r\n";
                                }
                                echo "</fieldset>\r\n";
                        }
                        elseif($nomColonne === 'optin')
                        {
                                $sets = explode(',',$iType);
                                echo '<input type="checkbox" value="oui" id="'.str_replace(' ','_',$nomColonne).'" name="'.$nomColonne.'">'."\r\n";
                                echo '<label for="'.str_replace(' ','_',$nomColonne).'">'.ucfirst($nomColonne).'</label>'."\r\n";
                        }
                        elseif($type === 'enum')
                        {
                                $enums = explode(',',$iType);
                                echo '<label for="'.str_replace(' ','_',$nomColonne).'">'.ucfirst($nomColonne).'</label>'."\r\n";
                                echo '<select id="'.str_replace(' ','_',$nomColonne).'" name="'.$nomColonne.'">'."\r\n";
                                foreach($enums AS $enum)
                                {
                                        $enum = str_replace(array("'",'"'),'',$enum);
                                        echo '<option value="'.$enum.'">'.$enum.'</option>'."\r\n";
                                }
                                echo '</select>'."\r\n";
                        }
                        else
                        {
                                echo '<label for="'.$nomColonne.'">'.ucfirst($nomColonne).'</label>'."\r\n";
                                echo '<input type="'.$iType.'"'.$maxLen.' name="'.$nomColonne.'">'."\r\n";
                        }
                        echo "<br>\r\n";
                }
        }
        echo <<< HTML
<input type="submit">
</form>
HTML;
        $return = ob_get_contents();
        ob_end_clean();
        return $return;
        $vals = array();
        foreach($nomsColonnes AS $n)
        {
                $vals[] = '$r[\''.$n.'\']';
        }
        $php = ucfirst($nomtable)."(".implode(',',$vals).");";
        creerClass($php);
}

function creerClass($php)
{
	/*
	$php = <<< 'TMP'
FormulaireContact($r['idFormulaireContact'],$r['idContact'],$r['interets'],$r['sujet'],$r['message']);
TMP;
	*/
	$separ = explode('(',$php);
	$classe = $separ[0];
	$tmp = $separ[1];
	$tmp = str_replace(array(';','$r[',"'","(",']',')'),'',$tmp);
	$tmp = str_replace(', ',',',$tmp);
	$vars = explode(",",$tmp);
	//var_dump($vars);

	$maj = ucfirst($classe);
	$min = strtolower($classe);

	echo "\r\n\r\n".'    /******************************************************
		 *          '.$min.'
		 ******************************************************/
	';

	echo '    public function insert'.$maj.'($'.$min.'Object)
		{
			if ($'.$min.'Object)
			{
	';

	foreach($vars AS $var)
	{
			echo "                $".$var." = self::escapeit($".$min."Object->".$var.");\r\n";
	}
	echo '
				$query = "INSERT INTO BOUTIQUE_OCCASION.'.$min.' SET ';
	foreach($vars AS $var)
	{
			$values[] = $var." = '\".$".$var.".\"'";
	}
	echo implode(', ',$values)."\";\r\n";
	//      nom = '".$nom."', prenom = '".$prenom."', societe = '".$societe."', adresse = '".$adresse."', complementAdresse1 = '".$complementAdresse1."', complementAdresse2 = '".$complementAdresse2."', cp = '".$cp."', ville = '".$ville."', pays = '".$pays."', telephone1 = '".$telephone1."', telephone2 = '".$telephone2."'";
	echo '            $this->request($query);

				$query = "SELECT LAST_INSERT_ID()";
				$id'.$maj.' = $this->getOne($query);

				return $id'.$maj.';
			}
		}
	';






	echo "==============================================================
	";

	echo "class ".$classe." {\r\n\r\n";
	foreach($vars AS $var)
	{
			echo "\tpublic ".'$'.$var.";\r\n";
	}
	echo "}\r\n\r\n";
}

function code($form)
{
	$code = htmlentities($form);
	$code = str_replace(array('&lt;','&gt'),array('&lt;','&gt'),$code);
}

?>
<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml" lang="fr">
<head>
	<meta charset="utf-8">
	<title>Online converter MySQL table to HTML form</title>
</head>
<body>

<style>
	.mytextarea
	{
		vertical-align: top;
		display: inline-block;
		width: 49%;
		height:40%;
		border: 2px solid whitesmoke;
	}
	.mydiv
	{
		width: 100%;
		height: 300px;/*90%;*/
	}
	textarea
	{
		width: auto;
		/*height:50%;*/
	}
	div
	{
		width: 100%;
		height:100%;
	}
	label
	{
		display: inline-block;
		vertical-align: top;
		width: 90px;
	}
	.code
	{
		background: black;
		color: whitesmoke;
	}
</style>

<div class="mytextarea" style="float:left">
<section>
<h1>Online converter MySQL table to HTML form</h1>
<p>
Convert types INT,VARCHAR,SET and ENUM to INPUT, SELECT, CHECKBOX and TEXTAREA.<br>
VARCHAR over 100 characters becomes TEXTAREA, columns named email become type EMAIL.<br>
MAXLENGHT for text and MAX for numbers are set automatically.<br>
Columns names starting with "id" are not converted.</p>
<script>
  (function(i,s,o,g,r,a,m){i['GoogleAnalyticsObject']=r;i[r]=i[r]||function(){
  (i[r].q=i[r].q||[]).push(arguments)},i[r].l=1*new Date();a=s.createElement(o),
  m=s.getElementsByTagName(o)[0];a.async=1;a.src=g;m.parentNode.insertBefore(a,m)
  })(window,document,'script','//www.google-analytics.com/analytics.js','ga');

  ga('create', 'UA-46793477-1', 'auto');
  ga('send', 'pageview');
</script>

</section>
<?php /*
</div>
	<div class="mytextarea" style="float:left">
	*/ ?>
		<div>
			<legend>SHOW CREATE TABLE `<?php echo $nomtable ?>`</legend>
			<form name="sql2form" method="post">
			<textarea class="mydiv" name="sql"><?php echo $sql ?></textarea><br>
			<input type="submit" value="Convert to form"><br>
			</form>
		</div>
	</div>
	<div class="mytextarea" style="float:right">
		<div>
			<div id="htmlpreview" style="background-color: lightblue"><?php echo $form ?></div>
		</div>
<?php /*	</div>
	<div class="mytextarea" style="float:right"> */
	?>
		<div>
			<legend>HTML code</legend>
			<textarea id="htmlcode" class="code mydiv" name="sql"><?php echo htmlentities($form) ?></textarea>
		</div>
	</div>
</body>
</html>

<script>
	document.getElementById("htmlcode").onkeyup = function() {preview() };
	function preview()
	{
		document.getElementById("htmlpreview").innerHTML = document.getElementById("htmlcode").value;
	}
</script>

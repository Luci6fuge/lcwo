<? 

if (!$_SESSION['uid']) {
echo "Sorry, you must be logged in to use this function.";
return 0;
}


if (in_array($_POST['mode'], array("letters", "figures", "mixed", "custom"))
		&& $_SESSION['uid']) {

	$_SESSION['groups_mode'] = $_POST['mode'];
	$upd = mysqli_query($db,"update lcwo_users set groups_mode ='".$_SESSION['groups_mode']."' where id='".$_SESSION['uid']."'");

	# Abbreviated numbers?
	if (in_array($_POST['abbrev'], array('0','1','2'))) {
		$_SESSION['groups_abbrev'] = $_POST['abbrev'];
			$upd = mysqli_query($db,"update lcwo_users set groups_abbrev = '".$_SESSION['groups_abbrev']."' where id='".$_SESSION['uid']."' limit 1");
	}

	if (!$upd) {
		echo "<p>Error: Updating duration failed. .".mysqli_error($db)."</p>";
	}
}

if (isset($_POST['duration']) && is_numeric($_POST['duration']) && $_SESSION['uid']) {
	$_SESSION['groups_duration'] = $_POST['duration'];
	/* saved in the database */
	$upd = mysqli_query($db,"update lcwo_users set groups_duration='".$_SESSION['groups_duration']."' where id='".$_SESSION['uid']."'");

	if (!$upd) {
		echo "<p>Error: Updating duration failed. .".mysqli_error($db)."</p>";
	}
}


?>

<h1><? echo l('codegroups') ?></h1>

<p>
<form action="/groups" method="POST">
<table>
<tr>
<td><? echo l('changemode') ?>:</td>
<td>
<select onchange="this.form.submit();" name="mode" size="1">
<?
	if ($_SESSION['groups_mode'] == 'letters') {
			echo "<option value=\"letters\"
			selected>".l('letters')."</option>";
	}
	else {
			echo "<option value=\"letters\">".l('letters')."</option>";
	}

	if ($_SESSION['groups_mode'] == 'figures') {
			echo "<option value=\"figures\"
			selected>".l('figures')."</option>";
	}
	else {
			echo "<option value=\"figures\">".l('figures')."</option>";
	}

	if ($_SESSION['groups_mode'] == 'mixed') {
			echo "<option  value=\"mixed\" selected>".l('mixed')."</option>";
	}
	else {
			echo "<option value=\"mixed\">".l('mixed')."</option>";
	}

	if ($_SESSION['groups_mode'] == 'custom') {
			echo "<option  value=\"custom\" selected>".l('customchars')."</option>";
	}
	else {
			echo "<option value=\"custom\">".l('customchars')."</option>";
	}

?>
</select>
</td>
<?
	if ($_SESSION['groups_mode'] == "figures") {
?>
	<td><?=l('abbreviatednumbers')?></td><td>
<select  onchange="this.form.submit();"  name="abbrev" size="1">
	<option value="0" <? is_selected($_SESSION['groups_abbrev'],"0");?>>-</option>
	<option value="1" <? is_selected($_SESSION['groups_abbrev'],"1");?>>1, 9, 0</option>
	<option value="2" <? is_selected($_SESSION['groups_abbrev'],"2");?>>0 - 9</option>
</select>
</td>
<?
	}
?>
<td>&nbsp;<? echo l('changeduration');?>:</td>
<td>
<select  onchange="this.form.submit();"  name="duration" size="1">
<?
	for ($i = 1; $i < 31; $i++) {
		if ($i == $_SESSION['groups_duration']) {
			echo "<option selected>$i</option>";
		}
		else {
			echo "<option>$i</option>";
		}
	}
?>
</select>
</td>
</tr>
</table>
</p>
</form>

<h2><? echo l('codegroups')." (".$_SESSION['groups_duration'] ?>  <?=l("minute")?>)</h2>

<?
if (isset($_POST['text']))  {
	$sent = explode(' ', stripslashes($_POST['text']));	
	$rxtext = my_strtoupper(stripslashes($_POST['input']));
	/* remove superfluous \n and \s */
	$rxtext = preg_replace('/[\s]+/', ' ', $rxtext);
	$rxtext = preg_replace('/[\n]+/', ' ', $rxtext);
	/* strip other shit */
	$rxtext = stripcommands($rxtext);

	/* w2up: accept ; instead of ? */
	if ($_SESSION['groups_mode'] == 'mixed') {
		$rxtext = preg_replace('/;/', '?', $rxtext);
	}

    /* sa7c: accept � for � */
	if ($_SESSION['groups_mode'] == 'custom') {
		$rxtext = preg_replace('/�/', '�', $rxtext);
		$rxtext = preg_replace('/�/', '�', $rxtext);
	}

	$rcvd = preg_split('/[\s\n]+/', $rxtext);

	echo "<table><tr><td valign=\"top\">\n";
	
	echo "<p><strong>".l('result')." (".$_SESSION['cw_speed']."/".$_SESSION['cw_eff']." ".l('wpm')."):</strong></p>\n";

	echo "<table><tr><th>".l('sentgroup')."</th><th>".l('receivedgroup')."</th><th colspan=\"2\">".l('errors')."</th></tr>";
	$i=0;
	foreach ($sent as $group) {
		if ($group == '') { break; }
		$error = check ($group, $rcvd[$i]);
		$totalerrors += $error[1];
		$groups++;
		echo "<tr><td style=\"font-family:monospace\">$group</td><td style=\"font-family:monospace\">".$rcvd[$i]."</td><td>".$error[0]."</td><td>".$error[1]."</td></tr>";
		$i++;
	}
	echo "</table>\n";

	echo "</td><td>&nbsp;&nbsp;</td><td valign=\"top\">\n";
	
    $real = realspeed($_POST['text'], $_SESSION['cw_speed'], $_SESSION['cw_eff']);
	$errpct = (intval(1000*$totalerrors/($real[3]))/10);
	
	if ($errpct > 100) {
	    $errpct = 100;
	}
	
	echo "<p>".l('groups').": $groups (".($real[3])." ".l('characters')."), ".l('errors').": ".  $totalerrors." = $errpct%</p>";

	echo '<p>'.l('realspeed').': '.$real[3].' '.l('characters').' / '.$real[2].
	' '.l('seconds').' = '.$real[1].' '.l('wpm').' / '.$real[0].' '.l('cpm').'</p>';

	if (strlen($_POST['text']) < 255) { 

        $lserrors =
	levenshtein(my_strtoupper(substr($_POST['text'],0,255)), my_strtoupper(substr($rxtext,0,255)));
	$lserrpct = (intval(1000*$lserrors/($real[3]))/10);

	if ($lserrpct > 100) {
		$lserrpct = 100;
	}

	echo "<p><a href='http://en.wikipedia.org/wiki/Levenshtein_distance'>Levenshtein</a>-".l('errors').": ".$lserrors." = $lserrpct %</p>";

	}
	else {
		$lserrpct = 100;
	}

	if (($errpct < 10) || ($lserrpct < 10)) {
        echo "<p><strong>".l('good')."</strong> ".l('goodaccuracy')."</p>";
	}

	echo '<p><a href="/groups" id="newattempt">'.l('continuetraining').'</a></p>';


	echo "</td></tr></table>\n";
?>
<script type="text/javascript">
    document.getElementById('newattempt').focus();
</script>
<?	
	$accuracy = 100-min($errpct, $lserrpct);

	/* only valid for highscores if grouplength = 5 */

	if ($_SESSION['koch_randomlength'] == 5) {
		$valid = 1;
	}
	else {
		$valid = 0;
	}
	
	$in = mysqli_query($db,"insert into lcwo_groupsresults set `uid`='".$_SESSION['uid']."', `mode`='".$_SESSION['groups_mode']."', `speed`='".$_SESSION['cw_speed']."', `eff`='".$_SESSION['cw_eff']."', `accuracy`='$accuracy', `time`=NULL, valid='$valid';");

	if (!$in) {
		echo "<p>Error: Storing result in database
		failed.".mysqli_error($db)."</p>";
	}
			
}
else {

currentparameters();


switch ($_SESSION['groups_mode']) {
		case 'letters':
			$char = $letterschar;
			break;
		case 'figures':
			$char = $figureschar;
			break;
		case 'mixed':
			$char = $mixedchar;
			break;
		case 'custom':
            $char = getcustomcharacters();
            break;
}

$nr = count($char)-1;

$text = my_strtoupper(getgroups($_SESSION['cw_speed'], $_SESSION['cw_eff'], $nr, $char,
				$_SESSION['groups_duration'],
				$_SESSION['koch_randomlength'])); 

# customcharacters could be empty?

if ($nr == -1) {
	echo "<p><strong>".l('nocharacters')."</strong></p>";
	return;
}

# consider abbreviated numbers?
$playertext = $text;
if ($_SESSION['groups_mode'] == 'figures') {
    switch ($_SESSION['groups_abbrev']) {
    case "0":			# No abbreviations
        break;
    case "1":			# 0, 1 and 9
        $playertext = preg_replace('/1/', 'A', $playertext);
        $playertext = preg_replace('/9/', 'N', $playertext);
        $playertext = preg_replace('/0/', 'T', $playertext);
        break;
    case "2":
        $playertext = preg_replace('/1/', 'A', $playertext);
        $playertext = preg_replace('/2/', 'U', $playertext);
        $playertext = preg_replace('/3/', 'V', $playertext);
        $playertext = preg_replace('/5/', 'E', $playertext);
        $playertext = preg_replace('/7/', 'B', $playertext);
        $playertext = preg_replace('/8/', 'D', $playertext);
        $playertext = preg_replace('/9/', 'N', $playertext);
        $playertext = preg_replace('/0/', 'T', $playertext);
        break;
    }
}

if ($_SESSION['player'] != PL_JSCWLIB) {
    if ($_SESSION['groups_mode'] == 'custom') {
        $playertext = "|W".$_SESSION['cw_ews']." ".$playertext;
    }

    if ($_SESSION['delay_start'] > 0) {
		$playertext = '|S'.($_SESSION['delay_start']*1000).' '.$playertext;
    }
}



?>

<div id="formatwarning" style="width:65%;background-color:#cef010;display:none">
<?=l('formatwarning');?>
</div>


<form action="/groups" method="POST" id="eform">
<table>
	<tr>
	<td><textarea spellcheck="false" autocapitalize="off" autocorrect="off" autocomplete="off" name="input" cols="40" rows="10"></textarea></td>
	<td>
	&nbsp;

	<? player($playertext, $_SESSION['player'], $_SESSION['cw_speed'], $_SESSION['cw_eff'],0, 0, 0, 1); ?>
	
	</td>
	</tr>
	<tr>
	<td>
	<?
		$text2 = $text;
            if ($_SESSION['vvv'] == 1 && $_SESSION['player'] != PL_JSCWLIB) { 
                $text2 = substr($text2, 6);
				$text2 = substr($text2, 0, -5);
            }
		$text2 = stripcommands($text2);
	?>
		<input type="hidden" name="text" value="<? echo addquot($text2); ?>">
 <input type="submit" value=" <? echo l('checkresult',1); ?> " onClick="return checkspaces();"> (<? echo l('notcasesensitive'); ?>)
	</td>
</table>


</form>
<?

}


function is_selected ($var, $val) {
	if ($var == $val) {
		echo "selected";
	}
}




?>
<div class="vcsid">$Id: groups.php 246 2014-06-14 17:22:14Z dj1yfk $</div>


<?
include("inc/formatwarning.php");
?>

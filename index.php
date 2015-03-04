<?php
/* ----------------------------------------------------

BIG-TWO

This is a basic implementation of Big-Two in PHP; it
implements the game logic and a basic UI as a starting
point for a fully functional game.

By Alastair Brockwell & Marty Anstey, December 2002

https://marty.anstey.ca/

---------------------------------------------------- */

ob_start();
session_start();

define("_DEBUG",false);

class Player
{
	var $human;
	var $cards_selected;
	var $hand;
	var $multiples;
	var $flushes;
	var $straits;

	function find_hand_info()
	{
		$card = 0;
		for($i=0; $i<count($this->hand); $i++)
		{
			if(($i < floor(($this->hand[$card]) / 4)) || ($card == count($this->hand)))
				$this->multiples[] = array(0);
			else if ($i == floor(($this->hand[$card]) / 4))
			{
				$current = $card;
				while(($card < count($this->hand)) && ($i == floor(($this->hand[$card]) / 4)))
					$card++;
				$this->multiples[] = array($card - $current, $current);
			}
			else
				exit("Huh? $i $card");
		}

		$this->flushes = array(array(),array(),array(),array());
		for($i = 0; $i<count($this->hand); $i++)
			$this->flushes[($this->hand[$i]) % 4][] = $i;
		for($i=0;$i<8;$i++)
		{
			$this->straits[] = true;
		  	for($j=0;$j<5;$j++)
		  		$this->straits[$i] = $this->straits[$i] && $this->multiples[$i + $j][0];
		}
	}

	function validate_selection() {
		if (!$this->cards_selected) return 0;
		$ak = $this->get_cards_selected();
		$c = count($ak);

		if ($c == 1)
			return 1;
		else if ($c == 2)
			return (floor($ak[0]/4) == floor($ak[1]/4))?2:0;
		else if ($c == 3)
			return ((floor($ak[0]/4) == floor($ak[1]/4)) && (floor($ak[1]/4) == floor($ak[2]/4)))?3:0;
		else if ($c == 5) {
			$sflag = false;
			$fflag = false;
			// silly logic
			if(((floor($ak[0]/4) == floor($ak[1]/4)) &&
			   ((floor($ak[2]/4) == floor($ak[3]/4)) &&
			   (floor($ak[3]/4) == floor($ak[4]/4)))) ||
			   (((floor($ak[0]/4) == floor($ak[1]/4)) &&
			   (floor($ak[1]/4) == floor($ak[2]/4))) &&
			   (floor($ak[3]/4) == floor($ak[4]/4)))) return 6;
			if(((floor($ak[1]/4) == floor($ak[2]/4)) &&
			   (floor($ak[2]/4) == floor($ak[3]/4))) &&
			   ((floor($ak[0]/4) == floor($ak[1]/4)) ||
			   (floor($ak[3]/4) == floor($ak[4]/4)))) return 7;
			if((floor($ak[0]/4) == (floor($ak[1]/4) - 1)) &&
			   (floor($ak[0]/4) == (floor($ak[2]/4) - 2)) &&
			   (floor($ak[0]/4) == (floor($ak[3]/4) - 3)) &&
			   (floor($ak[0]/4) == (floor($ak[4]/4) - 4))) $sflag = true;
			if((floor($ak[0]%4) == floor($ak[1]%4)) &&
			   (floor($ak[0]%4) == floor($ak[2]%4)) &&
			   (floor($ak[0]%4) == floor($ak[3]%4)) &&
			   (floor($ak[0]%4) == floor($ak[4]%4))) $fflag = true;
			if($sflag && $fflag) return 8;
			if($sflag) return 4;
			if($fflag) return 5;
			return 0;
		}
		else
			return 0;
	}

	function get_cards_selected()
	{
		$result = array();
		$sk = array_keys($this->cards_selected);
		for ($i=0;$i<count($sk);$i++)
			$result[] = $this->hand[$sk[$i]];
		return $result;
	}

	function remove_cards_selected()
	{
		$sk = array_keys($this->cards_selected);
		for ($i=count($sk) - 1;$i>=0;$i--) array_splice($this->hand, $sk[$i], 1);
	}
}
?>
<form method="post" action="index.php">
<?php
//unset($_SESSION);
if (@$_GET["reset"]) {session_unset(); header("location: index.php");}
if (@$_GET["win"]) $won = $_GET["win"];

if (isset($won)) print "Congratulations player $won!";

if (!isset($_SESSION["state"])) {			// new game!

	$_SESSION["players"] = 2;
	$deck = range(0,51);
	for ($n=0;$n<7;$n++) shuffle($deck);
	for($p=0;$p<$_SESSION["players"];$p++) $_SESSION["player"][] = new Player;

	$_SESSION["player"][0]->human = true;
	$_SESSION["player"][1]->human = true;

	$v = (13 * $_SESSION["players"]);
	while ($v) { for ($p=0;$p<$_SESSION["players"];$p++) $_SESSION["player"][$p]->hand[] = array_shift($deck); $v -= $_SESSION["players"]; }

	if (_DEBUG) {
		//uberhand = 0,4,5,8,9,10,12,13,14,15,16,17,18,25
		$_SESSION["player"][0]->hand = array(0,4,5,8,9,10,12,13,14,15,16,17,25);
		$_SESSION["player"][1]->hand = array(4,8,9,12,13,14,16,17,18,19,20,21,29);
	}

	for ($p=0;$p<$_SESSION["players"];$p++) sort($_SESSION["player"][$p]->hand);

	$first = 0;
	for ($p=1;$p<$_SESSION["players"];$p++) if ($_SESSION["player"][$p]->hand[0] < $_SESSION["player"][$first]->hand[0]) $first = $p;

	$_SESSION["type"] = 0;				// reset play type
	$_SESSION["lastmove"] = null;		// no prev moves
	$_SESSION["state"] = $first;
}

//-- next turn
//
if (@$_POST["pass"]) {
	if($_SESSION["type"] == 0)
		print "You must make a move!";
	else
	{
		$_SESSION["type"] = 0;				// reset play type
		$_SESSION["lastmove"] = null;		// reset
		next_player();
	}
}
else if (@$_POST["turn"]) {
	print_r($_SESSION["player"][$_SESSION["state"]]->cards_selected);

	if ($validate = $_SESSION["player"][$_SESSION["state"]]->validate_selection()) {

		$sel = $_SESSION["player"][$_SESSION["state"]]->get_cards_selected();

		if (!$_SESSION["type"])
		{
			if((count($_SESSION["player"][$_SESSION["state"]]->hand) == 13) && ($sel[0] != $_SESSION["player"][$_SESSION["state"]]->hand[0]))
				print "The first move of the game must involve your lowest card<br/>";
			else
			{
				$_SESSION["type"] = $validate;

				$_SESSION["lastmove"] = $sel;
				$_SESSION["player"][$_SESSION["state"]]->remove_cards_selected();
				next_player();
			}
		}
		else if (($_SESSION["type"] <= 3) && ($_SESSION["type"] != $validate))
			print "Invalid Move Wrong Number<br/>";
		else if ($_SESSION["type"] <= 3) {
			if (end($sel) > end($_SESSION["lastmove"]))
			{
				$_SESSION["lastmove"] = $sel;
				$_SESSION["player"][$_SESSION["state"]]->remove_cards_selected();
				next_player();
			}
			else
				print "Invalid Move Single/Pair/Triple<br/>";
		}
		else if($validate > $_SESSION["type"])
		{
			$_SESSION["type"] = $validate;

			$_SESSION["lastmove"] = $sel;
			$_SESSION["player"][$_SESSION["state"]]->remove_cards_selected();
			next_player();
		}
		else if($validate == $_SESSION["type"])
		{
			if($validate == 4)
			{
				if($sel[4] > $_SESSION["lastmove"][4])
				{
					$_SESSION["lastmove"] = $sel;
					$_SESSION["player"][$_SESSION["state"]]->remove_cards_selected();
					next_player();
				}
				else
					print "Invalid Move Strait<br/>";
			}
			else if(($validate == 5) || ($validate == 8))
			{
				if((floor($sel[4]%4) > floor($_SESSION["lastmove"][4]%4)) ||
				   ((floor($sel[4]%4) == floor($_SESSION["lastmove"][4]%4)) &&
				   (floor($sel[4]/4) > floor($_SESSION["lastmove"][4]/4))))
				{
					$_SESSION["lastmove"] = $sel;
					$_SESSION["player"][$_SESSION["state"]]->remove_cards_selected();
					next_player();
				}
				else
					print "Invalid Move Flush/Strait Flush<br/>";
			}
			else
			{
				if($sel[2] > $_SESSION["lastmove"][2])
				{
					$_SESSION["lastmove"] = $sel;
					$_SESSION["player"][$_SESSION["state"]]->remove_cards_selected();
					next_player();
				}
				else
					print "Invalid Move Full House/4 Kind<br/>";
			}
		}
		else
			print "Invalid Move General<br/>";
	}
	print "VALIDATE: $validate<br/>";
}

//-- allow cards to be selected/unselected
//
if ((isset($_GET['card'])) && ((($v = $_GET["card"])==="0") || (($v = $_GET["card"]))))
	if ($_SESSION["player"][$_SESSION["state"]]->cards_selected[$v])
		unset($_SESSION["player"][$_SESSION["state"]]->cards_selected[$v]);
	else
	{
		$_SESSION["player"][$_SESSION["state"]]->cards_selected[$v]=true;
		ksort($_SESSION["player"][$_SESSION["state"]]->cards_selected);
	}

print "PLAYER $_SESSION[state]'s TURN<br/>LAST MOVE:<table border=0 cellspacing=2 cellpadding=2><tr>";
for ($i=0;$i<count($_SESSION["lastmove"]);$i++) print "<td style=\"border: 2px solid white;\"><img src=img/".$_SESSION["lastmove"][$i].".gif></td>";
print "</tr></table><br/>";

for($p=0;$p<$_SESSION["players"];$p++) {

	print "<table border=0 cellspacing=2 cellpadding=2><tr>";

	if (($_SESSION["player"][$p]->human) && ($_SESSION["state"]==$p) && (!isset($won)))
		for ($i=0;$i<count($_SESSION["player"][$p]->hand);$i++)
			print "<td style=\"border: ".(($_SESSION["player"][$p]->cards_selected[$i])?$b="2px solid blue;":$b="2px solid white;")."\"><a href=\"?card=$i\"><img src=\"img/".$_SESSION["player"][$p]->hand[$i].".gif\" border=\"0\"></a></td>\n";
	else
		for ($i=0;$i<count($_SESSION["player"][$p]->hand);$i++) print "<td style=\"border: 2px solid white;\"><img src=img/back.gif></td>";

	print "</tr></table>\n";
}

print "<br/><br/><a href=\"?reset=true\">Reset Game!</a>";

if (!isset($won)) {
?>
&nbsp;&nbsp;<input type="submit" name="turn" value=" GO ">&nbsp;&nbsp;<input type="submit" name="pass" value=" PASS ">
</form><?php
}

print "<pre>";
print_r($_SESSION["player"]);


function next_player()
{
	if (count($_SESSION["player"][$plyr = $_SESSION["state"]]->hand) <= 0) header("location: index.php?win=$plyr");
	unset($_SESSION["player"][$_SESSION["state"]]->cards_selected);
	if ((++$_SESSION["state"]) >= $_SESSION["players"]) $_SESSION["state"] = 0;
}


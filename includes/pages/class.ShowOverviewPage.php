<?php

/**
 * @package	xNova
 * @version	1.0.x
 * @since	1.0.0
 * @license	http://creativecommons.org/licenses/by-sa/3.0/ CC-BY-SA
 * @link	http://www.razican.com
 * @author	Razican <admin@razican.com>
 */

if ( ! defined('INSIDE')) die(header("Location: ./../../"));

class ShowOverviewPage
{
	function __construct($CurrentUser, $CurrentPlanet)
	{
		global $planetrow, $lang, $db;

		include_once(XN_ROOT.'includes/functions/InsertJavaScriptChronoApplet.php');
		include_once(XN_ROOT.'includes/classes/class.FlyingFleetsTable.php');
		include_once(XN_ROOT.'includes/functions/CheckPlanetUsedFields.php');

		$FlyingFleetsTable = new FlyingFleetsTable();

		$lunarow = doquery("SELECT * FROM `{{table}}` WHERE `id_owner` = '".intval($CurrentPlanet['id_owner'])."' && `galaxy` = '".intval($CurrentPlanet['galaxy'])."' && `system` = '".intval($CurrentPlanet['system'])."' &&  `planet` = '".intval($CurrentPlanet['planet'])."' && `planet_type`='3'",'planets',TRUE);

		if (empty($lunarow))
		{
			unset($lunarow);
		}

		CheckPlanetUsedFields($lunarow);

		$parse 						= $lang;
		$parse['planet_id'] 		= $CurrentPlanet['id'];
		$parse['planet_name'] 		= $CurrentPlanet['name'];
		$parse['galaxy_galaxy'] 	= $CurrentPlanet['galaxy'];
		$parse['galaxy_system'] 	= $CurrentPlanet['system'];
		$parse['galaxy_planet'] 	= $CurrentPlanet['planet'];
		$fpage						= array();
		$flotten					= '';
		$Have_new_message			= '';

		$mode	= isset($_GET['mode']) ? $_GET['mode'] : NULL;
		$Have_new_message	= '';

		switch ($mode)
		{
			case 'renameplanet':

				if (isset($_POST['action']) && $_POST['action'] == $lang['ov_planet_rename_action'])
				{
					$newname = $db->real_escape_string(strip_tags(trim($_POST['newname'])));

					if (preg_match("/[^A-z0-9_\-]/", $newname) == 1)
					{
						message($lang['ov_newname_error'],"game.php?page=overview&mode=renameplanet",2);
					}
					if ($newname != "")
					{
						doquery("UPDATE `{{table}}` SET `name` = '".$newname."' WHERE `id` = '".intval($CurrentUser['current_planet'])."' LIMIT 1;","planets");
					}
				}
				elseif (isset($_POST['action']) && $_POST['action'] == $lang['ov_abandon_planet'])
				{
					return display(parsetemplate(gettemplate('overview/overview_deleteplanet'), $parse));
				}
				elseif (isset($_POST['kolonieloeschen']) && isset($_POST['deleteid']) && intval($_POST['kolonieloeschen']) == 1 && intval($_POST['deleteid']) == $CurrentUser['current_planet'])
				{
					$filokontrol = doquery("SELECT * FROM `{{table}}` WHERE fleet_owner = '".intval($CurrentUser['id'])."' && fleet_start_galaxy='".intval($CurrentPlanet['galaxy'])."' && fleet_start_system='".intval($CurrentPlanet['system'])."' && fleet_start_planet='".intval($CurrentPlanet['planet'])."'",'fleets');

					while ($satir = $filokontrol->fetch_array())
					{
						$kendifilo = $satir['fleet_owner'];
						$digerfilo = $satir['fleet_target_owner'];
						$harabeyeri = $satir['fleet_end_type'];
						$mess = $satir['fleet_mess'];
					}

					$filokontrol = doquery("SELECT * FROM `{{table}}` WHERE fleet_target_owner = '".intval($CurrentUser['id'])."' && fleet_end_galaxy='".intval($CurrentPlanet['galaxy'])."' && fleet_end_system='".intval($CurrentPlanet['system'])."' && fleet_end_planet='".intval($CurrentPlanet['planet'])."'",'fleets');

					while ($satir = $filokontrol->fetch_array())
					{
						$kendifilo = $satir['fleet_owner'];
						$digerfilo = $satir['fleet_target_owner'];
						$gezoay = $satir['fleet_end_type'];
						$mess = $satir['fleet_mess'];
					}

					if ($kendifilo > 0)
					{
						message($lang['ov_abandon_planet_not_possible'],'game.php?page=overview&mode=renameplanet');
					}
					elseif ((($digerfilo > 0) && ($mess < 1)) && $gezoay != 2)
					{
						message($lang['ov_abandon_planet_not_possible'],'game.php?page=overview&mode=renameplanet');
					}
					else
					{
						if (sha1($_POST['pw']) == $CurrentUser["password"] && $CurrentUser['id_planet'] != $CurrentUser['current_planet'])
						{

							doquery("UPDATE `{{table}}` SET `destruyed` = '".(time() + 86400)."' WHERE `id` = '".intval($CurrentUser['current_planet'])."' LIMIT 1;",'planets');
							doquery("UPDATE `{{table}}` SET `current_planet` = `id_planet` WHERE `id` = '".intval($CurrentUser['id'])."' LIMIT 1","users");
							doquery("DELETE FROM `{{table}}` WHERE `galaxy` = '".intval($CurrentPlanet['galaxy'])."' && `system` = '".intval($CurrentPlanet['system'])."' && `planet` = '".intval($CurrentPlanet['planet'])."' && `planet_type` = 3;",'planets');

							message($lang['ov_planet_abandoned'],'game.php?page=overview&mode=renameplanet');
						}
						elseif ($CurrentUser['id_planet'] == $CurrentUser["current_planet"])
						{
							message($lang['ov_principal_planet_cant_abanone'],'game.php?page=overview&mode=renameplanet');
						}
						else
						{
							message($lang['ov_wrong_pass'],'game.php?page=overview&mode=renameplanet');
						}
					}
				}

				return display(parsetemplate(gettemplate('overview/overview_renameplanet'), $parse));
				break;

			default:
				if ($CurrentUser['new_message'])
				{
					$Have_new_message .= "<tr>";
					if ($CurrentUser['new_message'] == 1)
					{
						$Have_new_message .= "<th colspan=4><a href=game.php?page=messages>".$lang['ov_have_new_message']."</a></th>";
					}
					elseif ($CurrentUser['new_message'] > 1)
					{
						$Have_new_message .= "<th colspan=4><a href=game.php?page=messages>";
						$Have_new_message .= str_replace('%m',Format::pretty_number($CurrentUser['new_message']), $lang['ov_have_new_messages']);
						$Have_new_message .= "</a></th>";
					}
					$Have_new_message .= "</tr>";
				}

				$OwnFleets = doquery("SELECT * FROM `{{table}}` WHERE `fleet_owner` = '".intval($CurrentUser['id'])."';",'fleets');

				$Record = 0;

				$fpage	= array();
				while ($FleetRow = $OwnFleets->fetch_array())
				{
					$Record++;

					$StartTime = $FleetRow['fleet_start_time'];
					$StayTime = $FleetRow['fleet_end_stay'];
					$EndTime = $FleetRow['fleet_end_time'];
					/////// // ### LUCKY, CODES ARE BELOW
					$hedefgalaksi = $FleetRow['fleet_end_galaxy'];
					$hedefsistem = $FleetRow['fleet_end_system'];
					$hedefgezegen = $FleetRow['fleet_end_planet'];
					$mess = $FleetRow['fleet_mess'];
					$filogrubu = $FleetRow['fleet_group'];
					$id = $FleetRow['fleet_id'];
					//////
					$Label	= "fs";
					if ($StartTime > time())
					{
						$fpage[$StartTime.$id] = $FlyingFleetsTable->BuildFleetEventTable($FleetRow,0,TRUE, $Label, $Record);
					}

					if (($FleetRow['fleet_mission'] != 4) && ($FleetRow['fleet_mission'] != 10))
					{
						$Label = "ft";

						if ($StayTime > time())
						{
							$fpage[$StayTime.$id] = $FlyingFleetsTable->BuildFleetEventTable($FleetRow,1,TRUE, $Label, $Record);
						}
						$Label = "fe";

						if ($EndTime > time())
						{
							$fpage[$EndTime.$id] = $FlyingFleetsTable->BuildFleetEventTable($FleetRow,2,TRUE, $Label, $Record);
						}
					}

					/**fix fleet table return by jstar**/
					if ($FleetRow['fleet_mission'] == 4 && $StartTime < time() && $EndTime > time())
					{
						$fpage[$EndTime.$id] = $FlyingFleetsTable->BuildFleetEventTable($FleetRow,2,TRUE,"fjstar", $Record);
					}
					/**end fix**/

				}
				$OwnFleets->free_result();
				//iss ye katilan filo////////////////////////////////////

				// ### LUCKY, CODES ARE BELOW
				if ( ! empty($hedefgalaksi) && ! empty($hedefsistem) && ! empty($hedefgezegen) && ! empty($filogrubu))
				{
					$dostfilo = doquery("SELECT * FROM `{{table}}` WHERE `fleet_end_galaxy` = '".intval($hedefgalaksi)."' && `fleet_end_system` = '".intval($hedefsistem)."' && `fleet_end_planet` = '".intval($hedefgezegen)."' && `fleet_group` = '".intval($filogrubu)."';",'fleets');
					$Record1 = 0;
					while ($FleetRow = $dostfilo->fetch_array())
					{
						$StartTime = $FleetRow['fleet_start_time'];
						$StayTime = $FleetRow['fleet_end_stay'];
						$EndTime = $FleetRow['fleet_end_time'];

						$hedefgalaksi = $FleetRow['fleet_end_galaxy'];
						$hedefsistem = $FleetRow['fleet_end_system'];
						$hedefgezegen = $FleetRow['fleet_end_planet'];
						$mess = $FleetRow['fleet_mess'];
						$filogrubu = $FleetRow['fleet_group'];
						$id = $FleetRow['fleet_id'];

						if (($FleetRow['fleet_mission'] == 2) && ($FleetRow['fleet_owner'] != $CurrentUser['id']))
						{
							$Record1++;
							$StartTime = ($mess > 0) ? "" : $FleetRow['fleet_start_time'];

							if ($StartTime > time())
							{
								$Label = "ofs";
								$fpage[$StartTime.$id] = $FlyingFleetsTable->BuildFleetEventTable($FleetRow,0,FALSE, $Label, $Record1);
							}

						}

						if (($FleetRow['fleet_mission'] == 1) && ($FleetRow['fleet_owner'] != $CurrentUser['id']) && ($filogrubu > 0))
						{
							$Record++;
							if ($mess > 0)
							{
								$StartTime = "";
							}
							else
							{
								$StartTime = $FleetRow['fleet_start_time'];
							}
							if ($StartTime > time())
							{
								$Label = "ofs";
								$fpage[$StartTime.$id] = $FlyingFleetsTable->BuildFleetEventTable($FleetRow,0,FALSE, $Label, $Record);
							}

						}

					}
					$dostfilo->free_result();
				}
				//
				//////////////////////////////////////////////////


				$OtherFleets = doquery("SELECT * FROM `{{table}}` WHERE `fleet_target_owner` = '".intval($CurrentUser['id'])."';",'fleets');

				$Record = 2000;
				while ($FleetRow = $OtherFleets->fetch_array())
				{
					if ($FleetRow['fleet_owner'] != $CurrentUser['id'])
					{
						if ($FleetRow['fleet_mission'] != 8)
						{
							$Record++;
							$StartTime = $FleetRow['fleet_start_time'];
							$StayTime = $FleetRow['fleet_end_stay'];
							$id = $FleetRow['fleet_id'];

							if ($StartTime > time())
							{
								$Label = "ofs";
								$fpage[$StartTime.$id] = $FlyingFleetsTable->BuildFleetEventTable($FleetRow,0,FALSE, $Label, $Record);
							}
							if ($FleetRow['fleet_mission'] == 5)
							{
								$Label = "oft";
								if ($StayTime > time())
								{
									$fpage[$StayTime.$id] = $FlyingFleetsTable->BuildFleetEventTable($FleetRow,1,FALSE, $Label, $Record);
								}
							}
						}
					}
				}
				$OtherFleets->free_result();

				$planets_query	= doquery("SELECT * FROM `{{table}}` WHERE id_owner='".intval($CurrentUser['id'])."' && `destruyed` = 0","planets");
				$Colonies		= $planets_query->num_rows;
				$Colony			= 0;
				$AllPlanets		= '';

				while ($CurrentUserPlanet = $planets_query->fetch_array())
				{
					if ($CurrentUserPlanet["id"] != $CurrentUser["current_planet"] && $CurrentUserPlanet['planet_type'] != 3)
					{
						$Colony++;
						if ($Colony%(((MAX_PLAYER_PLANETS-1)/2)>5 ? 5 : ((MAX_PLAYER_PLANETS-1)/2)) === 1 && $Colony != 1) $AllPlanets .= '</tr><tr>';
						$AllPlanets .= "<th>".$CurrentUserPlanet['name']."<br>";
						$AllPlanets .= "<a href=\"game.php?page=overview&cp=".$CurrentUserPlanet['id']."&re=0\" title=\"".$CurrentUserPlanet['name']."\"><img src=\"". DPATH."planeten/small/s_".$CurrentUserPlanet['image'].".jpg\" height=\"88\" width=\"88\"></a><br>";
						$AllPlanets .= "<center>";

						if ($CurrentUserPlanet['b_building'])
						{
							UpdatePlanetBatimentQueueList($CurrentUserPlanet, $CurrentUser);
							if ($CurrentUserPlanet['b_building'])
							{
								$BuildQueue = $CurrentUserPlanet['b_building_id'];
								$QueueArray = explode(";", $BuildQueue);
								$CurrentBuild = explode(",", $QueueArray[0]);
								$BuildElement = $CurrentBuild[0];
								$BuildLevel = $CurrentBuild[1];
								$BuildRestTime = Format::pretty_time($CurrentBuild[3] - time());
								$AllPlanets .= ''.$lang['tech'][$BuildElement].' ('.$BuildLevel.')';
								$AllPlanets .= "<br><font color=\"#7f7f7f\">(".$BuildRestTime.")</font>";
							}
							else
							{
								CheckPlanetUsedFields($CurrentUserPlanet);
								$AllPlanets .= $lang['ov_free'];
							}
						}
						else
						{
							$AllPlanets .= $lang['ov_free'];
						}

						$AllPlanets .= "</center></th>";

						if($Colone <= 1)
						{
							$Colone++;
						}
						else
						{
							$AllPlanets .= "</tr><tr>";
							$Colone = 1;
						}
					}
				}
				$planets_query->free_result();

				if ($lunarow['id'] && $lunarow['destruyed'] != 1 && $CurrentPlanet['planet_type'] != 3)
				{
					if ($CurrentPlanet['planet_type'] == 1 OR $lunarow['id'])
					{
						$moon = doquery("SELECT `id`,`name`,`image` FROM `{{table}}` WHERE `galaxy` = '".intval($CurrentPlanet['galaxy'])."' && `system` = '".intval($CurrentPlanet['system'])."' && `planet` = '".intval($CurrentPlanet['planet'])."' && `planet_type` = '3'",'planets',TRUE);
						$parse['moon'] = '<th><a href="game.php?page=overview&cp='.$moon['id'].'&re=0" title="'.$moon['name'].'"><img src="'.DPATH.'planeten/'.$moon['image'].'.jpg" height="50" width="50"></a><br>'.$moon['name'].' ('.$lang['fcm_moon'].')</th>';
					}
					else
					{
						$parse['moon'] = '';
					}
				}
				else
				{
					$parse['moon_img'] = "";
					$parse['moon'] = "";
				}

				$parse['planet_diameter'] = Format::pretty_number($CurrentPlanet['diameter']);
				$parse['planet_field_current'] = $CurrentPlanet['field_current'];
				$parse['planet_field_max'] = CalculateMaxPlanetFields($CurrentPlanet);
				$parse['planet_temp_min'] = $CurrentPlanet['temp_min'];
				$parse['planet_temp_max'] = $CurrentPlanet['temp_max'];

				$StatRecord = doquery("SELECT `total_rank`,`total_points` FROM `{{table}}` WHERE `stat_type` = '1' && `stat_code` = '1' && `id_owner` = '".intval($CurrentUser['id'])."';",'statpoints',TRUE);

				$parse['user_username'] = $CurrentUser['username'];

				$flotten	= '';
				if (count($fpage) > 0)
				{
					ksort($fpage);
					foreach ($fpage as $time => $content)
					{
						$flotten .= $content."\n";
					}
				}

				if ($CurrentPlanet['b_building'])
				{
					include (XN_ROOT.'includes/functions/InsertBuildListScript.php');

					UpdatePlanetBatimentQueueList($planetrow, $CurrentUser);
					if ($CurrentPlanet['b_building'])
					{
						$BuildQueue = explode(";", $CurrentPlanet['b_building_id']);
						$CurrBuild = explode(",", $BuildQueue[0]);
						$RestTime = $CurrentPlanet['b_building'] - time();
						$PlanetID = $CurrentPlanet['id'];
						$Build = InsertBuildListScript("overview");
						$Build .= $lang['tech'][$CurrBuild[0]].' ('.($CurrBuild[1]).')';
						$Build .= "<br><div id=\"blc\" class=\"z\">".Format::pretty_time($RestTime)."</div>";
						$Build .= "\n<script language=\"JavaScript\">";
						$Build .= "\n	pp = \"".$RestTime."\";\n";
						$Build .= "\n	pk = \"1\";\n";
						$Build .= "\n	pm = \"cancel\";\n";
						$Build .= "\n	pl = \"".$PlanetID."\";\n";
						$Build .= "\n	t();\n";
						$Build .= "\n</script>\n";
						$parse['building'] = $Build;
					}
					else
					{
						$parse['building'] = $lang['ov_free'];
					}
				}
				else
				{
					$parse['building'] = $lang['ov_free'];
				}

				$parse['fleet_list']		= $flotten;
				$parse['Have_new_message']	= $Have_new_message;
				$parse['planet_image']		= $CurrentPlanet['image'];
				$parse['other_planets']		= ( ! empty($AllPlanets)) ? '<tr></tr><td class="c" colspan="2">'.$lang['colonies'].'</td><tr><th colspan="2"><table><tr>'.$AllPlanets.'</tr></table></th><tr>' : '';
				$parse['colspan']			= empty($parse['moon']) ? ' colspan="2"' : '';
				$parse["dpath"]				= DPATH;
				if (read_config('stat') == 0)
					$parse['user_rank'] = Format::pretty_number($StatRecord['total_points'])." (".$lang['ov_place']." <a href=\"game.php?page=statistics&range=".$StatRecord['total_rank']."\">".$StatRecord['total_rank']."</a> ".$lang['ov_of']." ".$CurrentPlanet['total_users'].")";
				elseif (read_config('stat') == 1 && $CurrentUser['authlevel'] < read_config('stat_level'))
					$parse['user_rank'] = Format::pretty_number($StatRecord['total_points'])." (".$lang['ov_place']." <a href=\"game.php?page=statistics&range=".$StatRecord['total_rank']."\">".$StatRecord['total_rank']."</a> ".$lang['ov_of']." ".$CurrentPlanet['total_users'].")";
				else
					$parse['user_rank'] = "-";

				$parse['micronow']	= round(microtime(TRUE)*1000);
				$parse['date']		= show_date();

				return display(parsetemplate(gettemplate('overview/overview_body'), $parse), TRUE, '', FALSE, TRUE, 'time();');
				break;
		}
	}
}


/* End of file class.ShowOverviewPage.php */
/* Location: ./includes/pages/class.ShowOverviewPage.php */
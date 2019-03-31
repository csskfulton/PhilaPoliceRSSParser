<?php


        //INCLUDE NEWSOBJECT CLASS
        include('NewsObject.php');
        include('DistrictInfoObject.php');
        include('PSAObject.php');
        include('CalendarObject.php');
    
	    // MySQL Server Settings
        define("MYSQL_SERVER", 'localhost');
        define("MYSQL_USERNAME", 'root');
        define("MYSQL_PASSWORD", 'Keithistheking');
        define("MYSQL_DATABASE",'PhillyPolice');
       
        define('R_MD5_MATCH', '/^[a-f0-9]{32}$/i'); //MD5 REGEX CHECKER
        define('NUM_ONLY', '/^\d{1,6}$/');
        $IP = $_SERVER['REMOTE_ADDR'];
        $PROTO1 = "http://phillypd.info/api/v1/"; //URL PREFIX
        $IMG_DIR = "http://10.20.30.10/phillyPD/images/"; // IMAGE DIRECTORY
        $data = json_decode(file_get_contents('php://input'),true);
        
        // ERRORS MESSAGES
        $NO_DATABASE = json_encode(array("error"=>"true","msg"=>"Database Does Not Exist"));
        $NO_CONNECTION = json_encode(array("error"=>"true","msg"=>"Could Not Connect To Database"));
        $MYSQL_ERROR = json_encode(array("error"=>"true","msg"=>mysql_error()));
        $INVALID_DEVICE_ID = json_encode(array("error"=>"true","msg"=>"INVALID DEVICE ID"));
        $INVALID_DEVICE = json_encode(array("error"=>"true","msg"=>"INVALID DEVICE BEING USED"));
        $ZERO_HASH_KEYS = json_encode(array("error"=>"true","msg"=>"NO HASH KEYS FOUND"));
        
        
        //CONNECT TO DATABASE
        $CONN = mysqli_connect(MYSQL_SERVER,MYSQL_USERNAME,MYSQL_PASSWORD,MYSQL_DATABASE);
        
        if(mysqli_connect_errno()){
            die($NO_CONNECTION. header('HTTP/1.1 403 Forbidden'));
        }
        
        
        ///////FUNCTIONS/////////////////////
        ////////////////////////////////////////////////////////////
        ////////////////////////////////////////////////////////////////////////////////
    	
			function convertdiv($distNum){

			    $div = null;

			    $south = array("17","3","1");
			    $cen = array("22","6","9");
			    $east = array("25","24","26");
			    $norE = array("15","2","7","8");
			    $norW = array("39","35","14","5");
			    $souW = array("19","16","12","18");

			    if(in_array($distNum,$south)){
			        $div = "South";
			    }else if(in_array($distNum,$cen)){
			        $div = "Central";
			    }else if(in_array($distNum,$east)){
			        $div = "East";
			    }else if(in_array($distNum,$norE)){
			        $div = "Northeast";
			    }else if(in_array($distNum,$norW)){
			        $div = "Northwest";
			    }else if(in_array($distNum,$souW)){
			        $div = "Southwest";
			    }

			    return $div;

			}
    				
    				
			function cleanUpHTML($ret){ // USED TO TRIM (HTML) THE DESCRIPTION OF THE NEW STORY
			    
			    $fire = $ret;
			    
			    if(strpos($fire,"&#8217;") >=1){
			        $fire = str_replace("&#8217;","'",$fire);
			    }
			    
			    if(strpos($fire,"&#8243;") >=1){
			        $fire = str_replace("&#8243;","'",$fire);
			    }
			    if(strpos($fire,"&#8220;") >=1){
			        $fire = str_replace("&#8220;","'",$fire);
			    }
			    
			    if(strpos($fire,"&#8221;") >=1){
			        $fire = str_replace("&#8221;","'",$fire);
			    }
			    if(strpos($fire,"&#8242;") >=1){
			        $fire = str_replace("&#8242;","'",$fire);
			    }
			    if(strpos($fire,"&#039;") >=1){
			        $fire = str_replace("&#039;","â€²",$fire);
			    }
			    if(strpos($fire,"&amp;") >=1){
			        $fire = str_replace("&amp;","&",$fire);
			    }
			    if(strpos($fire,"&#215;") >=1){
			        $fire = str_replace("&#215;","X",$fire);
			    }
			    if(strpos($fire,"&#8211;") >=1){
			    $fire = str_replace("&#8211;","â€“",$fire);
			    }
			    
			    return $fire;
			}
	
			///////END of FUNCTIONS/////////////////////
			////////////////////////////////////////////////////////////
			////////////////////////////////////////////////////////////////////////////////
			

		
//////////////////////////////////////////////// START CLIENT KEY EXCHANGE//////////////////////////////////////////////////////////////		
		
		///  CLIENT UPDATE CHECKER /////
    	if($_SERVER['REQUEST_METHOD'] == 'GET' && $_GET['Update'] == "true" || $_SERVER['REQUEST_METHOD'] == 'POST' && $data['Update'] == "true"){
				
			// CHECK THE HASH FOR A MD5 HASH  
    		if(preg_match(R_MD5_MATCH, $data['DeviceID']) || preg_match(R_MD5_MATCH, $_GET['DeviceID'])){  //CHECK FOR VALID HASH
				
    		    $devID = mysqli_real_escape_string($data['DeviceID']); // ESCAPE MALFORM STRING
				
    				if(!empty($data['DeviceID'])){
    				    $devID = $data['DeviceID'];
    				}else if(!empty($_GET['DeviceID'])){
    				    $devID = $_GET['DeviceID'];
    				}
    				
				$isDev = "SELECT `DeviceID` FROM `Devices` WHERE `DeviceID` = '$devID'";
				$is_res = mysqli_query($CONN, $isDev) or die($MYSQL_ERROR. header('HTTP/1.1 403 Forbidden'));
				
					// IF THE DEVICE EXIST ALREDY IN THE DATABASE; UPDATE THE TIMESTAMP AND IP
					if(mysqli_num_rows($is_res) >=1){
							
						$up_res = "UPDATE `Devices` SET `TimeStamp` = NOW(), `LastRequestIP` = '$IP' WHERE `DeviceID` = '$devID'";
						$in_rec = mysqli_query($CONN, $up_res);
							
							if($in_rec){
								/// FETCH THE NEW HASHES
								$array = array();
								$get_nHash = "SELECT `HashName`, `Hash` FROM `CurrentHash`";
								$h_res = mysqli_query($CONN, $get_nHash);
									
									if(mysqli_num_rows($h_res) >=1){
											
										while($row = mysqli_fetch_array($h_res)){
											
											$hashName = $row['HashName'];
											$HASH = $row['Hash'];
											$obj = array("HashName"=>$hashName,"Hash"=>$HASH);
											
											array_push($array, $obj);
										}
										
											echo json_encode(array("HashKeys"=>$array,"error"=>"false"));
										
									}else{
										// FAILED TO GET HASH KEYS
									    echo $ZERO_HASH_KEYS;
									}
		
								
							}else{
								// UPDATE DEVICE EXHNACGE FAILED
							    echo $MYSQL_ERROR;
							}
					
					}else{
						// COULD NOT FIND THE DEVICE ID OF DEVICE
					    echo $INVALID_DEVICE;
					}
		

			}else{
				// NOT A VAILD HASHTAG SENT TO SERVER 
			    echo $INVALID_DEVICE_ID;
			}
		
		
		
		}
		
////////////////////////////////////////////////////////// END CLIENT KEY EXCHANGE //////////////////////////////////////////////////////////////			
		
		
		
		
		
////////////////////////////////////////////////////////// START LATEST NEWS UPDATE //////////////////////////////////////////////////////////////
		
		
	

		if($_SERVER['REQUEST_METHOD'] == 'GET' && $_GET['LatestNews'] == 'true' || $_SERVER['REQUEST_METHOD'] == 'POST' && $data['LatestNews'] == 'true'){
		   
		    if(preg_match(NUM_ONLY, $data['Start']) && preg_match(NUM_ONLY, $data['End'])|| preg_match(NUM_ONLY, $_GET['Start']) && preg_match(NUM_ONLY, $_GET['End'])){  //CHECK FOR NEWSOBJECT NUMBER

		    
		    if(!empty($_GET['Start']) && !empty($_GET['End'])){
		        $srt = $_GET['Start'];
		        $end = $_GET['End'];
		    }else if(!empty($data['Start']) && !empty($data['End'])){
		        $srt = $data['Start'];
		        $end = $data['End'];
		    }else{
		        $srt = 0;
		        $end = 5;
		    }
		    
    		    $NEW_HASH = 0;
    		    
    		    
    		    $get_sql = "SELECT `Hash` FROM `CurrentHash` WHERE `HashName` = 'NewsStory'";
    		    $res = mysqli_query($CONN, $get_sql);
    		    
    		    if(mysqli_num_rows($res) >=1){
    		        
    		        $row = mysqli_fetch_array($res);
    		        $NEW_HASH = $row['Hash'];
    		        
    		        $array2 = array();
    		        $array = array();
    		        $query = "SELECT SQL_CALC_FOUND_ROWS `ID`,`Category`,`PubDate`,`StoryAuthor`,`Title`,`Description`,`ImageURL`,`TubeURL` FROM `NewsStory` WHERE `ScrapeHash` = '$NEW_HASH' ORDER BY `PubDate` DESC LIMIT $srt,$end";
    		        $cquery = "SELECT FOUND_ROWS() AS ROWS";
    		        $result = mysqli_query($CONN, $query);
    		        $cresult = mysqli_query($CONN, $cquery);
    		        $cat = mysqli_fetch_array($cresult);
    		        
    		        while($row = mysqli_fetch_array($result)){
    		            
    		            $newzObj = new NewsObject();
    		            $newzObj->setCategory($row['Category']);
    		            $newzObj->setPubDate(date('M j, g:i A',strtotime($row['PubDate'])));
    		            $newzObj->setStoryAuthor($row['StoryAuthor']);
    		            $newzObj->setNewsStoryID($row['ID']);
    		            $newzObj->setDescription(cleanUpHTML(utf8_encode($row['Description'])));
    		            $newzObj->setTitle($row['Title']);
   		                //$newzObj->setTitle(cleanUpHTML(utf8_encode($row['Description'])));

    		            $imgURLL = $row['ImageURL'];
    		            $SQLL = "SELECT `LocalImageURL` FROM `Images` WHERE `RemoteImageURL` = '$imgURLL'";
    		            $REZZ = mysqli_query($CONN, $SQLL);

    		            if(mysqli_num_rows($REZZ) >=1){
    		                $ROWW = mysqli_fetch_array($REZZ);
    		                $pre = $ROWW['LocalImageURL'];
    		                $newzObj->setImageURL($IMG_DIR.$pre);
    		            }else{
   		                $newzObj->setImageURL($row['ImageURL']);
   		                }

    		            if(empty($row['TubeURL'])){
    		                $newzObj->setTubeURL("No Video");
    		            }else{
    		               $newzObj->setTubeURL($row['TubeURL']);
   		                }
                    		            
   		               array_push($array2,$newzObj);   
                        
    		        }
    		        
    		        echo json_encode(array("News"=>$array2,"TotalCount"=>$cat['ROWS']));
    		      
    		        
    		    }else{
    		        echo $ZERO_HASH_KEYS;
    		    }
		    
		}
		    
		    }
		    
		    
////////////////////////////////////////////////////////// END LATEST NEWS UPDATE //////////////////////////////////////////////////////////////
		    
		    
		    
		    
		    
////////////////////////////////////////////////////////// START DISTRICT INFO //////////////////////////////////////////////////////////////
		    
		    
		    
		

		    else if($_SERVER['REQUEST_METHOD'] == 'GET' && $_GET['DistrictInfo'] == "true" || $_SERVER['REQUEST_METHOD'] == 'POST' && $data['DistrictInfo'] == "true"){
		        
		        if(!empty($_GET['DistrictNumber'])){
		            $dist_num = $_GET['DistrictNumber'];
		        }else if(!empty($data['DistrictNumber'])){
		            $dist_num = $data['DistrictNumber'];
		        }
		        
		        
		        $array = array();
		        $array1 = array();
		        $array2 = array();
		        $query = "SELECT * FROM `DistrictInfo` WHERE `DistrictNumber` = '$dist_num'";
		        $query1 = "SELECT * FROM `PSA` WHERE `DistrictNumber` = '$dist_num'";
		        //$query2 = "SELECT * FROM `DistrictCalendar` WHERE `DistrictNumber` = '$dist_num' AND `DistrictDate` > DATE_FORMAT(NOW(), '%b %e, %Y %h :%i %p') LIMIT 0,3";
		        $query2 = "SELECT * FROM `Calendar` WHERE `DistrictNumber` = '$dist_num' ORDER BY `Calendar`.`TimeStamp` DESC LIMIT 0,7";
		        
		        $result = mysqli_query($CONN, $query)or die('Bad Query '.mysql_error());
		        if(mysqli_num_rows($result) >0){
		            $row = mysqli_fetch_array($result);        
		            
		            $disObj = new DistrictInfoObject();
		            $disObj->setDistrictNumber($row['DistrictNumber']);
		            $disObj->setLocationAddress($row['LocationAddress']);
		            $disObj->setPhoneNumber($row['Phone']);
		            $disObj->setEmailAddress($row['EmailAddress']);
		            $disObj->setCaptainName($row['CaptainName']);
		            $disObj->setCaptainImageURL($row['CaptainURL']);
		            $disObj->setDetectiveDivision($row['DetectiveDivision']);

		            
		        }
		        
		        
		        $result1 = mysqli_query($CONN, $query1) or die('Bad query 1');
		        while($row1 = mysqli_fetch_array($result1)){

		            $psaObj = new PSAObject();
		            $psaObj->setPSAID($row1['ID']);
		            $psaObj->setDistrictNumber($row1['DistrictNumber']);
		            $psaObj->setEmailAddress($row1['Email']);
		            $psaObj->setPSAAreaNumber($row1['PSAAreaNum']);
		            $psaObj->setLieutenantName($row1['LieutenantName']);
		            array_push($array1,$psaObj);

		            
		        }
		        
		        
		        
		        $result2 = mysqli_query($CONN, $query2)or die('Bad query2');
		        while($row2 = mysqli_fetch_array($result2)){

		            $calObj = new CalendarObject();
		            $calObj->setTimeStamp($row2['TimeStamp']);
		            $calObj->setDistrictNumber($row2['DistrictNumber']);
		            $calObj->setTitle($row2['Title']);
		            $calObj->setMeetDate($row2['MeetDate']);
		            $calObj->setMeetLocation($row2['MeetLocation']);
		            array_push($array2, $calObj);
		            
		            
		        }
		        
		        echo json_encode(array("DistrictInfo"=>$disObj,"PSAInfo"=>$array1,"CalenderInfo"=>$array2));
		        
}




////////////////////////////////////////////////////////// END DISTRICT INFO //////////////////////////////////////////////////////////////





////////////////////////////////////////////////////////// START DISTRICT NEWS //////////////////////////////////////////////////////////////



else if($_SERVER['REQUEST_METHOD'] == 'GET' && $_GET['DistrictNews'] == "true" || $_SERVER['REQUEST_METHOD'] == 'POST' && $data['DistrictNews'] == "true"){
                
    if(!empty($_GET['DistrictNumber'])){
        $district = $_GET['DistrictNumber'];
        if(!empty($_GET['End']) && !empty($_GET['Start'])){
                $srt = $_GET['Start'];
                $end = $_GET['End'];
            }else{
                $srt = 0;
                $end = 5;
            }
    }else if(!empty($data['DistrictNumber'])){
        $district = $data['DistrictNumber'];
        if(!empty($data['End']) && !empty($data['Start'])){
            $srt = $data['Start'];
            $end = $data['End'];
        }else{
            $srt = 0;
            $end = 5;
        }
    }
        	    
        	    
                    //$district = $_GET['d'];
                    $array = array();
                    $query = "SELECT SQL_CALC_FOUND_ROWS * FROM `NewsStory` WHERE `DistrictNumber` = $district ORDER BY `TimeStamp` DESC LIMIT $srt,$end";
                    $cquery = "SELECT FOUND_ROWS() AS ROWS";
                    
                    $result = mysqli_query($CONN, $query) or die('Bad Query '.mysql_error());
                    $cresult = mysqli_query($CONN, $cquery);
                    
                    $ct = mysqli_fetch_array($cresult);
                    while($row = mysqli_fetch_array($result)){
                        $title = $row['Title'];
                        $captionURL = $row['ImageURL'];
                        
                        
                        $sel = "SELECT `LocalImageURL` FROM `Images` WHERE `RemoteImageURL` = '$captionURL'";
                        $r_Q = mysqli_query($CONN, $sel);
                        if(mysqli_num_rows($r_Q) >=1){
                            $rowz = mysqli_fetch_array($r_Q);
                            $pre = $rowz['LocalImageURL'];
                            $captionURL = $IMG_DIR.$pre;
                        }
                        
                        // $mdate = new DateTime($row['StroyDate']);
                        // $date = $mdate->format("M jS, g:s A");
                        $date = date('M j, g:i A',strtotime($row['PubDate']));
                        
                        $excert = cleanUpHTML(utf8_encode($row['Description']));
                        $storyURL = trim($row['URL']);
                        $storyID = $row['ID'];
                        $districtNumber = $row['DistrictNumber'];
                        $videoURL = $row['TubeURL'];
                        //				$description = utf8_encode($row['Description']);
                        $alertType = $row['Category'];
                        
                        if($alertType == "missing person"){
                            $alertType = "Missing Person";
                        }
                        
                        $author = $row['StoryAuthor'];
                        
                        if(empty($videoURL)){
                            $videoURL = "No Video";
                        }
                        
                        $article = array("StoryID"=>$storyID,"AlertType"=>$alertType,"District"=>$districtNumber,"Title"=>$title,"Excert"=>$excert,"StoryDate"=>$date,
                            "CaptionURL"=>$captionURL,"StoryURL"=>$storyURL,"VideoURL"=>$videoURL,"Author"=>$author);
                        array_push($array, $article);
                        //print_r($article);
                    }
                    
                    echo json_encode(array("Articles"=>$array,"TotalCount"=>$ct['ROWS']));
                    //echo json_encode($array);
                    // $aa = array_map(utf8_encode, $array);
                    // echo json_encode($aa);
            
        }
		
        
		
////////////////////////////////////////////////////////// END DISTRICT NEWS //////////////////////////////////////////////////////////////
        
        
		
		
		
		
		
		
		

		
		
		
///////////////////////////////////////////////////////// START CLIENT APIs ///////////////////////////////////////////////////////////////////		
		
		else if($_SERVER['REQUEST_METHOD'] == 'POST' && $data['isAgreement'] == 'true'){
			$devID = $data['DeviceID'];
				
				if(preg_match(R_MD5_MATCH, $devID)){
					
					$q = "SELECT `DeviceID` FROM `Devices` WHERE `DeviceID` = '$devID'";
					$res1 = mysqli_query($CONN, $q);
						
						if(mysqli_num_rows($res1) >=1){
							// record already exist
							echo json_encode(array("error"=>"false","msg"=>"success"));
						}else{
							// No Record exist please input
							$in = "INSERT INTO `Devices` (`DeviceID`,`LastRequestIP`)VALUES('$devID','$IP')";
							$res = mysqli_query($CONN, $in) or die($MYSQL_ERROR. header('HTTP/1.1 403 Forbidden'));
						
								if($res){
									echo json_encode(array("error"=>"false","msg"=>"success"));
								}else{
									echo json_encode(array("error"=>"true","msg"=>mysql_error()));
								}
						}
						
					
				}else{
					echo json_encode(array("error"=>"true","msg"=>"INVAID DEVICE ID"));
				}
				
				
				
		}
		
			
			
			
			else if($_SERVER['REQUEST_METHOD'] == 'POST' && $data['District_Update'] == 'true'){
				//else if($_SERVER['REQUEST_METHOD'] == 'GET' && $data['District_Update'] == 'true'){
					
				$array = array();	
				$devID = $data['DeviceID'];
				$dis_arr = $data['Districts'];
				$is_Vid = $data['UC_Videos'];
				$hash_T = $data['HashTag'];
				$hash = 0;


					if(preg_match(R_MD5_MATCH, $devID)){
						
						$q = "SELECT `DeviceID` FROM `Devices` WHERE `DeviceID` = '$devID'";
						$res = mysqli_query($CONN, $q) or die($MYSQL_ERROR. header('HTTP/1.1 403 Forbidden'));
							
							if(mysqli_num_rows($res) >=1 ){
								
								if(!is_null($hash_T) && !is_null($dis_arr)){
									
									$ob_ay = array();
									$oj_ary1 = array();
									$tall = join("', '", $dis_arr);
									$gq = "SELECT SQL_CALC_FOUND_ROWS * FROM `NewsStory` WHERE `ScrapeHash` = '$hash_T' AND `DistrictNumber` IN ('$tall')";
									$gqq = "SELECT SQL_CALC_FOUND_ROWS * FROM `UCVideos` WHERE `HashTag` = '$hash_T'";
									$gq1 = "SELECT FOUND_ROWS() AS ROWS";
									$gqq1 = "SELECT FOUND_ROWS() AS ROWS";
									$timeSTP = "";
									
									$rqq = mysqli_query($CONN, $gq);
									$rq2 = mysqli_query($CONN, $gq1);
									$nob = mysqli_fetch_array($rq2);
									
									$rqqq = mysqli_query($CONN, $gqq);
									$rqq2 = mysqli_query($CONN, $gqq1);
									$vob = mysqli_fetch_array($rqq2);
										
										if(mysqli_num_rows($rqq) >=1){
												
											while($rop = mysqli_fetch_array($rqq)){
												$dist = $rop['DistrictNumber'];
												$title = $rop['Title'];
												$desc = utf8_encode($rop['Description']);
												$s_date = date('M j, g:i A',strtotime($rop['PubDate']));
												$timeSTP = date('M j, g:i A',strtotime($rop['TimeStamp']));
												$cat = $rop['Category'];
												$img = $rop['ImageURL'];
												
												$sel = "SELECT `LocalImageURL` FROM `Images` WHERE `RemoteImageURL` = '$img'";
												$r_Q = mysqli_query($CONN, $sel);
												if(mysqli_num_rows($r_Q) >=1){
												    $roq = mysqli_fetch_array($r_Q);
												    $pre = $roq['LocalImageURL'];
												    $img = $PROTO1."images/".$pre;
												}
												
												$news_id = $rop['ID'];
												$vid_URL = $rop['TubeURL'];
												$author = $rop['StoryAuthor'];
												
												$n_obj = array("NewsID"=>$news_id,"District"=>$dist,"Title"=>$title,"Description"=>$desc,"Author"=>$author,
																	"PubDate"=>$s_date,"Category"=>$cat,"ImageURL"=>$img,"TubeURL"=>$vid_URL);
											
												array_push($ob_ay,$n_obj);
											}
											
										}else{
											/// NO NEWS STORIES
										}
										
										if(mysqli_num_rows($rqqq) >=1){
											
											while($rog = mysqli_fetch_array($rqqq)){
												$id = $rog['ID'];
												$vidT = $rog['VideoTitle'];
												$des = $rog['Description'];
												$vID = $rog['VideoID'];
												$vimgURL = $rog['VideoImageURL'];
												$vidDate = $rog['VideoDate'];
												$Pdiv = $rog['PoliceDivision'];
												$crimeT = $rog['CrimeType'];
												
												$ucc_obj = array("ID"=>$id,"VideoTitle"=>$vidT,"Description"=>$des,"VideoID"=>$vID,"VideoImageURL"=>$vimgURL,
																	"VideoDate"=>$vidDate,"PoliceDivision"=>$Pdiv,"CrimeType"=>$crimeT);
												
												array_push($oj_ary1, $ucc_obj);
											}
											
										}else{
											// NO VIDEO RECORDS
										}
										
										
										echo json_encode(array("NewsStories"=>$ob_ay,"NewsTotalCount"=>$nob['ROWS'],"VideoObjects"=>$oj_ary1,"VideoTotalCount"=>$vob['ROWS'],"TimeStamp"=>$timeSTP));
										
								}
								
									if($is_Vid == "false"){
											
										$query = "SELECT `Hash` FROM `CurrentHash` WHERE `HashName` = 'NewsStory'";
										$r_hash = mysqli_query($CONN, $query);
										
											if(mysqli_num_rows($r_hash) >=1){
													
												$var = mysqli_fetch_array($r_hash);
												$hash = $var['Hash'];
											
											}else{
													
												$hash = "";
											}
								
											$tall = join("', '", $dis_arr);
											$sql = "SELECT SQL_CALC_FOUND_ROWS * FROM `NewsStory` WHERE `ScrapeHash` = '$hash' AND `DistrictNumber` IN ('$tall')";
											$sql1 = "SELECT FOUND_ROWS() AS ROWS";
											$res1 = mysqli_query($CONN, $sql);
											$res2 = mysqli_query($CONN, $sql1);
											$ct = mysqli_fetch_array($res2);
											$obj_array = array();	
											$timeSTP = "";
											
												while($row = mysqli_fetch_array($res1)){
														
													$dist = $row['DistrictNumber'];
													$title = $row['Title'];
													$desc = utf8_encode($row['Description']);
													$s_date = date('M j, g:i A',strtotime($row['PubDate']));
													$timeSTP = date('M j, g:i A',strtotime($rop['TimeStamp']));
													$cat = $row['Category'];
													$img = $row['ImageURL'];
													
													$sel = "SELECT `LocalImageURL` FROM `Images` WHERE `RemoteImageURL` = '$img'";
													$r_Q = mysqli_query($CONN, $sel);
												if(mysqli_num_rows($r_Q) >=1){
												    $roww = mysqli_fetch_array($r_Q);
												    $pre = $roww['LocalImageURL'];
												    $img = $PROTO1."images/".$pre;
												}
													
													$news_id = $row['ID'];
													$vid_URL = $row['TubeURL'];
													$author = $row['StoryAuthor'];
													
													$n_obj = array("NewsID"=>$news_id,"District"=>$dist,"Title"=>$title,"Description"=>$desc,"Author"=>$author,
																		"PubDate"=>$s_date,"Category"=>$cat,"ImageURL"=>$img,"TubeURL"=>$vid_URL);
												
													array_push($obj_array,$n_obj);
												
												}
									
											
												echo json_encode(array("NewsTotalCount"=>$ct['ROWS'],"NewsObjects"=>$obj_array,"VideoTotalCount"=>"0","VideoObjects"=>"0","TimeStamp"=>$timeSTP));
										
										
										
										
										
									}else if($is_Vid == "true"){
											
										$query2 = "SELECT `Hash` FROM `CurrentHash` WHERE `HashName` = 'UCVideos'";
										$query3 = "SELECT `Hash` FROM `CurrentHash` WHERE `HashName` = 'NewsStory'";
										$uc_hash = 0;
										$ns_hash = 0;
										$obj_array = array();
										$obj_array1 = array();
										$r_hash2 = mysqli_query($CONN, $query2);
										$r_hash3 = mysqli_query($CONN, $query3);
											
											if(mysqli_num_rows($r_hash2) >=1){
													
												$var2 = mysqli_fetch_array($r_hash2);
												$uc_hash = $var2['Hash'];
											
											}else{
													
												$uc_hash = "";
											}
											
											
											if(mysqli_num_rows($r_hash3) >=1 ){
													
												$var3 = mysqli_fetch_array($r_hash3);
												$ns_hash = $var3['Hash'];
											
											}else{
												
												$ns_hash = "";
											}
											
											$tall = join("', '", $dis_arr);
											$sql = "SELECT SQL_CALC_FOUND_ROWS * FROM `NewsStory` WHERE `ScrapeHash` = '$ns_hash' AND `DistrictNumber` IN ('$tall')";
											$sql1 = "SELECT FOUND_ROWS() AS ROWS";
											$res1 = mysqli_query($CONN, $sql);
											$res2 = mysqli_query($CONN, $sql1);
											$ct = mysqli_fetch_array($res2);
												
											
												while($row = mysqli_fetch_array($res1)){
														
													$dist = $row['DistrictNumber'];
													$title = $row['Title'];
													$desc = utf8_encode($row['Description']);
													$s_date = date('M j, g:i A',strtotime($row['PubDate']));
													$cat = $row['Category'];
													$img = $row['ImageURL'];
													
													$sel = "SELECT `LocalImageURL` FROM `Images` WHERE `RemoteImageURL` = '$img'";
													$r_Q = mysqli_query($CONN, $sel);
												if(mysqli_num_rows($r_Q) >=1){
												    $rowx = mysqli_fetch_array($r_Q);
												    $pre = $rowx['LocalImageURL'];
												    $img = $PROTO1."images/".$pre;
												}
													
													$news_id = $row['ID'];
													$vid_URL = $row['TubeURL'];
													$author = $row['StoryAuthor'];
													
													$n_obj = array("NewsID"=>$news_id,"District"=>$dist,"Title"=>$title,"Description"=>$desc,"Author"=>$author,
																		"PubDate"=>$s_date,"Category"=>$cat,"ImageURL"=>$img,"TubeURL"=>$vid_URL);
												
													array_push($obj_array,$n_obj);
												
												}


												$qvid = "SELECT * FROM `UCVideos` WHERE `HashTag` = '$uc_hash'";
												$sql5 = "SELECT FOUND_ROWS() AS ROWS";
												$res5 = mysqli_query($CONN, $qvid);
												$res6 = mysqli_query($CONN, $sql5);
												$ct1 = mysqli_fetch_array($res6);
											
													while($row1 = mysqli_fetch_array($res5)){
														
														$vidT = $row1['VideoTitle'];
														$des = $row1['Description'];
														$vID = $row1['VideoID'];
														$vimgURL = $row1['VideoImageURL'];
														$vidDate = $row1['VideoDate'];
														$Pdiv = $row1['PoliceDivision'];
														$crimeT = $row1['CrimeType'];
														
														$ucc_obj = array("VideoTitle"=>$vidT,"Description"=>$des,"VideoID"=>$vID,"VideoImageURL"=>$vimgURL,
																			"VideoDate"=>$vidDate,"PoliceDivision"=>$Pdiv,"CrimeType");
														
														array_push($obj_array1, $ucc_obj);	
														
																		
													}
											
											
													echo json_encode(array("NewsTotalCount"=>$ct['ROWS'],"NewsObjects"=>$obj_array,"VideoTotalCount"=>$ct1['ROWS'],"VideoObjects"=>$obj_array1));
											
											
									}
									
								
									
										
									
								
							}else if(mysqli_num_rows($res) <=0 ) {
									
								echo json_encode(array("error"=>"true","msg"=>"No Record INVaIlid DEviCe"));
							
							}else{
									
								echo json_encode(array("error"=>"true","msg"=>mysql_error()));
							
							}
					}
			}
		
		


//////////////////////////////////////////////////////////////////////////////////// UCVIDEOS API //////////////////////////////////////////////////////////////////////////////////////////////////////////////




			else if($_SERVER['REQUEST_METHOD'] == 'GET' && $_GET['UCVideos'] == "true" || $_SERVER['REQUEST_METHOD'] == 'POST' && $data['UCVideos'] == "true"){
							
// 									$devID = $data['DeviceID'];
// 									$hashTag = $data['HashTag'];
// 									$dist = $data['District'];
// 									$div = $data['Division'];
// 									$start = $data['Start'];
// 									$end = $data['End'];
// 									$div = $_GET['d'];
						
						
									
									
									if(!empty($_GET['UCVideos'])){
									    $district = $_GET['District'];
									    if(!empty($_GET['End']) && !empty($_GET['Start'])){
									        $start = $_GET['Start'];
									        $end = $_GET['End'];
									    }else{
									        $start = 0;
									        $end = 5;
									    }
									}else if(!empty($data['District'])){
									    $district = $data['District'];
									    if(!empty($data['End']) && !empty($data['Start'])){
									        $start = $data['Start'];
									        $end = $data['End'];
									    }else{
									        $start = 0;
									        $end = 5;
									    }
									}
						
			                         
									
									$array = array();
									//$query = "SELECT SQL_CALC_FOUND_ROWS * FROM `UCVideos` WHERE `PoliceDivision` = '$div' AND `VideoID` != '0' AND `VideoDate` > DATE_FORMAT(NOW(), '%b %e, %Y') ORDER BY STR_TO_DATE( `VideoDate` , '%b %e, %Y' ) DESC LIMIT $start,$end";
									if(!empty($district)){
									    $query = "SELECT SQL_CALC_FOUND_ROWS * FROM `UCVideos` WHERE `VideoID` != '0' AND `DistrictNumber` = '$district'  ORDER BY  `TimeStamp` DESC LIMIT $start,$end";
									    
									}else{
									    $query = "SELECT SQL_CALC_FOUND_ROWS * FROM `UCVideos` WHERE `VideoID` != '0' AND `DistrictNumber` != 0  ORDER BY  `TimeStamp` DESC LIMIT $start,$end";
									    
									}
									
									
									$cquery = "SELECT FOUND_ROWS() AS ROWS";
									$result = mysqli_query($CONN, $query) or die('Bad Query '.mysql_error());
									$cresult = mysqli_query($CONN, $cquery);
										$cat = mysqli_fetch_array($cresult);
										if(mysqli_num_rows($result) > 0){
											while($row = mysqli_fetch_array($result)){
												$id = $row['ID'];
												$title = $row['VideoTitle'];
												
												if(strpos($title," DC ") >=1){
												    $txt = explode(" DC",$title);
												    $title = $txt[0];
												}
												
												
												
												$captionURL = $row['VideoImageURL'];
												$videoDate = $row['VideoDate'];
												$crimeT = $row['CrimeType'];
												$vid_ID = $row['VideoID'];
												
												$chg = "SELECT `Description` FROM `NewsStory` WHERE `TubeURL` LIKE '%$vid_ID%' LIMIT 0,1";
												$r_chg = mysqli_query($CONN, $chg);
												
												if(mysqli_num_rows($r_chg) <= 0){
												    $desc = cleanUpHTML(utf8_encode($row['Description']));
												}else{
												    $azzy = mysqli_fetch_array($r_chg);
												    $desc = cleanUpHTML(utf8_encode($azzy['Description']));
												}
												
												
												$videoURL = "https://www.youtube.com/embed/".$vid_ID;
												$divv = convertdiv($row['DistrictNumber']);
												
												
												$story = array("ID"=>$id,"Title"=>$title,"Description"=>$desc,"CaptionURL"=>$captionURL,
																"VideoDate"=>$videoDate,"CrimeType"=>$crimeT,"VideoURL"=>$videoURL,"Division"=>$divv);
												array_push($array,$story);
												
											}
											
												echo json_encode(array("Videos"=>$array,"TotalCount"=>$cat['ROWS'],"error"=>"false"));
										}
									
								}



//////////////////////////////////////////////////////////////////////////////// END of UCVIDEOS API ///////////////////////////////////////////////////////////////////





/////////////////////////////////////////////////////////////////////////////////////// Bookmark API FEED ///////////////////////////////////////////////////////////////////

						else if($_SERVER['REQUEST_METHOD'] == 'POST' && $data['Bookmark'] == "true"){
						//	else if($_SERVER['REQUEST_METHOD'] == 'GET' && $_GET['Bookmark'] == "true"){
								$device = $data['DeviceID'];
								//$device = '1106d78f7c334ac537b71b53e40bcc5f';
								$isNews = $data['Bookmark_NewsStory'];
								$isUCVids = $data['Bookmark_UCVideos'];
								$isBkSub = $data['Bookmark_Submit'];
								$isBkNews = $data['BookmarkNews'];
								$isVid = $data['BookmarkVideos'];
								$story_ID = $data['BookmarkID'];
								$rmBook = $data['BookmarkRemove'];
								$is_news_id = $data['News'];
								$is_vid_id = $data['Video'];
								$obj_news_id = $data['NewsID'];
								$obj_vid_id = $data['VideoID'];
								
								$news = array();
								$news_array = array();
								$videos = array();
								$videos_array = array();
								
								if(preg_match(R_MD5_MATCH, $device)){
										
									
									if($isNews == "true"){
												
										$sql = "SELECT `DeviceID` FROM `Bookmarks` WHERE `DeviceID` = '$device' AND `NewsStoryID` != 0";
										$isDev = mysqli_query($CONN, $sql) or die("Bad Query ".mysql_error());
									
										if(mysqli_num_rows($isDev) >=1){
												
											$getAll = "SELECT SQL_CALC_FOUND_ROWS * FROM `Bookmarks` WHERE `DeviceID` = '$device' AND `NewsStoryID` != 0";
											$cquery = "SELECT FOUND_ROWS() AS ROWS";
											$val = mysqli_query($CONN, $getAll);
											$cresult = mysqli_query($CONN, $cquery);
											$count = mysqli_fetch_array($cresult);
											
												while($rows = mysqli_fetch_array($val)){
													
													// if($rows['NewsStoryID'] == 0){
														// array_push($videos, $rows['UCVideoID']);
													// }
													
													 if($rows['UCVideoID'] == 0){
														array_push($news, $rows['NewsStoryID']);
													}
												
												}
												
												///CHCEK FOR EMPTY VALUES IN NEWS ARRAY
													if(empty($news)){
														
													}else{
															
														$s_vals = join(',', $news);
														$news_story = "SELECT * FROM `NewsStory` WHERE `ID` IN ($s_vals) LIMIT 0,5";
														$res = mysqli_query($CONN, $news_story);
														
															while($n_row = mysqli_fetch_array($res)){
																	
																$dist = $n_row['DistrictNumber'];
																$title = $n_row['Title'];
																$desc = cleanUpHTML(utf8_encode($n_row['Description']));
																$s_date = date('M j, g:i A',strtotime($n_row['PubDate']));
																$cat = $n_row['Category'];
																//$img = $n_row['ImageURL'];
																$news_id = $n_row['ID'];
																$vid_URL = $n_row['TubeURL'];
																$author = $n_row['StoryAuthor'];
																$divv = convertdiv($dist);
																
																$fetc = "SELECT `LocalImageURL` FROM `Images` WHERE `NewsID` = '$news_id'";
																$dex = mysqli_query($CONN, $fetc);
																$rowx = mysqli_fetch_array($dex);
																$iimg = $IMG_DIR.$rowx['LocalImageURL'];
																
																if(empty($vid_URL)){
																	$vid_URL = "No Video";
																}
																
																
																$n_obj = array("NewsID"=>$news_id,"District"=>$dist,"Title"=>$title,"Description"=>$desc,"Author"=>$author,
																				"PubDate"=>$s_date,"Category"=>$cat,"ImageURL"=>$iimg,"TubeURL"=>$vid_URL,"Division"=>$divv);				
															
																array_push($news_array,$n_obj);
																
															}
															
																				
													}
													
													// if(empty($videos)){
// 														
													// }else{
// 															
														// $v_vals = join(',', $videos);
														// $vid_story = "SELECT * FROM `UCVideos` WHERE `ID` IN ($v_vals) ";
														// $res_v = mysqli_query($CONN, $vid_story);
// 														
															// while($v_row = mysqli_fetch_array($res_v)){
																// $vid_title = $v_row['VideoTitle'];
																// $desc = $v_row['Description'];
																// $vid_ID = $v_row['VideoID'];
																// $imgURL = $v_row['VideoImageURL'];
																// $vid_Date = $v_row['VideoDate'];
																// $div = $v_row['PoliceDivision'];
																// $cat = $v_row['CrimeType'];
																// $vid_URL = "https://www.youtube.com/embed/".$vid_ID;
// 																
																// $v_obj = array("Division"=>$div,"Title"=>$vid_title,"Description"=>$desc,
																				// "PubDate"=>$vid_Date,"Category"=>$cat,"ImageURL"=>$imgURL,"TubeURL"=>$vid_URL);
// 																				
																// array_push($videos_array,$v_obj);
// 																
															// }
// 															
// 															
// 															
													// }
												
												
												
												echo json_encode(array("Bookmarks"=>array("NewsStory"=>$news_array),"TotalCount"=>$count['ROWS']),JSON_NUMERIC_CHECK);
													
												
										}else{
											echo json_encode(array("error"=>"INVALID DEVICE"));
										}	
										
									}
									
									else if($isUCVids == "true"){
										
										
										$sql = "SELECT `DeviceID` FROM `Bookmarks` WHERE `DeviceID` = '$device' AND `UCVideoID` != 0";
										$isDev = mysqli_query($CONN, $sql) or die("Bad Query ".mysql_error());
									
										if(mysqli_num_rows($isDev) >=1){
												
											$getAll = "SELECT SQL_CALC_FOUND_ROWS * FROM `Bookmarks` WHERE `DeviceID` = '$device' AND `UCVideoID` != 0";
											$cquery = "SELECT FOUND_ROWS() AS ROWS";
											$val = mysqli_query($CONN, $getAll);
											$cresult = mysqli_query($CONN, $cquery);
											$count = mysqli_fetch_array($cresult);
											
												while($rows = mysqli_fetch_array($val)){
													
													if($rows['NewsStoryID'] == 0){
														array_push($videos, $rows['UCVideoID']);
													}
													
													 // if($rows['UCVideoID'] == 0){
														// array_push($news, $rows['NewsStoryID']);
													// }
												
												}
												
												///CHCEK FOR EMPTY VALUES IN NEWS ARRAY
													// if(empty($news)){
// 														
													// }else{
														// $s_vals = join(',', $news);
														// $news_story = "SELECT * FROM `NewsStory` WHERE `ID` IN ($s_vals) ";
														// $res = mysqli_query($CONN, $news_story);
// 														
															// while($n_row = mysqli_fetch_array($res)){
																// $dist = $n_row['DistrictNumber'];
																// $title = $n_row['Title'];
																// $desc = $n_row['Description'];
																// $s_date = date('M j, g:i A',strtotime($n_row['PubDate']));
																// $cat = $n_row['Category'];
																// $img = $n_row['ImageURL'];
																// $vid_URL = $n_row['TubeURL'];
																// $author = $n_row['StoryAuthor'];
// 																
// 																
																// $n_obj = array("District"=>$dist,"Title"=>$title,"Description"=>$desc,"Author"=>$author,
																				// "PubDate"=>$s_date,"Category"=>$cat,"ImageURL"=>$img,"TubeURL"=>$vid_URL);				
// 															
																// array_push($news_array,$n_obj);
// 																
															// }
// 															
// 																				
													// }
													
													if(empty($videos)){
														
													}else{
															
														$v_vals = join(',', $videos);
														$vid_story = "SELECT * FROM `UCVideos` WHERE `ID` IN ($v_vals) LIMIT 0,5";
														$res_v = mysqli_query($CONN, $vid_story);
														
															while($v_row = mysqli_fetch_array($res_v)){

																$vid_title = $v_row['VideoTitle'];
																$desc = utf8_encode($v_row['Description']);
																$vid_ID = $v_row['ID'];
																$imgURL = $v_row['VideoImageURL'];
																$vid_Date = $v_row['VideoDate'];
																$div = $v_row['PoliceDivision'];
																$distNum = $v_row['DistrictNumber'];
																$cat = $v_row['CrimeType'];
																$vid_URL = "https://www.youtube.com/embed/".$vid_ID;
																$divv = convertdiv($v_row['DistrictNumber']);
																
    																if(strpos($vid_title," DC ") >=1){
    																    $txt = explode(" DC",$vid_title);
    																    $vid_title = $txt[0];
    																}
																
																$v_obj = array("VideoID"=>$vid_ID,"Division"=>$div,"Title"=>$vid_title,"Description"=>$desc,
																				"PubDate"=>$vid_Date,"Category"=>$cat,"ImageURL"=>$imgURL,"TubeURL"=>$vid_URL,"Division"=>$divv);
																				
																array_push($videos_array,$v_obj);
																
															}
															
															
															
													}
												
												
												echo json_encode(array("Bookmarks"=>array("UCVideos"=>$videos_array),"TotalCount"=>$count['ROWS']),JSON_NUMERIC_CHECK);
													
												
										}else{
											echo json_encode(array("error"=>"INVALID DEVICE"));
										}
										
										
										
									}else if ($isBkSub == "true"){
										
										$device = $data['DeviceID'];
								//$device = '1106d78f7c334ac537b71b53e40bcc5f';
								$isNews = $data['Bookmark_NewsStory'];
								$isUCVids = $data['Bookmark_UCVideos'];
								$isBkSub = $data['Bookmark_Submit'];
								$news = array();
								$news_array = array();
								$videos = array();
								$videos_array = array();
										
										
										$sql = "SELECT `DeviceID` FROM `Devices` WHERE `DeviceID` = '$device'";
										$res_q = mysqli_query($CONN, $sql) or die($MYSQL_ERROR. header('HTTP/1.1 403 Forbidden'));
											
											if(mysqli_num_rows($res_q) >=1){
												
												if($isBkNews == "true"){ // IF USER SENDS A NEWS STORY
														
													$sql1 = "SELECT `DeviceID` FROM `Bookmarks` WHERE `NewsStoryID` = '$story_ID' AND `DeviceID` = '$device'";
													$res_q1 = mysqli_query($CONN, $sql1); //LOOK FOR BOOKMARK BEFOR INSERT
														
														if(mysqli_num_rows($res_q1) >=1){
															// BOOKMARK ALREADY EXIST
															echo json_encode(array("error"=>"true","Insert Record"=>"false","msg"=>"Record Already Exist"));
														
														}else{
																
															$in_q = "INSERT INTO `Bookmarks` (`DeviceID`,`NewsStoryID`,`UCVideoID`)VALUES('$device','$story_ID','0')";
															$res_in = mysqli_query($CONN, $in_q);
															
																if($res_in){
																	echo json_encode(array("error"=>"false","Insert Record"=>"true","msg"=>"success"));
																}else{
																	echo json_encode(array("error"=>"true","Insert Record"=>"false","msg"=>mysql_error()));
																}
														}
														
														
												}else if($isVid == "true"){
													
													$sql = "SELECT `DeviceID` FROM `Bookmarks` WHERE `UCVideoID` = '$story_ID' AND `DeviceID` = '$device'";
													$res = mysqli_query($CONN, $sql);
														
														if(mysqli_num_rows($res) >=1){
														
															echo json_encode(array("error"=>"false","Insert Record"=>"true","msg"=>"success"));
														
														}else{
																	
															$in = "INSERT INTO `Bookmarks` (`DeviceID`,`NewsStoryID`,`UCVideoID`)VALUES('$device','0','$story_ID')";	
															$in_res = mysqli_query($CONN, $in);
															
																if($in_res){
																	echo json_encode(array("error"=>"false","Insert Record"=>"true","msg"=>"success"));
																}else{
																	echo json_encode(array("error"=>"true","Insert Record"=>"false","msg"=>mysql_error()));
																}
															
														}
													
												}
												
												
											}else{
												// DEVICE DONES NOT EXIST IN DEVICES TABLE NEED TO STOP THE PROGRAM  HERE !!!!!!
											}
										
									}
									
										else if($rmBook == "true"){
											
											if($is_news_id == "true" && $is_vid_id == "false"){
												
												$sql = "SELECT `ID`,`DeviceID`,`NewsStoryID` FROM `Bookmarks` WHERE `DeviceID` = '$device' AND `NewsStoryID` = '$obj_news_id'";
												$res = mysqli_query($CONN, $sql) or die($MYSQL_ERROR. header('HTTP/1.1 403 Forbidden'));
													
													if(mysqli_num_rows($res)>=1){
															
														$row = mysqli_fetch_array($res);
														$rID = $row['ID'];
														$del = "DELETE FROM `Bookmarks` WHERE `ID` = '$rID'";
														$res1 = mysqli_query($CONN, $del);
															
															if($res1){
																	
																echo json_encode(array("error"=>"false","msg"=>"Record Deleted"));
															
															}else{
																// DEL RECORD FAILED
																echo json_encode(array("error"=>"true","msg"=>"Record Deleted FAILED !!"));
															}
													
													}else{
														//NO REcord RETUNED 
														echo json_encode(array("error"=>"true","msg"=>"Record Does Not Exist"));
													}
												
												}else if($is_vid_id == "true" && $is_news_id == "false"){
														
													$sql = "SELECT `ID`,`DeviceID`,`UCVideoID` FROM `Bookmarks` WHERE `DeviceID` = '$device' AND `UCVideoID` = '$obj_vid_id'";
													$res = mysqli_query($CONN, $sql) or die($MYSQL_ERROR. header('HTTP/1.1 403 Forbidden'));
														
														if(mysqli_num_rows($res)>=1){
																
															$row = mysqli_fetch_array($res);
															$rID = $row['ID'];
															$del = "DELETE FROM `Bookmarks` WHERE `ID` = '$rID'";
															$res1 = mysqli_query($CONN, $del);
																
																if($res1){
																		
																	echo json_encode(array("error"=>"false","msg"=>"Record Deleted"));
																
																}else{
																	// DEL RECORD FAILED
																	echo json_encode(array("error"=>"true","msg"=>"Record Deleted FAILED !!"));
																}
														}else{
														
														echo json_encode(array("error"=>"true","msg"=>"Record Does Not Exist"));
							
													}
													
													
											}
											
											
										}
									
									
								}
						}

			
			
/////////////////////////////////////////////////////////////////////////////////////// END Bookmark API FEED ///////////////////////////////////////////////////////////////////			
			
			
			
			
			
			
			else if($_SERVER['REQUEST_METHOD'] == 'POST' && $data['ImageUpdate'] == "true"){
						    $devID  = $data['DeviceID'];
						    
						 //   if(preg_match(R_MD5_MATCH, $data['DeviceID']) && == "6D0CB6E184986C431DA3EB154B127E6B"){
						        
						        $data = $data['Images'];
						          foreach($data as $item){
						            $iURL = $item['RemoteImageURL'];
						            $chk = "SELECT `RemoteImageURL` FROM  `Images` WHERE `RemoteImageURL` = '$iURL'";
						            $res = mysqli_query($CONN, $chk);
						            
						              if(mysqli_num_rows($res)>=1){
						                  // RECORD EXIST
						              }else{
						                  $r_url = $item['RemoteImageURL'];
						                  $i_url = $item['LocalImageURL'];
						                  $in = "INSERT INTO `Images` (`LocalImageURL`,`RemoteImageURL`)VALUES('$i_url','$r_url')";
						                  $in_res = mysqli_query($CONN, $in);
						              }
						            
						          }
						        
						      //}
						}
			
			
			
			else{
			    header("HTTP/1.0 404 Not Found");
				exit();
			}
		
		
		
	


?>

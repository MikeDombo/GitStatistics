<?php
	$csvFile = "gitFile.csv";
	
	$options = getopt("f::d::Lh::v::");
	if(isset($options["h"])){
		echo "Usage: php ".$argv[0]." -f<output csv> -d<git repository directory> -v<verbose> -L<no line counts>";
		exit();
	}
	if(isset($options["f"])){
		$csvFile = $options["f"];
	}
	$gitPath = "";
	if(isset($options["d"])){
		$gitPath = " -C ".$options["d"];
	}
	else{
		$options["d"] = getcwd();
	}
	
	$handle = fopen($csvFile, "a");
	$list = array("Git commit hash", "Unix DateTime", "DateTime", "# of Files Changed", "Lines Changed", realpath($options["d"]));
	fputcsv($handle, $list);
	
	exec("git".$gitPath." log --all --pretty=format:\"%H %ct\" --reverse", $commits);
	$c = array();
	foreach($commits as $k=>$v){
		preg_match("/(^.{40}) (\d{10})/", $v, $output_array);
		array_push($c, ["hash"=>$output_array[1], "datetime"=>$output_array[2]]);
	}
	unset($commits);
	
	$commits = $c;
	foreach($commits as $k=>$v){
		if(isset($options["v"])){
			echo "Processing commit ".$v["hash"]."\r\n";
		}
		if($k==0){
			exec("git".$gitPath."  diff --name-only ".$v["hash"], $lines);
		}
		else{
			exec("git".$gitPath."  diff --name-only ".$commits[$k-1]["hash"]." ".$v["hash"], $lines);
		}
		$commits[$k]["files-changed"] = count($lines);
		unset($lines);
		
		if(!isset($options["L"])){
			if(isset($options["v"])){
				echo "Getting line change count of commit ".$v["hash"]."\r\n";
			}
			if($k==0){
				$lines = exec("git".$gitPath."  diff --shortstat ".$v["hash"]);
			}
			else{
				$lines = exec("git".$gitPath."  diff --shortstat ".$commits[$k-1]["hash"]." ".$v["hash"]);
			}
			$linecount = 0;
			preg_match("/(\d+) file[s]? changed(, (\d+) insertion[s]?\(\+\))?(, (\d+))?/", $lines, $output_array);
			if(!isset($output_array[3])){
				$linecount = 0;
			}
			else{
				$linecount = $output_array[3];
			}
			if(!(intval($linecount)>0)){
				$linecount = 0;
			}
			unset($lines);
			$commits[$k]["lines-changed"] = $linecount;
			if(isset($options["v"])){
				echo "Got line change count of commit ".$v["hash"]."\r\n";
			}
		}
		else{
			$commits[$k]["lines-changed"] = "";
		}
		if(isset($options["v"])){
			echo "Saving commit ".$v["hash"]."\r\n";
		}
		$list = array($commits[$k]["hash"], $commits[$k]["datetime"], (intval($commits[$k]["datetime"])/86400)+25569, $commits[$k]["files-changed"], $commits[$k]["lines-changed"]);
		fputcsv($handle, $list);
		
		if(isset($options["v"])){
			echo "Processed commit ".$v["hash"]."\r\n\r\n";
		}
	}
	
	fclose($handle);	
?>
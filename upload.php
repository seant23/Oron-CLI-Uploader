#!/usr/bin/php
<?php

include 'cli.php';

$u = new Uploader();
$u->init();

class Uploader {
	
	public $username;
	public $password;
	public $fileLocation;
	
	public $sessionDetails = array();
	
	public function init() {
		CLI::nl();
		CLI::colorEcho("Oron Uploader Version 1.0", true, CLI::YELLOW, CLI::BLACK, true);
		CLI::nl();
		
		$this->parseUssage();
		$this->login();
		$this->getSessionDetails();
		$this->upload();
	}
	
	public function login() {
		CLI::startMSG("Logging In:");
		
		$postdata = "login={$this->username}&password={$this->password}&op=login&redirect=%2F%3Fop%3Dmy_account&rand=";
				
		$ch = curl_init(); 
		curl_setopt ($ch, CURLOPT_URL, "http://oron.com/login");
		curl_setopt ($ch, CURLOPT_SSL_VERIFYPEER, FALSE); 
		curl_setopt ($ch, CURLOPT_USERAGENT, "Mozilla/5.0 (Windows; U; Windows NT 6.1; en-US) AppleWebKit/534.13 (KHTML, like Gecko) Chrome/9.0.597.107 Safari/534.13"); 
		curl_setopt ($ch, CURLOPT_TIMEOUT, 60); 
		curl_setopt ($ch, CURLOPT_FOLLOWLOCATION, 0); 
		curl_setopt ($ch, CURLOPT_RETURNTRANSFER, 1); 
		curl_setopt ($ch, CURLOPT_COOKIEJAR, "cookies/{$this->username}.txt"); 
		curl_setopt ($ch, CURLOPT_REFERER, "http://oron.com"); 
		curl_setopt ($ch, CURLOPT_POSTFIELDS, $postdata); 
		curl_setopt ($ch, CURLOPT_POST, 1); 
		$response = curl_exec ($ch); 

		if(strpos($response, 'Incorrect Login or Password')) {
			CLI::finishMSG("FAILED", CLI::RED);
			$this->error('Incorrect Login or Password');
		} else if(strpos($response, 'Enter correct captcha')) {
			CLI::finishMSG("FAILED", CLI::RED);
			$this->error('Captcha Required - Manual Login Required!');			
		} else {
			CLI::finishMSG("OK");
		}
	}
	
	public function getSessionDetails() {
		CLI::startMSG("Getting Session Info:");
		
		$ch = curl_init("http://oron.com/");
    	curl_setopt ($ch, CURLOPT_RETURNTRANSFER, 1); 
		curl_setopt ($ch, CURLOPT_COOKIEFILE, "cookies/{$this->username}.txt"); 		
		$response = curl_exec ($ch); 

		preg_match_all('<input type="hidden" name="(.*)" value="(.*)">',$response, $out, PREG_SET_ORDER);
		
		foreach($out as $detail) {
			$this->sessionDetails[$detail[1]] = $detail[2];
		}
		
		CLI::finishMSG("OK");
	}
	
	public function upload() {
		CLI::startMSG("Uploading:");
		
		//Remove Bad Vars
		unset($this->sessionDetails['utype']);
		unset($this->sessionDetails['mass_upload']);
		unset($this->sessionDetails['skip_step']);
		unset($this->sessionDetails['act_pass']);
		unset($this->sessionDetails['verification_code']);
		unset($this->sessionDetails['oron_pin']);
		unset($this->sessionDetails['progress_id']);
		unset($this->sessionDetails['x_progress_id']);
		
		//Add Good ones
		$this->sessionDetails['upload_type'] = 'file';
		$this->sessionDetails['file_0'] = "@{$this->fileLocation}";
		$this->sessionDetails['tos'] = '1';
		
		$this->sessionDetails['ut'] = 'file';
		$this->sessionDetails['link_rcpt'] = '';
		$this->sessionDetails['link_pass'] = '';
		$this->sessionDetails['submit_btn'] = ' Upload! ';

		
		$ch = curl_init( $this->sessionDetails['srv_tmp_url'] . "/upload/" . $this->sessionDetails['srv_id']);
		curl_setopt ($ch, CURLOPT_POSTFIELDS, $this->sessionDetails); 
    	curl_setopt ($ch, CURLOPT_RETURNTRANSFER, 1); 
		curl_setopt ($ch, CURLOPT_COOKIEFILE, "cookies/{$this->username}.txt"); 		
		$response = curl_exec ($ch); 

		CLI::finishMSG("OK");
	}
	
	
	
	public function error($message) {
		CLI::nl();
		CLI::colorEcho("An Error Has Occurred: ", true, CLI::WHITE, CLI::BLACK, true);
		CLI::colorEcho("\t$message ", true, CLI::RED, CLI::BLACK, true);
		CLI::nl();
		exit;
	}
	
	public function parseUssage() {
		global $argc, $argv;
		
		if($argc == 4) {
			$this->username = $argv[1];
			$this->password = $argv[2];
			$this->fileLocation = $argv[3];
		} else {
			$this->showUssage();
			exit;
		}
	}
		
	public function showUssage() {
		CLI::nl();
		CLI::colorEcho("Correct Ussage: ", true, CLI::WHITE, CLI::BLACK, true);
		CLI::colorEcho("\tupload.php <username> <password> <file> ", true, CLI::WHITE, CLI::BLACK, false);
		CLI::nl();
		
	}
}
<?php

Class SendWeeklyReportPlugin extends MantisPlugin {
	function register() {
		$this->name		= 'Send weekly report';
		$this->description 	= 'Send a weekly report by email to specified users.';
		$this->page		= 'config';

		$this->version		= '0.1.1';
		$this->requires		= array('MantisCore' => '1.2.14');
		
		$this->author		= 'eCola GmbH, Heiko Schneider-Lange';
		$this->contact		= 'hsl@ecola.com';
		$this->url		= 'http://www.lebensmittel.de';
	}

	function config() {
		return array();
	}
	
	function init() {
		
	}
}

<?php
/**
* Committees Class
*
* @package Membao
* @author Alan Kawamara
* @copyright 2017
*/
  
if (!defined("_VALID_PHP"))
	die('Direct access to this location is not allowed.');
  
class Committees
	{
    	const cTable = "committees";
      	const ctTable = "committees_type";
      	const cmTable = "committees_members";
      	const cmsTable = "committees_meetings";
	  
	  	public $committeeslug = null;
	  	private static $db;
      
	    /**
	    * Committees::__construct()
	    * 
	    * @return
	    */
    
    	public function __construct()
      		{
        		self::$db = Registry::get("Database");
		  		$this->getCommitteeSlug();

      		}

		/**
		* Committee::getCommitteeSlug()
		* 
		* @return
		*/
	
		private function getCommitteeSlug()
	  		{		  
		  		if (isset($_GET['committeename'])) {
				  	$this->committeeslug = sanitize($_GET['committeename'],100);
				  	return self::$db->escape($this->committeeslug);
			  	}
		  	}
	  
	  	/**
	   	* Committees::getCommittees()
	  	* 
	   	* @param bool $sort
	   	* @return
	   	*/
	  
	  	public function getCommittees($from = '')
	  		{
		  		if (isset($_GET['letter']) and (isset($_POST['fromdate']) && $_POST['fromdate'] <> "" || isset($from) && $from != '')) {
			  		$enddate = date("Y-m-d");
			  		$letter = sanitize($_GET['letter'], 2);
			  		$fromdate = (empty($from)) ? $_POST['fromdate'] : $from;
			  		
			  		if (isset($_POST['enddate']) && $_POST['enddate'] <> "") {
				  		$enddate = $_POST['enddate'];
			  		}
			  		
			  		$q = "SELECT COUNT(*) FROM " . self::cTable . " WHERE created BETWEEN '" . trim($fromdate) . "' AND '" . trim($enddate) . " 23:59:59'"
			  		. "\n AND name REGEXP '^" . self::$db->escape($letter) . "'";
			  		$where = " WHERE c.created BETWEEN '" . trim($fromdate) . "' AND '" . trim($enddate) . " 23:59:59' AND name REGEXP '^" . self::$db->escape($letter) . "'";
			  
			  		} elseif (isset($_POST['fromdate']) && $_POST['fromdate'] <> "" || isset($from) && $from != '') {
				  		$enddate = date("Y-m-d");
				  		$fromdate = (empty($from)) ? $_POST['fromdate'] : $from;
				  		
				  		if (isset($_POST['enddate']) && $_POST['enddate'] <> "") {
					  		$enddate = $_POST['enddate'];
				  		}
				  		
				  		$q = "SELECT COUNT(*) FROM " . self::cTable . " WHERE created BETWEEN '" . trim($fromdate) . "' AND '" . trim($enddate) . " 23:59:59'";
				  		$where = " WHERE c.created BETWEEN '" . trim($fromdate) . "' AND '" . trim($enddate) . " 23:59:59'";
				  
			  		} elseif(isset($_GET['letter'])) {
				  		$letter = sanitize($_GET['letter'], 2);
				  		$where = "WHERE c.name REGEXP '^" . self::$db->escape($letter) . "'";
				  		$q = "SELECT COUNT(*) FROM " . self::cTable . " WHERE name REGEXP '^" . self::$db->escape($letter) . "' LIMIT 1"; 

			  		} else {
				  		$q = "SELECT COUNT(*) FROM " . self::cTable . " LIMIT 1";
				  		$where = null;
			  		}
			  
		        $record = self::$db->query($q);
		        $total = self::$db->fetchrow($record);
		        $counter = $total[0];
				 
				$pager = Paginator::instance();
				$pager->items_total = $counter;
				$pager->default_ipp = Registry::get("Core")->perpage;
				$pager->paginate();
				  
				$sql = "SELECT c.*, ct.name as committees_name" 
				. "\n FROM " . self::cTable . " as c"
				. "\n LEFT JOIN " . self::ctTable . " as ct ON ct.id = c.committees_type"
				. "\n $where"
				. "\n ORDER BY c.created DESC" . $pager->limit;
		        
		         $row = self::$db->fetch_all($sql);
				  
		        return ($row) ? $row : 0;
	  		}

	  	/**
	   	* Committees:::processCommittee()
	   	* 
	   	* @return
	   	*/
	  	public function processCommittee()
	  		{		  
		  		Filter::checkPost('name', "Enter committee name");
		  		Filter::checkPost('description', "Enter committee description");
		  		Filter::checkPost('committees_type', "Select committee type");		  		  
		    		  
		  		if (empty(Filter::$msgs)) {
			  		$data = array(
				  	'name' => sanitize($_POST['name']),				  
				  	'slug' => doSeo($_POST['name']),
				  	'description' => $_POST['description'],
				  	'committees_type' => $_POST['committees_type']
			  		);	
			  
				  	if (!Filter::$id) {
						$data['created'] = "NOW()";
				  	}

				  	if (empty($_POST['metakeys' . Lang::$lang]) or empty($_POST['metadesc'])) {
						include (BASEPATH . 'lib/class_meta.php');
					  	parseMeta::instance($_POST['description']);
					  	
					  	if (empty($_POST['metakeys'])) {
							$data['metakeys'] = parseMeta::get_keywords();
					  	}
					  
					  	if (empty($_POST['metadesc'])) {
							$data['metadesc'] = parseMeta::metaText($_POST['description']);
					  	}
				  	}
			                              

				  	(Filter::$id) ? self::$db->update(self::cTable, $data, "id=" . Filter::$id) : $lastid = self::$db->insert(self::cTable, $data);
				  	$message = (Filter::$id) ? "Committee updated" : "Committee added";

				  	if (self::$db->affected()) {
						$json['type'] = 'success';
					  	$json['message'] = Filter::msgOk($message, false);
				  	} else {
						$json['type'] = 'success';
					  	$json['message'] = Filter::msgAlert(Lang::$word->NOPROCCESS, false);
				  	}
				  	
				  	print json_encode($json);			  	  			  
			  
			  	} else {
					$json['message'] = Filter::msgStatus();
				  	print json_encode($json);
			  	}
	  		}

	  	/**
	   	* Committees::renderCommittee()
	   	* 
	   	* @return
	   	*/
	  	
	  	public function renderCommittee()
	  		{
		  		$is_admin = Registry::get("Users")->is_Admin();
		  
		  		$sql = "SELECT c.*, c.id as cid, ct.name as committees_name" 		  		
		  		. "\n FROM " . self::cTable . " as c"	
		  		. "\n LEFT JOIN " . self::ctTable . " as ct ON ct.id = c.committees_type"	  
		  		. "\n WHERE c.slug = '".$this->committeeslug."'"
		  		. "\n $is_admin";
          		$row = self::$db->first($sql);		           
	  		}
	  	  	     	  
	  
	  	/**
	   	* Committees::getCommitteesList()
	   	* 
	   	* @return
	   	*/
	  	public function getCommitteesList()
	  		{
		  		$row = self::$db->fetch_all("SELECT id, name, slug FROM " . self::cTable . " ORDER BY created");
          		return $row ? $row : 0;
	  		}

		
		/**
		* Committees:::processCommitteeType()
		* 
		* @return
		*/
	
		public function processCommitteeType()
	  		{		  
				Filter::checkPost('name', "Enter committee name");			  		  		 
		    		  
		  		if (empty(Filter::$msgs)) {
			  		$data = array(
				  	'name' => sanitize($_POST['name']),
				  	'description' => $_POST['description']
			  		);			  			  	
			                              

			  		(Filter::$id) ? self::$db->update(self::ctTable, $data, "id=" . Filter::$id) : $lastid = self::$db->insert(self::ctTable, $data);
			  		$message = (Filter::$id) ? "Committee type updated" : "Committee type added";

			  		if (self::$db->affected()) {
				  		$json['type'] = 'success';
				  		$json['message'] = Filter::msgOk($message, false);
			  		} else {
				  		$json['type'] = 'success';
				  		$json['message'] = Filter::msgAlert(Lang::$word->NOPROCCESS, false);
			  		}
			  		
			  		print json_encode($json);			  	  			  			  
		  		} else {
			  		$json['message'] = Filter::msgStatus();
			  		print json_encode($json);
		  		}
	  		} 

	  	/**
	   	* Committees::getCommitteesList()
	   	* 
	   	* @return
	   	*/
	  	public function getCommitteesTypeList()
	  		{
		  		$row = self::$db->fetch_all("SELECT id, name FROM " . self::ctTable . " ORDER BY name");
          		return $row ? $row : 0;
	  		}	

		/**
		* Committees:::processCommitteeMembers()
		* 
		* @return
		*/
	
		public function processCommitteeMembers()
	  		{		  
				Filter::checkPost('chair', "Please select committee chair name");
				Filter::checkPost('deputy-chair', "Please select committee deputy chair name");
				Filter::checkPost('member3', "Please select committee member name");
				Filter::checkPost('member4', "Please select committee member name");			  		  		 
				Filter::checkPost('member5', "Please select committee member name");
				Filter::checkPost('member6', "Please select committee member name");
				Filter::checkPost('member7', "Please select committee member name");
				Filter::checkPost('member8', "Please select committee member name");
				Filter::checkPost('member9', "Please select committee member name");
				Filter::checkPost('member10', "Please select committee member name");
				Filter::checkPost('member11', "Please select committee member name");
				Filter::checkPost('member12', "Please select committee member name");
				Filter::checkPost('member13', "Please select committee member name");
				Filter::checkPost('member14', "Please select committee member name");
				Filter::checkPost('member15', "Please select committee member name");
				Filter::checkPost('member16', "Please select committee member name");
		    		  
		  		if (empty(Filter::$msgs)) {
		  			$members = array($_POST['chair'], $_POST['deputy-chair'], $_POST['member3'], $_POST['member4'], $_POST['member5'], $_POST['member6'], $_POST['member7'], $_POST['member8'], $_POST['member9'], $_POST['member10'], $_POST['member11'], $_POST['member12'], $_POST['member13'], $_POST['member14'], $_POST['member15'], $_POST['member16']);

		  			$i = 0;

		  			//delete existing members list for this committee
		  			self::$db->delete(self::cmTable, "committee=" . Filter::$id);

		  			foreach ($members as $val):
			  			$memberid = intval($val);			  		

			  			$data = array(
				  			'committee' => Filter::$id,
				  			'member' => $memberid
			  			);

			  			//echo $data['member'];

			  			if ($i==0) {
			  				$data['role'] = 1;
			  			}

			  			elseif ($i==1) {
			  				$data['role'] = 2;
			  			}			  		
			  			else {
			  				$data['role'] = 3;
			  			}	  	
			                   
						//insert record in car-campaign table
						self::$db->insert(self::cmTable, $data);

						$i++;

					endforeach;

					if (self::$db->affected()) {
						$json['type'] = 'success';
					  	$json['title'] = Lang::$word->SUCCESS;
					  	$json['message'] = Filter::msgSingleOk("Members of this committee have been updated", false);
			  		
			  		} else {
				  		$json['type'] = 'success';
				  		$json['message'] = Filter::msgAlert(Lang::$word->NOPROCCESS, false);
			  		}
			  		
			  		print json_encode($json);

		  		} else {
			  		$json['message'] = Filter::msgStatus();
			  		print json_encode($json);
		  		}
	  		} 	

	  	/**
	   	* Committees::getCommitteeMembers()
	  	* 
	   	* @param bool $sort
	   	* @return
	   	*/
	  
	  	public function getCommitteeMembers($committee)
	  		{		  		
				  
				$sql = "SELECT cm.*, CONCAT(l.first_name,' ',l.last_name) as name, l.constituency as constituencyid" 
				. "\n FROM " . self::cmTable . " as cm"
				. "\n LEFT JOIN " . Leaders::lTable . " as l ON l.id = cm.member"
				. "\n WHERE cm.committee = " . $committee
				. "\n ORDER BY cm.role ASC";
		        
		         $row = self::$db->fetch_all($sql);
				  
		        return ($row) ? $row : 0;
	  		}	

	  			/**
		* Committee::getCommitteeMeetingsSlug()
		* 
		* @return
		*/
	
		private function getCommitteeMeetingsSlug()
	  		{		  
		  		if (isset($_GET['committeemeetingname'])) {
				  	$this->committeemeetingslug = sanitize($_GET['committeemeetingname'],100);
				  	return self::$db->escape($this->committeemeetingslug);
			  	}
		  	}
	  
	  	/**
	   	* Committees::getCommitteeMeetings()
	  	* 
	   	* @param bool $sort
	   	* @return
	   	*/
	  
	  	public function getCommitteeMeetings($from = '')
	  		{
		  		if (isset($_GET['letter']) and (isset($_POST['fromdate']) && $_POST['fromdate'] <> "" || isset($from) && $from != '')) {
			  		$enddate = date("Y-m-d");
			  		$letter = sanitize($_GET['letter'], 2);
			  		$fromdate = (empty($from)) ? $_POST['fromdate'] : $from;
			  		
			  		if (isset($_POST['enddate']) && $_POST['enddate'] <> "") {
				  		$enddate = $_POST['enddate'];
			  		}
			  		
			  		$q = "SELECT COUNT(*) FROM " . self::cmsTable . " WHERE date BETWEEN '" . trim($fromdate) . "' AND '" . trim($enddate) . " 23:59:59'"
			  		. "\n AND name REGEXP '^" . self::$db->escape($letter) . "'";
			  		$where = " WHERE cms.date BETWEEN '" . trim($fromdate) . "' AND '" . trim($enddate) . " 23:59:59' AND name REGEXP '^" . self::$db->escape($letter) . "'";
			  
			  		} elseif (isset($_POST['fromdate']) && $_POST['fromdate'] <> "" || isset($from) && $from != '') {
				  		$enddate = date("Y-m-d");
				  		$fromdate = (empty($from)) ? $_POST['fromdate'] : $from;
				  		
				  		if (isset($_POST['enddate']) && $_POST['enddate'] <> "") {
					  		$enddate = $_POST['enddate'];
				  		}
				  		
				  		$q = "SELECT COUNT(*) FROM " . self::cmsTable . " WHERE date BETWEEN '" . trim($fromdate) . "' AND '" . trim($enddate) . " 23:59:59'";
				  		$where = " WHERE cms.date BETWEEN '" . trim($fromdate) . "' AND '" . trim($enddate) . " 23:59:59'";
				  
			  		} elseif(isset($_GET['letter'])) {
				  		$letter = sanitize($_GET['letter'], 2);
				  		$where = "WHERE cms.name REGEXP '^" . self::$db->escape($letter) . "'";
				  		$q = "SELECT COUNT(*) FROM " . self::cmsTable . " WHERE name REGEXP '^" . self::$db->escape($letter) . "' LIMIT 1"; 

					} elseif(isset(Filter::$id)) {
				  		$where = "WHERE cms.committee = " . Filter::$id ."";
				  		$q = "SELECT COUNT(*) FROM " . self::cmsTable . " WHERE committee = " . Filter::$id . ""; 

			  		
			  		} else {
				  		$q = "SELECT COUNT(*) FROM " . self::cmsTable . " LIMIT 1";
				  		$where = null;
			  		}
			  
		        $record = self::$db->query($q);
		        $total = self::$db->fetchrow($record);
		        $counter = $total[0];
				 
				$pager = Paginator::instance();
				$pager->items_total = $counter;
				$pager->default_ipp = Registry::get("Core")->perpage;
				$pager->paginate();
				  
				$sql = "SELECT cms.*, c.name as committees_name" 
				. "\n FROM " . self::cmsTable . " as cms"
				. "\n LEFT JOIN " . self::cTable . " as c ON c.id = cms.committee"
				. "\n $where"
				. "\n ORDER BY cms.created DESC" . $pager->limit;
		        
		         $row = self::$db->fetch_all($sql);
				  
		        return ($row) ? $row : 0;
	  		}

	  	/**
	   	* Committees:::processCommitteeMeeting()
	   	* 
	   	* @return
	   	*/
	  	public function processCommitteeMeeting()
	  		{		  
		  		Filter::checkPost('name', "Enter meeting title");
		  		Filter::checkPost('description', "Enter committee description");
		  		Filter::checkPost('meeting_date', "Select meeting date");		
		    		  
		  		if (empty(Filter::$msgs)) {

		  			$meeting_date = $_POST['meeting_date'];
					$fdate = DateTime::createFromFormat('d F, Y', $meeting_date);
					$fdate = $fdate->format("Y-m-d");

			  		$data = array(
				  	'name' => sanitize($_POST['name']),				  
				  	'slug' => doSeo($_POST['name']),
				  	'description' => $_POST['description'],
				  	'meeting_date' => $fdate,
				  	'meeting_type' => $_POST['meeting_type'],
				  	'committee' => $_POST['committee']
			  		);	
			  
				  	if (!Filter::$id) {
						$data['created'] = "NOW()";
				  	}

				  	if (empty($_POST['metakeys' . Lang::$lang]) or empty($_POST['metadesc'])) {
						include (BASEPATH . 'lib/class_meta.php');
					  	parseMeta::instance($_POST['description']);
					  	
					  	if (empty($_POST['metakeys'])) {
							$data['metakeys'] = parseMeta::get_keywords();
					  	}
					  
					  	if (empty($_POST['metadesc'])) {
							$data['metadesc'] = parseMeta::metaText($_POST['description']);
					  	}
				  	}
			                              

				  	(Filter::$id) ? self::$db->update(self::cmsTable, $data, "id=" . Filter::$id) : $lastid = self::$db->insert(self::cmsTable, $data);
				  	$message = (Filter::$id) ? "Committee meeting updated" : "Committee meeting added";

				  	if (self::$db->affected()) {
						$json['type'] = 'success';
					  	$json['message'] = Filter::msgOk($message, false);
				  	} else {
						$json['type'] = 'success';
					  	$json['message'] = Filter::msgAlert(Lang::$word->NOPROCCESS, false);
				  	}
				  	
				  	print json_encode($json);			  	  			  
			  
			  	} else {
					$json['message'] = Filter::msgStatus();
				  	print json_encode($json);
			  	}
	  		}

	  	/**
	   	* Committees::renderCommitteeMeeting()
	   	* 
	   	* @return
	   	*/
	  	
	  	public function renderCommitteeMeeting()
	  		{
		  		$is_admin = Registry::get("Users")->is_Admin();
		  
		  		$sql = "SELECT cms.*, cms.id as cmsid, c.name as committees_name" 		  		
		  		. "\n FROM " . self::cmsTable . " as cms"	
		  		. "\n LEFT JOIN " . self::cTable . " as c ON c.id = cms.committee"	  
		  		. "\n WHERE c.slug = '".$this->committeeslug."'"
		  		. "\n $is_admin";
          		$row = self::$db->first($sql);		           
	  		}
	  	  	     	  
	  
	  	/**
	   	* Committees::getCommitteesList()
	   	* 
	   	* @return
	   	*/
	  	public function getCommitteeMeetingsList()
	  		{
		  		$row = self::$db->fetch_all("SELECT id, name, slug FROM " . self::cmsTable . " ORDER BY date");
          		return $row ? $row : 0;
	  		}	  		
  	}
?>
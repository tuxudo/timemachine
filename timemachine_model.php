<?php
class Timemachine_model extends Model {
	
	function __construct($serial='')
	{
		parent::__construct('id', 'timemachine'); //primary key, tablename
		$this->rs['id'] = '';
		$this->rs['serial_number'] = $serial; $this->rt['serial_number'] = 'VARCHAR(255) UNIQUE';
		$this->rs['last_success'] = ''; // Datetime of last successfull backup
		$this->rs['last_failure'] = ''; // Datetime of last failure
		$this->rs['last_failure_msg'] = ''; // Message of the last failure
		$this->rs['duration'] = 0; // Duration in seconds
		$this->rs['timestamp'] = ''; // Timestamp of last update
		
		// Schema version, increment when creating a db migration
		$this->schema_version = 0;
		
		//indexes to optimize queries
		$this->idx[] = array('last_success');
		$this->idx[] = array('last_failure');
		$this->idx[] = array('timestamp');
		
		// Create table if it does not exist
		$this->create_table();
		
		if ($serial)
			$this->retrieve_one('serial_number=?', $serial);
		
		$this->serial = $serial;
		  
	}

	// ------------------------------------------------------------------------
	/**
	 * Process data sent by postflight
	 *
	 * @param string data
	 * 
	 **/
	function process($data)
	{		
		
		// Parse log data
		$start = ''; // Start date
        foreach(explode("\n", $data) as $line)
        {
        	$date = substr($line, 0, 19);
        	$message = substr($line, 21);
        	

        	if( strpos($message, 'Starting automatic backup') === 0)
        	{
        		$start = $date;
        	}
        	elseif( preg_match('/^Backup completed successfully/', $message))
        	{
        		if($start)
        		{
        			$this->duration = strtotime($date) - strtotime($start);
        		}
        		else
        		{
        			$this->duration = 0;
        		}
        		$this->last_success = $date;
        	}
        	elseif( preg_match('/^Backup failed/', $message))
        	{
        		$this->last_failure = $date;
        		$this->last_failure_msg = $message;
        	}
        }
        
        // Only store if there is data
        if($this->last_success OR $this->last_failure )
        {
			$this->timestamp = time();
        	$this->save();
        }
		
	}


}

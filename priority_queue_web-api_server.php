<?php

class Jobs extends SplPriorityQueue
{
	private $jobId;
	private $submitterId;
	private $processorId;
	private $status;
	private $processingTime;
	
	public function __construct()
	{
		$memcache  = new Memcache; // Create an Obj from MEMCACHE to optimize access to the queue data. 
		$memcache->addServer($hostname, 11211);
	}
	
	public function setJobID($jobID = Null)
	{
		$this->jobId = $jobID;
	}
	
	public function addJob($jobAttribute,$priority)
 	{
		$this->insert($jobAttribute,$priority);
		$jobID = uniqid(); // JobID might come from Database if we are dealing with database system.
		$this->setJobID($jobID);
		return $this->jobId;
	}
	
	public function processJob()
	{
		
		$start = microtime(true); //to get current processing time
		
		$this->top(); //Go to TOP of the Queue.

		while($this->valid()){ //Check whether the queue contains more jobs
			$jobRow = $this->current(); //Get the current non-completed job with the highest priority from the Queue.
			$jobID = $memcache->get("JOBID_$jobRow['jobid']"); Get JobID from MEMCACHE, So we make sure No two job processors should pick the same job
			if($jobID != '') //It means other processors Pick up this Job
			{
				$this->next(); //Go to the next job in the Queue.
			}
			else
			{
				$memcache->set('JOBID', $jobRow['jobid'],0,0); //SET Job(key, value) in MEMCACHE, let Other Process know that this Job already Picked up.
				//Some logic to save task into a file Or Database......
				$this->setProcessorIdID($jobRow); //Job being processed.
				$this->status = "Completed";
				$this->next(); //Go to the next job in the Queue.
			}
		}
		
		$time_elapsed_secs = microtime(true) - $start;
		$this->processingTime = $time_elapsed_secs;
	}
	
	public function getCurrentPocessingTime()
	{
		return $this->processingTime;
	}
	
	public function getSubmitterID($submitter)
	{
		//some logic to find submitterID......
		$this->submitterId = $submitterID;
		return $this->submitterID;
	}
	
	public function setProcessorIdID($jobRow = array())
	{
		//Some Logic to find which processor pick up the job ($processorId)
		$this->processorId = $processorId;
	}
	
	public getJobStatus($jobID = Null)
	{
		//Some logic that find job record........
		return $this->status;
	}
	
	public function compare($priority1, $priority2) //This Method to respect Queue Highest Priority 
 	{
    		if ($priority1 === $priority2) return 0;
      		return $priority1 < $priority2 ? -1 : 1;
   	}
}

/**
**Main Script
**/

$job = new Jobs();
$job->setExtractFlags(Jobs::EXTR_BOTH); //// Mode of extraction (Display Both Data & Priority)

$submitter = ''; //Some Sumbitter Input
$priority = array(); // list of all priority values

$input = new Input($_REQUEST); // Assuming we have class Input that does POST/GET...Cleaning when there End Point request 
if($input->post("task")) //Add new task to the queue.
{
	$jobAttribute = array();
	$jobAttribute['submitterId'] = $job->getSubmitterID($submitter); 
	$jobID = $job->addJob($jobAttribute,$priority);
}

elseif($input->get("task")) 
{
	if($input->get("id")) //Get status of the task with id = $id
	{
		$job->getJobStatus($input->get("id"));
	}
	else 
	{
		$job->processJob();
	}
}

$job->getCurrentPocessingTime();


?> 


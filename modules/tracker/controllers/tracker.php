<?php defined('SYSPATH') OR die('No direct access allowed.');

class Tracker_Controller extends Controller {
	public function __construct() {
		parent::__construct();
		if(request::is_ajax()) $this->_use_json_errors();
	}
	public function index() {
		if(request::is_ajax()) $this->_index_json();
		$t = new View('tracker/list');
		$t->set('projects',ORM::factory('project')->find_all());
		$t->render(TRUE);
	}
	protected function _index_json() {
		$projects = array();
		foreach(ORM::factory('project')->find_all() as $project)
			$projects[] = $project->as_array();
		die(json_encode($projects));
	}
	public function json() {
		$tasks = array();
		foreach(ORM::factory('task')->find_all() as $task)
			$tasks[] = array(
				'id'=>$task->id,
				'short'=>$task->title,
				'long'=>$task->notes,
				'url'=>$task->generate_url(),
			);
		die(json_encode($tasks));
	}
	public function project($id) {
		if(!is_numeric($id)) throw new Exception('INVALID_ID');
		$project = ORM::factory('project',$id);
		if(request::is_ajax()) $this->_project_json($project);
		
		$t = new View('tracker/list');
		$t->set('projects',array($project));
		$t->render(TRUE);
	}
	protected function _project_json($project) {
		$response = $project->as_array();
		$response['tasks'] = array();
		foreach($project->tasks as $task)
			$response['tasks'][] = array(
				'id'=>$task->id,
				'thl_id'=>$task->thl_id,
				'title'=>$task->title,
			);
		
		
		die(json_encode($response));
	}
	public function task($id) {
		if(!is_numeric($id)) throw new Exception('INVALID_ID');
		$task = ORM::factory('task',$id);
		if(request::is_ajax()) die(json_encode($task->as_array()));
		
		$t = new View('tracker/view');
		$t->set('task',$task);
		$t->render(TRUE);
	}
	public function report() {
		if(isset($_POST['username']) && isset($_POST['password']))
			Auth::instance()->login($_POST['username'],$_POST['password']);
		if(!Auth::instance()->logged_in('login'))
			throw new Exception('Bug Report requires login');
			
		$short	= strip_tags($this->input->post('short'));
		$long	= strip_tags($this->input->post('long'));
		$type	= strtolower(strip_tags($this->input->post('type')));
		
		if($short=='') throw new Exception('EMPTY BUG');
		if(!in_array($type,array('request','bug'))) throw new Exception('INVALID BUG TYPE');
		
		$task = ORM::factory('task');
		$task->title = $short;
		$task->notes = $long;
		$task->type = $type;
		$task->project_id = 1;
		$task->reporter_id = Auth::instance()->get_user()->id;
		$task->created_date = time();
		$task->modified_date = time();
		$task->save();
		
		if(request::is_ajax()) die(json_encode(array(
			'result'=>'OK',
			'id'=>$task->id,
			'url'=>$task->generate_url(),
		)));
	}
}
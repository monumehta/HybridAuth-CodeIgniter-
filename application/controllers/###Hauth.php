<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class HAuth extends CI_Controller {

	function __construct(){
		
		parent::__construct();
		$this->load->library('HybridAuthLib');
		// $this->load->model('front/user_model', 'user');
		// $this->user_id = $this->session->userdata('user_id');
		
	}

	public function login($provider)
	{   
		log_message('debug', "controllers.HAuth.login($provider) called");

		try
		{
			log_message('debug', 'controllers.HAuth.login: loading HybridAuthLib');
			$this->load->library('HybridAuthLib');
			
			if ($this->hybridauthlib->providerEnabled($provider))
			{ 

				log_message('debug', "controllers.HAuth.login: service $provider enabled, trying to authenticate.");
				$service = $this->hybridauthlib->authenticate($provider);
				
				if ($service->isUserConnected())
				{   
					log_message('debug', 'controller.HAuth.login: user authenticated.');

					$user_profile = $service->getUserProfile();
					
					log_message('info', 'controllers.HAuth.login: user profile:'.PHP_EOL.print_r($user_profile, TRUE));

					$data['user_profile'] = $user_profile;

					$loginid = $this->user->checkSocialid($data['user_profile']->identifier);
					if($loginid)
					{
						$this->session->set_userdata('user_id',$loginid);
						redirect('user/my-account');
					}
					else if(!empty($data['user_profile']->email)){
						$alreadyUser = $this->user->getUserByEmailAddress($user_profile->email);
						if($alreadyUser)
						{
							if($alreadyUser->status != "Active")
							{
								die('Sorry your account has been deactivated');
							}
							else
							{
								$input['is_email_verified'] = 'Yes';
								$this->user->updateUserByUserId($alreadyUser->id,$input);
								$this->session->set_userdata(array('user_id' => $alreadyUser->id));
				                $message = 'Login Successfully.';
			                	$this->session->set_flashdata('success_message', $message);
            					redirect('user/my-account');
							}
						}
						else
						{
							$input['first_name'] = $user_profile->displayName;
		                    $input['email'] =  $user_profile->email;
		                    $input['status'] = 'Active';
		                    $input['is_email_verified'] = 'Yes';
		                    $input['add_date'] = $this->common_model->getDefaultToGMTDate(time());
		                    if($user_id = $this->user->addUser($input))
		                    {
		                    	$socialdetail=array(
									'user_id'=> $user_id,
									'account_type'=> $provider, 
									'social_id'=> $data['user_profile']->identifier,
									'add_date_gmt'=> $this->common_model->getDefaultToGMTDate(time()),
									'status'=>'Active'
								);
								$this->user->addDetail($socialdetail);						
		                    	$this->session->set_userdata(array('user_id' => $user_id));
				                $message = 'Login Successfully.';
			                	$this->session->set_flashdata('success_message', $message);
            					redirect('user/my-account');
		                    }
						}

					}else{
						if(!empty($this->session->userdata('user_id')))
						{
							$this->WhenSessionSet($data,$provider);
						}
						else{

							$this->session->set_userdata(array('identifier' => $data['user_profile']->identifier, 'firstName' => $data['user_profile']->firstName, 'provider' => $provider));
							$this->session->set_flashdata('error_message','No Account is linked with your twitter account, please register or login first and link your Twitter account from account settings');
							redirect('registration');
						}

					}
					
				}
				else // Cannot authenticate user
				{
					show_error('Cannot authenticate user');
				}
			}
			else // This service is not enabled.
			{
				log_message('error', 'controllers.HAuth.login: This provider is not enabled ('.$provider.')');
				show_404($_SERVER['REQUEST_URI']);
			}
		}
		catch(Exception $e)
		{
			$error = 'Unexpected error';
			switch($e->getCode())
			{
				case 0 : $error = 'Unspecified error.'; break;
				case 1 : $error = 'Hybriauth configuration error.'; break;
				case 2 : $error = 'Provider not properly configured.'; break;
				case 3 : $error = 'Unknown or disabled provider.'; break;
				case 4 : $error = 'Missing provider application credentials.'; break;
				case 5 : log_message('debug', 'controllers.HAuth.login: Authentification failed. The user has canceled the authentication or the provider refused the connection.');
				         //redirect();
				         if (isset($service))
				         {
				         	log_message('debug', 'controllers.HAuth.login: logging out from service.');
				         	$service->logout();
				         }
				         show_error('User has cancelled the authentication or the provider refused the connection.');
				         break;
				case 6 : $error = 'User profile request failed. Most likely the user is not connected to the provider and he should to authenticate again.';
				         break;
				case 7 : $error = 'User not connected to the provider.';
				         break;
			}

			if (isset($service))
			{
				$service->logout();
			}

			log_message('error', 'controllers.HAuth.login: '.$error);
			show_error('Error authenticating user.');
		}
	}

	/*function logout($provider)
	{
		$this->load->library('HybridAuthLib');
		$service = $this->hybridauthlib->authenticate($provider);
		if (isset($service))
		{
				$service->logout();
		}
	}*/

	public function endpoint()
	{
		
		log_message('debug', 'controllers.HAuth.endpoint called.');
		log_message('info', 'controllers.HAuth.endpoint: $_REQUEST: '.print_r($_REQUEST, TRUE));

		if ($_SERVER['REQUEST_METHOD'] === 'GET')
		{
			log_message('debug', 'controllers.HAuth.endpoint: the request method is GET, copying REQUEST array into GET array.');
			$_GET = $_REQUEST;
		}

		log_message('debug', 'controllers.HAuth.endpoint: loading the original HybridAuth endpoint script.');
		require_once APPPATH.'/third_party/hybridauth/index.php';

	}

	public function WhenSessionSet($data,$provider)
	{ 	
						
		$loginid = $this->user->checkSocialid($data['user_profile']->identifier);		
		if($loginid)
		{ 	
			redirect('registration');			
		}else{
		
			$socialdetail=array(
							'user_id'=>$this->session->userdata('USER_ID'),
							'account_type'=> $provider, 
							'social_id'=> $data['user_profile']->identifier,
							'add_date_gmt'=> $this->common_model->getDefaultToGMTDate(time()),
							'status'=>'Active'
					);
					if($this->user->addDetail($socialdetail))
					{
						redirect('registration');
					}
		}

	}
	public function done(){
		echo 'done';die;
	}

}

/* End of file hauth.php */
/* Location: ./application/controllers/hauth.php */

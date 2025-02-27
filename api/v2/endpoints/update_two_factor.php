<?php

$response_data   = array(
    'api_status' => 400
);

if (!empty($_POST['type']) && $_POST['type'] == 'verify') {
	
	if (empty($_POST['code'])) {
        $error_code    = 7;
        $error_message = 'code can not be empty';
    }
    else{
        $confirm_code = $db->where('user_id', $br['user']['user_id'])->where('email_code', md5($_POST['code']))->getValue(T_USERS, 'count(*)');
        $Update_data = array();
        if (empty($confirm_code)) {
        	$error_code    = 8;
	        $error_message = 'wrong confirmation code';
        }
        if (empty($error_code)) {
            if ($br['config']['two_factor_type'] == 'phone') {
                $Update_data['phone_number'] = $br['user']['new_phone'];
                $Update_data['new_phone'] = '';
            }
            if ($br['config']['two_factor_type'] == 'email') {
                $Update_data['email'] = $br['user']['new_email'];
                $Update_data['new_email'] = '';
            }
            if ($br['config']['two_factor_type'] == 'both') {
                if (!empty($br['user']['new_email'])) {
                    $Update_data['email'] = $br['user']['new_email'];
                    $Update_data['new_email'] = '';
                }
                if (!empty($br['user']['new_phone'])) {
                    $Update_data['phone_number'] = $br['user']['new_phone'];
                    $Update_data['new_phone'] = '';
                }
            }
            $Update_data['two_factor_verified'] = 1;
            $Update_data['two_factor'] = 1;
            Br_UpdateUserData($br['user']['user_id'], $Update_data);

            $response_data['api_status'] = 200;
			$response_data['message'] = 'two factor on';
        }
    }
}
else{
	if ($br['user']['two_factor']) {
		$Update_data = array(
	        'two_factor' => 0,
	        'two_factor_verified' => 0
	    );
	    Br_UpdateUserData($br['user']['user_id'], $Update_data);
	    $response_data['api_status'] = 200;
		$response_data['message'] = 'two factor off';
	}
	else{
		if ($br['config']['two_factor_type'] == 'phone') {
			if (!empty($_POST['phone_number'])) {
				preg_match_all('/\+(9[976]\d|8[987530]\d|6[987]\d|5[90]\d|42\d|3[875]\d|
	                        2[98654321]\d|9[8543210]|8[6421]|6[6543210]|5[87654321]|
	                        4[987654310]|3[9643210]|2[70]|7|1)\d{1,14}$/', $_POST['phone_number'], $matches);
		        if (!empty($matches[1][0]) && !empty($matches[0][0])) {
		        	$code = rand(111111, 999999);
			        $hash_code = md5($code);
			        $message = "Your confirmation code is: $code";
			        $send = Br_SendSMSMessage($_POST['phone_number'], $message);
		            if ($send) {
		                $Update_data = array(
		                    'phone_number' => Br_Secure($_POST['phone_number']),
		                    'email_code' => $hash_code,
		                    'two_factor' => 0,
			                'two_factor_verified' => 0
		                );
		                Br_UpdateUserData($br['user']['user_id'], $Update_data);
		                $response_data['api_status'] = 200;
						$response_data['message'] = 'confirmation code sent';
		            }
		            else{
		            	$error_code    = 6;
				        $error_message = 'can not send sms';
		            }
		        }
		        else{
		        	$error_code    = 5;
			        $error_message = 'phone_number is wrong';
		        }
			}
			else{
				$error_code    = 4;
		        $error_message = 'phone_number can not be empty';
			}
		}
		else{
			$code = rand(111111, 999999);
	        $hash_code = md5($code);
	        $message = "Your confirmation code is: $code";
	        $send_message_data       = array(
	            'from_email' => $br['config']['siteEmail'],
	            'from_name' => $br['config']['siteName'],
	            'to_email' => $br['user']['email'],
	            'to_name' => $br['user']['name'],
	            'subject' => 'Please verify that it’s you',
	            'charSet' => 'utf-8',
	            'message_body' => $message,
	            'is_html' => true
	        );
	        $send = Br_SendMessage($send_message_data);
	        if ($send) {
	            $Update_data = array(
	                'email_code' => $hash_code,
	                'two_factor' => 0,
	                'two_factor_verified' => 0
	            );
	            Br_UpdateUserData($br['user']['user_id'], $Update_data);
	            $response_data['api_status'] = 200;
				$response_data['message'] = 'confirmation code sent';
	        }
	        else{
	        	$error_code    = 4;
		        $error_message = 'phone_number can not be empty';
	        }
		}
	}
}
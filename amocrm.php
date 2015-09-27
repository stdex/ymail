<?php

require_once "settings.php";

function send_to_amocrm($name = "", $phone = "", $email = "", $msg = "", $fertilizer = "", $volume_fertilizer = "", $address = "", $datetime = "", $utm_source = "", $utm_medium = "", $utm_campaign = "", $utm_content = "", $utm_term = "", $ga_utm = "") {
    global $amocrm_subdomain, $amocrm_login, $amocrm_api_hash;
    $errors=array(//Массив ошибок
      301=>'Moved permanently',
      400=>'Bad request',
      401=>'Unauthorized',
      403=>'Forbidden',
      404=>'Not found',
      500=>'Internal server error',
      502=>'Bad gateway',
      503=>'Service unavailable'
    );
    $flogs=fopen("logs.txt","a");//Файл логов
    $user=array(
        'USER_LOGIN' => $amocrm_login,
        'USER_HASH' => $amocrm_api_hash
    );

    #Формируем ссылки для запросов
    $subdomain = $amocrm_subdomain;
    $auth_link = 'https://'.$subdomain.'.amocrm.ru/private/api/auth.php?type=json';
    $account_link = 'https://'.$subdomain.'.amocrm.ru/private/api/v2/json/accounts/current';
    $contacts_link = 'https://'.$subdomain.'.amocrm.ru/private/api/v2/json/contacts/set';
    $leads_link = 'https://'.$subdomain.'.amocrm.ru/private/api/v2/json/leads/set';

    /*  Авторизация  */
	$curl=curl_init();
    curl_setopt($curl,CURLOPT_RETURNTRANSFER,true);
    curl_setopt($curl,CURLOPT_USERAGENT,'amoCRM-API-client/1.0');
    curl_setopt($curl,CURLOPT_URL,$auth_link);
    curl_setopt($curl,CURLOPT_CUSTOMREQUEST,'POST');
    curl_setopt($curl,CURLOPT_POSTFIELDS,json_encode($user));
    curl_setopt($curl,CURLOPT_HTTPHEADER,array('Content-Type: application/json'));
    curl_setopt($curl,CURLOPT_HEADER,false);
    curl_setopt($curl,CURLOPT_COOKIEFILE,dirname(__FILE__).'/cookie.txt'); #PHP>5.3.6 dirname(__FILE__) -> __DIR__
    curl_setopt($curl,CURLOPT_COOKIEJAR,dirname(__FILE__).'/cookie.txt'); #PHP>5.3.6 dirname(__FILE__) -> __DIR__
    curl_setopt($curl,CURLOPT_SSL_VERIFYPEER,0);
    curl_setopt($curl,CURLOPT_SSL_VERIFYHOST,0);

    $out = curl_exec($curl);
    $code = curl_getinfo($curl,CURLINFO_HTTP_CODE);
    curl_close($curl);

    //var_dump($out);
    //var_dump($code);

    if($code!=200 && $code!=204){
        if(isset($errors[$code])){
            fwrite($flogs,date("d-m-Y H:i:s")." Ошибка авторизации в amoCRM (".$code." ".$errors[$code].")\n");
        }else{
            fwrite($flogs,date("d-m-Y H:i:s")." Ошибка авторизации в amoCRM (".$code.")\n");
        }
    }elseif(preg_match('|"auth":true|',$out)){
        
        #Аккаунт
        $curl=curl_init();
        curl_setopt($curl,CURLOPT_RETURNTRANSFER,true);
        curl_setopt($curl,CURLOPT_USERAGENT,'amoCRM-API-client/1.0');
        curl_setopt($curl,CURLOPT_URL,$account_link);
        curl_setopt($curl,CURLOPT_HEADER,false);
        curl_setopt($curl,CURLOPT_COOKIEFILE,dirname(__FILE__).'/cookie.txt'); #PHP>5.3.6 dirname(__FILE__) -> __DIR__
        curl_setopt($curl,CURLOPT_COOKIEJAR,dirname(__FILE__).'/cookie.txt'); #PHP>5.3.6 dirname(__FILE__) -> __DIR__
        curl_setopt($curl,CURLOPT_SSL_VERIFYPEER,0);
        curl_setopt($curl,CURLOPT_SSL_VERIFYHOST,0);
         
        $response = curl_exec($curl);
        $code = curl_getinfo($curl,CURLINFO_HTTP_CODE);
        curl_close($curl);
        
        $response = json_decode($response,1);
        $response = $response['response'];
        $custom_fields = $response['account']['custom_fields']['contacts'];
        $custom_fields_leads = $response['account']['custom_fields']['leads'];
		$leads_statuses = $response['account']['leads_statuses'];
		$first_contact_status_id = $leads_statuses['0']['id'];
		$user_id = $response['account']['users']['0']['id'];
		
		foreach($custom_fields_leads as $key => $val) {
            switch ($custom_fields_leads[$key]['name']) {
                case "Сообщение":
                    $custom_fields_leads[$key]['value'] = $msg;
                    break;
                case "Удобрение":
                    $custom_fields_leads[$key]['value'] = $fertilizer;
                    break;
                case "Объём удоборений":
                    $custom_fields_leads[$key]['value'] = $volume_fertilizer;
                    break;
                case "Адрес доставки":
                    $custom_fields_leads[$key]['value'] = $address;
                    break;
                case "Источник трафика":
                    $custom_fields_leads[$key]['value'] = $address;
                    break;
                case "Тип трафика":
                    $custom_fields_leads[$key]['value'] = $address;
                    break;
                case "Название рекламной кампании":
                    $custom_fields_leads[$key]['value'] = $address;
                    break;
                case "Ключевое слово кампании":
                    $custom_fields_leads[$key]['value'] = $address;
                    break;
                case "GA UTM":
                    $custom_fields_leads[$key]['value'] = $address;
                    break;
            }   
        }
        
                    
        foreach($custom_fields as $key => $val) {
            switch ($custom_fields[$key]['name']) {
                case "Форма оплаты":
                    $custom_fields[$key]['value'] = key($custom_fields[$key]['enums']);
                    break;
            }   
        }

		if($code!=200 && $code!=204){
            if(isset($errors[$code]))
                fwrite($flogs,date("d-m-Y H:i:s")." Не удалось получить данные об аккаунте в amoCRM (".$code." ".$errors[$code].")\n");
            else 
                fwrite($flogs,date("d-m-Y H:i:s")." Не удалось получить данные об аккаунте amoCRM (".$code.")\n");
        }else{
            //$user_id = $response['response']['contacts']['add'][0]['id'];
            $add['request']['leads']['add'][0]['responsible_user_id'] = $user_id;
            $add['request']['leads']['add'][0]['name'] = "Сделка с почты от: ".$datetime;
            $add['request']['leads']['add'][0]['status_id'] = $first_contact_status_id;
			
			foreach($custom_fields_leads as $field){
				if($field['name'] == 'Сообщение' && $msg != '')
					$add['request']['leads']['add'][0]['custom_fields'][] = array('id'=>$field['id'],'values'=>array(array('value'=>$msg)));
				if($field['name'] == 'Удобрение' && $fertilizer != '')
					$add['request']['leads']['add'][0]['custom_fields'][] = array('id'=>$field['id'],'values'=>array(array('value'=>$fertilizer)));
				if($field['name'] == 'Сколько тонн' && $volume_fertilizer != '')
					$add['request']['leads']['add'][0]['custom_fields'][] = array('id'=>$field['id'],'values'=>array(array('value'=>$volume_fertilizer)));
				if($field['name'] == 'Адрес доставки' && $address != '')
					$add['request']['leads']['add'][0]['custom_fields'][] = array('id'=>$field['id'],'values'=>array(array('value'=>$address)));
                if($field['name'] == 'Источник трафика' && $utm_source != '')
					$add['request']['leads']['add'][0]['custom_fields'][] = array('id'=>$field['id'],'values'=>array(array('value'=>$utm_source)));
                if($field['name'] == 'Тип трафика' && $utm_medium != '')
					$add['request']['leads']['add'][0]['custom_fields'][] = array('id'=>$field['id'],'values'=>array(array('value'=>$utm_medium)));
                if($field['name'] == 'Название рекламной кампании' && $utm_campaign != '')
					$add['request']['leads']['add'][0]['custom_fields'][] = array('id'=>$field['id'],'values'=>array(array('value'=>$utm_campaign)));
                if($field['name'] == 'Ключевое слово кампании' && $utm_term != '')
					$add['request']['leads']['add'][0]['custom_fields'][] = array('id'=>$field['id'],'values'=>array(array('value'=>$utm_term)));
                if($field['name'] == 'GA UTM' && $ga_utm != '')
					$add['request']['leads']['add'][0]['custom_fields'][] = array('id'=>$field['id'],'values'=>array(array('value'=>$ga_utm)));
			}

			
            /*
            $add['request']['leads']['add'][0]['custom_fields'][] = array('id'=>"478100",'values'=>array(array('value'=>$msg)));
            $add['request']['leads']['add'][0]['custom_fields'][] = array('id'=>"478102",'values'=>array(array('value'=>$fertilizer)));
            $add['request']['leads']['add'][0]['custom_fields'][]=array('id'=>"478106",'values'=>array(array('value'=>$volume_fertilizer)));
            $add['request']['leads']['add'][0]['custom_fields'][]=array('id'=>"478108",'values'=>array(array('value'=>$address)));
			*/
            #Сделка
            $curl=curl_init();
            curl_setopt($curl,CURLOPT_RETURNTRANSFER,true);
            curl_setopt($curl,CURLOPT_USERAGENT,'amoCRM-API-client/1.0');
            curl_setopt($curl,CURLOPT_URL,$leads_link);
            curl_setopt($curl,CURLOPT_CUSTOMREQUEST,'POST');
            curl_setopt($curl,CURLOPT_POSTFIELDS,json_encode($add));
            curl_setopt($curl,CURLOPT_HTTPHEADER,array('Content-Type: application/json'));
            curl_setopt($curl,CURLOPT_HEADER,false);
            curl_setopt($curl,CURLOPT_COOKIEFILE,dirname(__FILE__).'/cookie.txt'); #PHP>5.3.6 dirname(__FILE__) -> __DIR__
            curl_setopt($curl,CURLOPT_COOKIEJAR,dirname(__FILE__).'/cookie.txt'); #PHP>5.3.6 dirname(__FILE__) -> __DIR__
            curl_setopt($curl,CURLOPT_SSL_VERIFYPEER,0);
            curl_setopt($curl,CURLOPT_SSL_VERIFYHOST,0);
             
            $response = curl_exec($curl);
            $code = curl_getinfo($curl,CURLINFO_HTTP_CODE);
            curl_close($curl);

            $response = json_decode($response,1);
			$response = $response['response'];
			
            //var_dump($response);
            //var_dump($code);
            unset($add);
			
			if($code!=200 && $code!=204){
            if(isset($errors[$code]))
                fwrite($flogs,date("d-m-Y H:i:s")." Не удалось добавить сделку в amoCRM (".$code." ".$errors[$code].")\n");
            else 
                fwrite($flogs,date("d-m-Y H:i:s")." Не удалось добавить сделку в amoCRM (".$code.")\n");
            }else{
				
				if(!isset($name)) {
					$name = "Клиент";
				}
				
				if($name!='')
					$add['request']['contacts']['add'][0]['name'] = $name;

				$add['request']['contacts']['add'][0]['linked_leads_id'][0] = $response['leads']['add'][0]['id']; 
				
				foreach($custom_fields as $field){
					if($field['code']=='PHONE'&&$phone!='')
						$add['request']['contacts']['add'][0]['custom_fields'][]=array('id'=>$field['id'],'values'=>array(array('value'=>$phone,'enum'=>'MOB')));
					if($field['code']=='EMAIL'&&$phone!='')
						$add['request']['contacts']['add'][0]['custom_fields'][]=array('id'=>$field['id'],'values'=>array(array('value'=>$email,'enum'=>'WORK')));
                    if($field['name']=='Форма оплаты')
						$add['request']['contacts']['add'][0]['custom_fields'][]=array('id'=>$field['id'],'values'=>array($field['value']));
				}
                

				
				#Контакт
				$curl=curl_init();
				curl_setopt($curl,CURLOPT_RETURNTRANSFER,true);
				curl_setopt($curl,CURLOPT_USERAGENT,'amoCRM-API-client/1.0');
				curl_setopt($curl,CURLOPT_URL,$contacts_link);
				curl_setopt($curl,CURLOPT_CUSTOMREQUEST,'POST');
				curl_setopt($curl,CURLOPT_POSTFIELDS,json_encode($add));
				curl_setopt($curl,CURLOPT_HTTPHEADER,array('Content-Type: application/json'));
				curl_setopt($curl,CURLOPT_HEADER,false);
				curl_setopt($curl,CURLOPT_COOKIEFILE,dirname(__FILE__).'/cookie.txt'); #PHP>5.3.6 dirname(__FILE__) -> __DIR__
				curl_setopt($curl,CURLOPT_COOKIEJAR,dirname(__FILE__).'/cookie.txt'); #PHP>5.3.6 dirname(__FILE__) -> __DIR__
				curl_setopt($curl,CURLOPT_SSL_VERIFYPEER,0);
				curl_setopt($curl,CURLOPT_SSL_VERIFYHOST,0);
				 
				$response = curl_exec($curl);
				$code = curl_getinfo($curl,CURLINFO_HTTP_CODE);
				curl_close($curl);

				$response = json_decode($response,1);
				//var_dump($response);
				//var_dump($code);
				unset($add);

				
			}
 
        }

    }
}

?>

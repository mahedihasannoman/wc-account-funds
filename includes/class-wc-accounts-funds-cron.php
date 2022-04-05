<?php
defined( 'ABSPATH' ) || exit();
class WC_Accounts_Funds_CRON {
	/**
	 * WC_Accounts_Funds_CRON constructor
	*/
	public function __construct(){
		add_action('wc_accounts_funds_daily_event',array(__CLASS__,'send_accounts_funds_alert_email'));
	}
	
	/**
	 * Send low account funds email notification
	 * @rerturn bool
	 * @since 1.0.0
	*/
	public static function send_accounts_funds_alert_email(){
		if('yes' != get_option('wc_af_apply_emails_enable')){
			return false;
		}
		
		$funds_thershold = get_option('wc_af_user_fund_limit',10);
		$all_users = get_users(array('fields'=>'all'));
		foreach($all_users as $single_user){
			$available_funds = get_user_meta($single_user->ID,'wc_user_account_funds',true);
			$available_funds = !empty($available_funds) ?  $available_funds : 0;
			if(absint($available_funds) < absint($funds_thershold)){
				$user_email = $single_user->data->user_email;
				$user_name = $single_user->data->display_name;
				$site_title = get_bloginfo('name');
				$email_subject = get_option('wc_af_user_email_subject');
				$email_heading = get_option('wc_af_user_email_heading');
				$email_content = get_option('wc_af_user_email_content');
				
				$patterns = array();
				$patterns['site_title'] = '/{site_title}/';
				$patterns['user_funds'] = '/{user_funds}/';
				$patterns['customer_email'] = '/{customer_email}/';
				$patterns['customer_name'] = '/{customer_name}/';
				$patterns['button_charging'] = '/{button_charging}/';
				
				$replacements = array();
				$replacements['site_title'] = $site_title;
				$replacements['user_funds'] = $available_funds;
				$replacements['customer_email'] = $user_email;
				$replacements['customer_name'] = $user_name;
				$replacements['button_charging'] = '<a href="'.site_url().'/my-account/accounts-funds/'.'">Button Charging</a>';
				
				$email_subject = preg_replace($patterns,$replacements,$email_subject);
				$email_heading = preg_replace($patterns,$replacements,$email_heading);
				$email_content = preg_replace($patterns,$replacements,$email_content);
				
				
				global $woocommerce;
				$mailer = $woocommerce->mailer();
				
				$message = $mailer->wrap_message( $email_subject, $email_heading.'<br>'.$email_content );
				$headers = apply_filters( 'woocommerce_email_headers', '', 'rewards_message' );
				$mailer->send( $user_email, $email_subject, $message, $headers, array() );
				
				exit();
				
			}
		}
		
	}
}
new WC_Accounts_Funds_CRON();
